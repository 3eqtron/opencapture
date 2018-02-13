<?php

class MailCapture 
    extends DOMXPath
{ 
    const MIME_TYPE_TEXT        = 0;
    const MIME_TYPE_MULTIPART   = 1;
    const MIME_TYPE_MESSAGE     = 2;
    const MIME_TYPE_APPLICATION = 3;
    const MIME_TYPE_AUDIO       = 4;
    const MIME_TYPE_IMAGE       = 5;
    const MIME_TYPE_VIDEO       = 6;
    const MIME_TYPE_OTHER       = 8;
    const MIME_TYPE_UNKNOWN     = 9;


    const MIME_ENCODING_7BIT    = 0;
    const MIME_ENCODING_8BIT    = 1;
    const MIME_ENCODING_BINARY  = 2;
    const MIME_ENCODING_BASE64  = 3;
    const MIME_ENCODING_QPRINT  = 4;
    const MIME_ENCODING_OTHER   = 5;
    
    private $Batch;
    
    private $imap_stream;
    private $mailbox;
    private $params = [];
    private $Msg;
    private $folders;
    private $msgRules;
    private $msgOutputs;
    private $attRules;
    private $attOutputs;
    private $logFile;
    private $targetEntityId;
    private $imapErrorTriggered = false;


    private $encodings;
    
    private $attachmentsOutputDir = false;
    private $addHeaderInMailContent = true;
    private $endFileName;

    public function __construct(
    ){       
        $this->Batch = $_SESSION['capture']->Batch;
        
        if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . "MailCapture.xml")) {
            $_SESSION['MailCapture'] = 'MailCapture.xml';
            $Config = new DOMDocument();
            $Config->load(
                __DIR__ . DIRECTORY_SEPARATOR . "MailCapture.xml"
            );
            parent::__construct($Config);
        }
        
        // List encodings in upper case
        $this->encodings = mb_list_encodings();
        foreach ($this->encodings as $encoding) 
            $this->encodings[] = strtoupper($encoding);
    }
                
    private function writeLog($message) {
        if ( empty($this->logFile) ) {
            echo "ERROR: logFile not-open :message: '$message'\n";
            return false;
        }
        return fwrite($this->logFile, date("Y-m-d H:i:s") . ' ' .$message . PHP_EOL);
    }
    
    private function handle_imap_errors(
        $func, $msgno = ''
    ) {
        $errors = array();
        $errors = imap_errors();
        $num_errors = count($errors);
        $actual_errors = 0;
        $clearError = false;
        for($i=0; $i<$num_errors; $i++) {
            $error = trim($errors[$i]);
            if($error) {
                $this->imapErrorTriggered = true;
                if (
                    1 === preg_match(
                        '/Unexpected characters at end of address/',
                        $error,
                        $matches
                    )
                ) {
                    $clearError = true;
                    $_SESSION['capture']->logEvent(
                        'clear error because we found pattern : Unexpected characters at end of address'
                    );
                }
                if (
                    1 === preg_match(
                        '/Must use comma to separate addresses/',
                        $error,
                        $matches
                    )
                ) {
                    $clearError = true;
                    $_SESSION['capture']->logEvent(
                        'clear error because we found pattern : Must use comma to separate addresses'
                    );
                }
                if (
                    1 === preg_match(
                        '/Unexpected characters after address in group/',
                        $error,
                        $matches
                    )
                ) {
                    $clearError = true;
                    $_SESSION['capture']->logEvent(
                        'clear error because we found pattern : Unexpected characters after address in group'
                    );
                }
                if (
                    1 === preg_match(
                        '/Invalid mailbox list/',
                        $error,
                        $matches
                    )
                ) {
                    $clearError = true;
                    $_SESSION['capture']->logEvent(
                        'clear error because we found pattern : Invalid mailbox list'
                    );
                }
                $actual_errors ++;
                $_SESSION['capture']->logEvent(
                    $error,
                    2
                );
            }
        }
        if ($clearError) {
            imap_errors();
        } elseif ($actual_errors > 0) {
            if (
                (
                    $func == 'imap_headerinfo' ||
                    $func == 'imap_fetchstructure' ||
                    $func == 'imap_fetchbody'
                )
                && $msgno <> ''
                && $this->folderError
            ) {
                imap_mail_move(
                    $this->imap_stream, 
                    trim((string) $msgno), 
                    $this->folderError
                );
            }
            
            $_SESSION['capture']->sendError(
                "Error(s) occured during function $func"
            );
        }
    }
       
    /**
    * MAIN Mail Capture function
    * @param string $account : name of account defined in config file
    * @param string $action : 'move' or 'delete'
    * @param string $folder : name of folder 
    */
    function CaptureMails(
        $account,
        $action,
        $configFile=false,
        $folder=false,
        $attachmentsOutputDir=false,
        $addHeaderInMailContent=true,
        $folderError=false
    ) {    
        if (empty($folder) || $folder == 'false') {
            $folder=false;
        }

        if (empty($attachmentsOutputDir) || $attachmentsOutputDir == 'false') {
            $attachmentsOutputDir=false;
        }

        if (empty($addHeaderInMailContent) || $addHeaderInMailContent == 'true') {
            $addHeaderInMailContent=true;
            $this->addHeaderInMailContent=true;
        } elseif ($addHeaderInMailContent == 'false') {
            $addHeaderInMailContent=false;
            $this->addHeaderInMailContent=false;
        }

        if ($folderError) {
            $this->folderError = $folderError;
        }

        $this->logFile = 
            fopen($this->Batch->directory . DIRECTORY_SEPARATOR . 'MailCapture.log', "w");
        
        if ($configFile) {
            $_SESSION['MailCapture'] = $configFile;
            $Config = '';
            $Config = new DOMDocument();
            echo 'LOAD ' . $configFile . ' FOR CAPTURE' . PHP_EOL;
            $Config->load(
                __DIR__ . DIRECTORY_SEPARATOR . $configFile
            );
            parent::__construct($Config);
        }

        if ($attachmentsOutputDir) {
            $this->attachmentsOutputDir = $attachmentsOutputDir;
        }

        /**********************************************************************
        ** Load Account config
        **********************************************************************/
        $accountConfig = 
            $this->query(
                '/MailCapture/accounts/account[@name="'.$account.'"]'
            )->item(0);
        if(!$accountConfig) {
            $_SESSION['capture']->sendError(
                "E-mail account $account is not defined in configuration!"
            );
        }
       
        $this->mailbox = 
            $accountConfig->getElementsByTagName('mailbox')->item(0)->nodeValue;
            
        $username = 
            $accountConfig->getElementsByTagName('username')->item(0)->nodeValue;
           
        $GLOBALS['username'] = $username;
                
        $password = 
            $accountConfig->getElementsByTagName('password')->item(0)->nodeValue;

        if (
            !empty(
                $accountConfig->getElementsByTagName('IMAP_CLIENT_CERT')->item(0)->nodeValue
            )
        ) {
            $this->params['IMAP_CLIENT_CERT'] 
                = $accountConfig->getElementsByTagName('IMAP_CLIENT_CERT')->item(0)->nodeValue;
        }

        if (
            !empty(
                $accountConfig->getElementsByTagName('IMAP_CLIENT_KEY')->item(0)->nodeValue
            )
        ) {
            $this->params['IMAP_CLIENT_KEY'] 
                = $accountConfig->getElementsByTagName('IMAP_CLIENT_KEY')->item(0)->nodeValue;
        }

        //var_dump($this->params);
        
        /**********************************************************************
        ** Load Message rules config
        **********************************************************************/
        $this->MsgRules = 
            $this->query(
                '/MailCapture/messagerules/messagerule'
            );
        /**********************************************************************
        ** Load Message outputs
        **********************************************************************/
        $this->MsgOutputs = 
            $this->query(
                '/MailCapture/messageoutputs/messageoutput'
            );
        
        /**********************************************************************
        ** Load attachment rules
        **********************************************************************/
        $attRulesNode = 
            $this->query(
                '/MailCapture/attachmentrules'
            )->item(0);
        $this->AttMode = $attRulesNode->getAttribute('mode');
        
        $this->AttRules = 
            $this->query(
                '/MailCapture/attachmentrules/attachmentrule'
            );
        $num_attRules = $this->AttRules->length;
        
        /**********************************************************************
        ** Load attachment outputs
        **********************************************************************/
        $this->AttOutputs = 
            $this->query(
                '/MailCapture/attachmentoutputs/attachmentoutput'
            );
        
        /**********************************************************************
        ** Open IMAP stream
        **********************************************************************/
        $this->imap_stream = 
            imap_open(
                $this->mailbox, 
                $username, 
                $password,
                0,
                0,
                $this->params
            );  
        
        if(!$this->imap_stream) {
            $this->handle_imap_errors("imap_open");
        } else {
            // clear errors
            $errors = imap_errors();
        }

        
        /**********************************************************************
        ** Get Folders and ACL for move options
        **********************************************************************/
        $this->folders = 
            imap_list(
                $this->imap_stream,
                $this->mailbox,
                "*"
            );
        $this->handle_imap_errors("imap_list");
               
        /**********************************************************************
        ** Loop on messages
        **********************************************************************/
        $num_msgs = imap_num_msg($this->imap_stream);
        $this->writeLog(
            $num_msgs . " messages in mailbox"
        );
        for ($Msgno=1; $Msgno<=$num_msgs; $Msgno++) {
            $this->writeLog(
                "Process message no " . $Msgno
            );
            $this->imapErrorTriggered = false;
            $capture = $this->captureMsg($Msgno);
            
            /**********************************************************************
            ** Action after process
            **********************************************************************/
            if ($capture) {
                switch ($action) {
                    case 'move':
                        $this->writeLog("Moving message to $folder...");
                        imap_mail_move(
                            $this->imap_stream,
                            $Msgno,
                            $folder
                        );
                        $this->handle_imap_errors("imap_mail_move");
                        break;
                         
                    case 'delete':
                        $this->writeLog(
                            "Deleting message..."
                        );
                        imap_delete(
                            $this->imap_stream,
                            $Msgno
                        );
                        $this->handle_imap_errors("imap_delete");
                        break;

                    case 'none':
                    default:
                        break;
                }
            }
        }
        
        /**********************************************************************
        ** Actually delete tagged messages, get logs and close connection
        **********************************************************************/
        imap_expunge(
            $this->imap_stream
        ); 
        $this->handle_imap_errors("imap_expunge");
        
        imap_close(
            $this->imap_stream 
        );
        $this->handle_imap_errors("imap_close");
        
        fclose($this->logFile);
    }

    
    function ackMail(
        $account,
        $action,
        $configFile=false,
        $folder=false,
        $folderError=false
    ) {
        $Elements = 
            $this->Batch->query(
                '/Batch/Documents/Document'
            );
        $l = $Elements->length;
        for ($i=0; $i<$l; $i++) {
            $Element = $Elements->item($i);
            $currentMsgId = $Element->getMetadata("uid");
            echo 'PROCESS THE MAIL ' . $currentMsgId . PHP_EOL;
            $ackVal = $Element->getMetadata("resId");
            if (empty($ackVal)) {
                echo 'MAIL' . $currentMsgId . ' NOT INTEGRETED IN MAARCH ! ' . PHP_EOL;
                if (!$folderError || $folderError == "false") {
                    $_SESSION['capture']->sendError(
                        'MAIL' . $currentMsgId . ' NOT INTEGRETED IN MAARCH ! '
                    );
                } else {
                    $_SESSION['capture']->logEvent(
                        'MAIL' . $currentMsgId . ' NOT INTEGRETED IN MAARCH ! '
                    );
                    $folder = $folderError;
                }
            }
            //var_dump($folder);
            $metaError = $Element->getMetadata("error");

            if (empty($metaError)) {
                $this->ProcessTheMail(
                    $account,
                    $action,
                    $ackVal,
                    $currentMsgId,
                    $configFile,
                    $folder
                );
            }
        }
    }

    function ProcessTheMail(
        $account,
        $action,
        $ackVal,
        $currentMsgId,
        $configFile=false,
        $folder=false
    ) {
        
        echo 'ACKVAL ' . $ackVal . PHP_EOL;
        if (empty($folder) || $folder == 'false') {
            $folder=false;
        }

        $this->logFile = 
            fopen($this->Batch->directory . DIRECTORY_SEPARATOR . 'MailCapture.log', "a");

        if ($configFile) {
            $Config = '';
            $Config = new DOMDocument();
            echo 'LOAD ' . $configFile . PHP_EOL;
            $Config->load(
                __DIR__ . DIRECTORY_SEPARATOR . $configFile
            );
            parent::__construct($Config);
        }
        
        /**********************************************************************
        ** Load Account config
        **********************************************************************/
        $accountConfig = 
            $this->query(
                '/MailCapture/accounts/account[@name="'.$account.'"]'
            )->item(0);
        if(!$accountConfig) {
            $_SESSION['capture']->sendError(
                "E-mail account $account is not defined in configuration!"
            );
        }

        $this->mailbox = 
            $accountConfig->getElementsByTagName('mailbox')->item(0)->nodeValue;
            
        $username = 
            $accountConfig->getElementsByTagName('username')->item(0)->nodeValue;
            
        $password = 
            $accountConfig->getElementsByTagName('password')->item(0)->nodeValue;

        if (
            !empty(
                $accountConfig->getElementsByTagName('IMAP_CLIENT_CERT')->item(0)->nodeValue
            )
        ) {
            $this->params['IMAP_CLIENT_CERT'] 
                = $accountConfig->getElementsByTagName('IMAP_CLIENT_CERT')->item(0)->nodeValue;
        }

        if (
            !empty(
                $accountConfig->getElementsByTagName('IMAP_CLIENT_KEY')->item(0)->nodeValue
            )
        ) {
            $this->params['IMAP_CLIENT_KEY'] 
                = $accountConfig->getElementsByTagName('IMAP_CLIENT_KEY')->item(0)->nodeValue;
        }
        
        /**********************************************************************
        ** Load Message rules config
        **********************************************************************/
        $this->MsgRules = 
            $this->query(
                '/MailCapture/messagerules/messagerule'
            );
        /**********************************************************************
        ** Load Message outputs
        **********************************************************************/
        $this->MsgOutputs = 
            $this->query(
                '/MailCapture/messageoutputs/messageoutput'
            );
        
        /**********************************************************************
        ** Load attachment rules
        **********************************************************************/
        $attRulesNode = 
            $this->query(
                '/MailCapture/attachmentrules'
            )->item(0);
        $this->AttMode = $attRulesNode->getAttribute('mode');
        
        $this->AttRules = 
            $this->query(
                '/MailCapture/attachmentrules/attachmentrule'
            );
        $num_attRules = $this->AttRules->length;
        
        /**********************************************************************
        ** Load attachment outputs
        **********************************************************************/
        $this->AttOutputs = 
            $this->query(
                '/MailCapture/attachmentoutputs/attachmentoutput'
            );
        
        /**********************************************************************
        ** Open IMAP stream
        **********************************************************************/
        $this->imap_stream = 
            imap_open(
                $this->mailbox, 
                $username, 
                $password,
                0,
                0,
                $this->params
            );
        
        if(!$this->imap_stream) {
            $this->handle_imap_errors("imap_open");
        } else {
            // clear errors
            $errors = imap_errors();
        }

        
        /**********************************************************************
        ** Get Folders and ACL for move options
        **********************************************************************/
        $this->folders = 
            imap_list(
                $this->imap_stream,
                $this->mailbox,
                "*"
            );
        $this->handle_imap_errors("imap_list");
               
        /**********************************************************************
        ** Loop on messages
        **********************************************************************/
        $num_msgs = imap_num_msg($this->imap_stream);
        $this->writeLog(
            $num_msgs . " messages in mailbox"
        );
        
        /**********************************************************************
        ** Action after process
        **********************************************************************/

        switch ($action) {
            case 'move':
                $this->writeLog(
                    "Moving message to $folder..."
                );
                imap_mail_move(
                    $this->imap_stream,
                    $currentMsgId,
                    $folder,
                    CP_UID
                );
                $this->handle_imap_errors("imap_mail_move");
                break;
                
            case 'delete':
                $this->writeLog(
                    "Deleting message..."
                );
                imap_delete(
                    $this->imap_stream,
                    $currentMsgId,
                    FT_UID
                );
                $this->handle_imap_errors("imap_delete");
                break;
                
            case 'none':
            default:
                break;
        }
        
        /**********************************************************************
        ** Actually delete tagged messages, get logs and close connection
        **********************************************************************/
        imap_expunge(
            $this->imap_stream
        );
        $this->handle_imap_errors("imap_expunge");
        
        imap_close(
            $this->imap_stream
        );
        $this->handle_imap_errors("imap_close");
        
        fclose($this->logFile);
    }
    
    function purgeMail(
        $account,
        $delay,
        $configFile=false,
        $folder=false
    ) {
        if (empty($folder) || $folder == 'false') {
            $folder=false;
        }

        $this->logFile = 
            fopen($this->Batch->directory . DIRECTORY_SEPARATOR . 'MailCapture.log', "a+");
        
        if ($configFile) {
            $Config = '';
            $Config = new DOMDocument();
            echo 'LOAD ' . $configFile . ' FOR PURGE' . PHP_EOL;
            $Config->load(
                __DIR__ . DIRECTORY_SEPARATOR . $configFile
            );
            parent::__construct($Config);
        }


        /**********************************************************************
        ** Load Account config
        **********************************************************************/
        $accountConfig = 
            $this->query(
                '/MailCapture/accounts/account[@name="'.$account.'"]'
            )->item(0);
        if(!$accountConfig) {
            $_SESSION['capture']->sendError(
                "E-mail account $account is not defined in configuration!"
            );
        }
       
        $this->mailbox = 
            $accountConfig->getElementsByTagName('mailbox')->item(0)->nodeValue;
            
        $username = 
            $accountConfig->getElementsByTagName('username')->item(0)->nodeValue;
            
        $password = 
            $accountConfig->getElementsByTagName('password')->item(0)->nodeValue;

        if (
            !empty(
                $accountConfig->getElementsByTagName('IMAP_CLIENT_CERT')->item(0)->nodeValue
            )
        ) {
            $this->params['IMAP_CLIENT_CERT'] 
                = $accountConfig->getElementsByTagName('IMAP_CLIENT_CERT')->item(0)->nodeValue;
        }

        if (
            !empty(
                $accountConfig->getElementsByTagName('IMAP_CLIENT_KEY')->item(0)->nodeValue
            )
        ) {
            $this->params['IMAP_CLIENT_KEY'] 
                = $accountConfig->getElementsByTagName('IMAP_CLIENT_KEY')->item(0)->nodeValue;
        }

        $tabExplode = explode('}', $this->mailbox);
        
        if (!empty($tabExplode[1])) {
            $this->mailbox = str_ireplace($tabExplode[1], '', $this->mailbox) . $folder;
        }
        

        // var_dump($this->mailbox);
        // var_dump($this->params);
        
        /**********************************************************************
        ** Load Message rules config
        **********************************************************************/
        $this->MsgRules = 
            $this->query(
                '/MailCapture/messagerules/messagerule'
            );
        /**********************************************************************
        ** Load Message outputs
        **********************************************************************/
        $this->MsgOutputs = 
            $this->query(
                '/MailCapture/messageoutputs/messageoutput'
            );
        
        /**********************************************************************
        ** Load attachment rules
        **********************************************************************/
        $attRulesNode = 
            $this->query(
                '/MailCapture/attachmentrules'
            )->item(0);
        $this->AttMode = $attRulesNode->getAttribute('mode');
        
        $this->AttRules = 
            $this->query(
                '/MailCapture/attachmentrules/attachmentrule'
            );
        $num_attRules = $this->AttRules->length;
        
        /**********************************************************************
        ** Load attachment outputs
        **********************************************************************/
        $this->AttOutputs = 
            $this->query(
                '/MailCapture/attachmentoutputs/attachmentoutput'
            );
        
        /**********************************************************************
        ** Open IMAP stream
        **********************************************************************/
        $this->imap_stream = 
            imap_open(
                $this->mailbox, 
                $username, 
                $password,
                0,
                0,
                $this->params
            );  
        
        if(!$this->imap_stream) {
            $this->handle_imap_errors("imap_open");
        } else {
            // clear errors
            $errors = imap_errors();
        }

        
        /**********************************************************************
        ** Get Folders and ACL for move options
        **********************************************************************/
        $this->folders = 
            imap_list(
                $this->imap_stream,
                $this->mailbox,
                "*"
            );
        $this->handle_imap_errors("imap_list");
               
        /**********************************************************************
        ** Loop on messages
        **********************************************************************/
        $num_msgs = imap_num_msg($this->imap_stream);
        $this->writeLog(
            $num_msgs . " messages in mailbox"
        );
        for ($Msgno=1; $Msgno<=$num_msgs; $Msgno++) {
            $this->writeLog(
                "Process message no " . $Msgno
            );
            
            /**********************************************************************
            ** PURGE MAIL
            **********************************************************************/
                
            $this->writeLog(
                "Deleting message..."
            );

            $imap_header = 
                imap_headerinfo(
                    $this->imap_stream, 
                    $Msgno
                );
            $this->handle_imap_errors("imap_headerinfo", $Msgno);
            $Msg = $this->decode_mime($imap_header);
            //var_export($Msg);
            // echo PHP_EOL . 'message date ' . $Msg->date . PHP_EOL;
            // echo PHP_EOL . 'unix date ' . $Msg->udate . PHP_EOL;
            $expr = "+" . $delay . " days";
            //echo PHP_EOL . 'expr ' . $expr . PHP_EOL;
            $limitDate = strtotime($expr, $Msg->udate);
            // echo PHP_EOL . 'limitDate ' . $limitDate . PHP_EOL;
            // echo PHP_EOL . 'php timestamp date ' . time() . PHP_EOL;
            
            if ($limitDate < time()) {
                //echo 'deleting message' . PHP_EOL;
                $this->writeLog(
                    "Deleting message no " . $Msgno
                );
                imap_delete(
                    $this->imap_stream, 
                    $Msgno
                );
                $this->handle_imap_errors("imap_delete");    
            } else {
                //echo 'NO deleting message' . PHP_EOL;
            }
        }
        
        /**********************************************************************
        ** Actually delete tagged messages, get logs and close connection
        **********************************************************************/
        imap_expunge(
            $this->imap_stream
        ); 
        $this->handle_imap_errors("imap_expunge");
        
        imap_close(
            $this->imap_stream 
        );
        $this->handle_imap_errors("imap_close");
        
        fclose($this->logFile);
    }

    private function applyRule(
        $strucure,
        $rule
    ) {
        $test = $rule->nodeValue;
        
        $value =
            $this->getInfo(
                $rule->getAttribute('info'),
                $strucure
            );
        $applies = false;
        
        /**********************************************************************
        ** Test value
        **********************************************************************/
        switch($rule->getAttribute('op')) {
        case "=":
            if($value == $test) $applies = true;
            break;
        case "&gt;=":
            if($value >= $test) $applies = true;
            break;
        case "&lt;=":
            if($value <= $test) $applies = true;
            break;
        case "&gt;":
            if($value > $test) $applies = true;
            break;
        case "&lt;":
            if($value < $test) $applies = true;
            break;
        case "!=":
        case "&lt;&gt;":
            if($value != $test) $applies = true;
            break;
        case "in":
            if(in_array($value, explode(' ', $test))) {
                $applies = true;
                var_dump($test);
                var_dump($value);
                echo 'iiiiiiiiiiccciiiiiii';
            }
            break;
        case "notin":
            if(!in_array(strtolower($value), explode(' ', strtolower($test)))) $applies = true;
            break;
        case "contains":
            if(strripos($value, $test) !== false) $applies = true;
            break;
          case "nocontains":
            if(strripos($value, $test) === false) $applies = true;
            break;
          case "checkUserMail":
            $mail = array();
            $theString = str_replace(">", "", $value);
            $mail = explode("<", $theString);

            # WS
            require_once('SOAP/Client.php');

            $Config_Capture = new DOMDocument();
            $Config_Capture->load("config" . DIRECTORY_SEPARATOR . $_SESSION['CaptureName']);
            $xpath_Capture = new DOMXpath($Config_Capture);

            $WSDL_Value = $xpath_Capture->query('//input[@name="WSDL"]')->item(0)->nodeValue;

            $MaarchWSClientConfig = $xpath_Capture->query(
                '//step[@name="SendToMaarch"]//input[@name="configFile"]'
            )->item(0)->nodeValue;
            if (!$MaarchWSClientConfig) {
                $MaarchWSClientConfig = 'MaarchWSClient.xml';
            }
            $Config_WS = new DOMDocument();
            $Config_WS->load("modules" . DIRECTORY_SEPARATOR .
                "MaarchWSClient" . DIRECTORY_SEPARATOR . $MaarchWSClientConfig
            );
            $xpath_WS = new DOMXpath($Config_WS);

            $WSDLConfig = $xpath_WS->query('//WSDL[@name="'.$WSDL_Value.'"]')->item(0);

            $uri = $WSDLConfig->getAttribute('uri');

            $proxyArgs = $xpath_WS->query('./proxy/*',$WSDLConfig);

            $l = $proxyArgs->length;
            $proxy = array();
            for($i=0; $i<$l; $i++) {
                $proxyArg = $proxyArgs->item($i);
                $proxyArgName = $proxyArg->nodeName;
                $proxyArgValue = $proxyArg->nodeValue;
                $proxy[$proxyArgName] = (string)$proxyArgValue;
            }    

            $wsdl = new SOAP_WSDL($uri, $proxy, false);
            $client = $wsdl->getProxy();
            
            $isUser = false;
            $this->targetEntityId = "";
            $userWS = $client->checkUserMail($mail[count($mail) -1]);

            if (!$userWS->item->isUser && file_exists( __DIR__ . DIRECTORY_SEPARATOR . "externalContacts.xml")) {
                $xmlfile = simplexml_load_file( __DIR__ . DIRECTORY_SEPARATOR . "externalContacts.xml");

                foreach ($xmlfile->externalContact as $externalContact ) {
                    if ($mail[count($mail) -1] == $externalContact->contactMail) {
                        $isUser = true;
                        $this->targetEntityId = $externalContact->targetEntityId;
                        break;
                    }
                }
            } elseif($userWS->item->isUser){
                // Primary entity is the first row
                $this->targetEntityId = $userWS->item->userEntities[0]->ENTITY_ID;
                $isUser = true;
            }

            if ($test == "true" && !$isUser) {

                $filename = __DIR__ . DIRECTORY_SEPARATOR . "sendmail.ini";
                $iniFile = parse_ini_file($filename);

                //Use to send html mail
                $headers  = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

                mail($mail[count($mail) -1], $iniFile['mail_object'], $iniFile['mail_message'], $headers);
            }

            if (!$isUser) {
                $applies = true;
            }

            break;
        }
        
        /**********************************************************************
        ** If applies, action required
        **********************************************************************/
        if(!$applies) return true;
        
        $this->writeLog(
            "Rule " . $rule->getAttribute('name') . " applied"
        );
        
        switch($rule->getAttribute('action')) {
        case 'ignore':
            break;
            
        case 'delete':
            $this->writeLog(
                "Deleting message..."
            );
            $res = 
                imap_delete(
                    $this->imap_stream, 
                    (int)$strucure->Msgno
                );
            $this->handle_imap_errors("imap_delete");
            break;
        case 'move':
            $this->writeLog(
               "Moving message to ".$rule->getAttribute('folder')."..."
            );
            $res = 
                imap_mail_move(
                    $this->imap_stream, 
                    (string)$strucure->Msgno, 
                    $rule->getAttribute('folder')
                );
            $this->handle_imap_errors("imap_mail_move");
            break;
        }
        
        return false;
        
    }
              
    private function captureMsg(
        $Msgno
    ) {
        /**********************************************************************
        ** Get message header info
        **********************************************************************/
        $imap_header = 
            imap_headerinfo(
                $this->imap_stream, 
                $Msgno
            );
        $this->handle_imap_errors("imap_headerinfo");
        $Msg = $this->decode_mime($imap_header);
        
        /**********************************************************************
        ** Apply message rules (skip process if returns false)
        **********************************************************************/
        for($msgRuleNo=0, $msgRulesLength=$this->MsgRules->length;
            $msgRuleNo<$msgRulesLength; 
            $msgRuleNo++
        ) {
            $process = 
                $this->applyRule(
                    $Msg,
                    $this->MsgRules->item($msgRuleNo)
                );
            if(!$process) {
                $this->writeLog(
                    "Message $Msgno excluded by rule..."
                );
                return false;
            }
        }
        
        $this->Msg = $Msg;
        
        /**********************************************************************
        ** Create tmp folder
        **********************************************************************/
        $Msg->uid = imap_uid ($this->imap_stream, $Msg->Msgno);
        echo 'UID ' . $Msg->uid . PHP_EOL;
        $Msg->Msgid = preg_replace('/[<>\\/:\*\?"|]/', "", $Msg->message_id);
        $Msg->MsgDirectory = $this->Batch->directory . DIRECTORY_SEPARATOR . $Msg->Msgid;
        mkdir($Msg->MsgDirectory, 0777);
        
        /**********************************************************************
        ** Fetch complete structure parts (body/attachments)
        **********************************************************************/
        $imap_structure = 
            imap_fetchstructure(
                $this->imap_stream, 
                $Msg->Msgno
            );
        $this->handle_imap_errors("imap_fetchstructure",$Msg->Msgno);
        $Msg->parts[0] = $imap_structure;

        $additionalHeader = explode("\n", imap_fetchheader($this->imap_stream, $Msgno));
        foreach ($additionalHeader as $value) {
            //if(stripos($value, "X-Priority")!== false || stripos($value, "X-Priority-Label")!== false || stripos($value, "X-Priority-TTL")!== false){
            if(stripos($value, "X-Priority")!== false || stripos($value, "X-Priority-TTL")!== false){
                $xpriorityHeader = explode(":", $value);
                $xpriority       = explode(" ", trim($xpriorityHeader[1]));
                if(is_numeric(trim($xpriority[0]))){
                    $Msg->xpriority  = trim($xpriority[0]);
                }
                break;
            } else if(strpos($value, "Disposition-Notification-To")!== false){
                $Msg->disposition_notification_to = 3; // Accusé de lecture
                break;
            } else if(strpos($value, "Return-Receipt-To")!== false){
                $Msg->return_receipt_to = 2; // Accusé de reception
                break;
            }
        }

        /**********************************************************************
        ** parse structure parts (body/attachments)
        **********************************************************************/
        $this->writeLog("Parse structure parts (body/attachments)...");
        $this->parse(
            $Msg->parts[0], 
            $partAdr = array()
        );
        $this->writeLog("End parse structure parts (body/attachments)...");

        # Debug
        $MsgFile = $Msg->MsgDirectory . DIRECTORY_SEPARATOR . 'Msg_parser.txt';
        $MsgFileHdl = fopen($MsgFile, "w");
        fputs($MsgFileHdl, print_r($Msg,true));
        fclose($MsgFileHdl);
        
        /**********************************************************************
        ** Loop on parts for extract actions
        **********************************************************************/
        $html_body = false;
        $plain_body = false;
        $isThereAnyBodyHere = false;

        $it_part = 0;

        $this->writeLog("Message have ".count($Msg->parser)." parts.");
        $aAttachmentsInfo = [];
        foreach($Msg->parser as $part) {
            $html_body = false;
            $plain_body = false;
            $it_part++;
            $this->writeLog("Part ".$it_part."/".count($Msg->parser)." in progress ...");
            $this->writeLog("SECTION => ".$part->section.", CONTENT_TYPE => ".strtoupper($part->disposition).", MIME_TYPE => ".$part->subtype);
            switch(strtoupper($part->disposition)) {
            case 'BODY':
                $isThereAnyBodyHere = true;
                if($html_body) continue;
                $this->writeLog("Disposition is BODY");
                if($part->subtype == 'html') {
                    $this->createHtml($part);
                    $html_body = true;
                } 
                elseif ($part->subtype == 'plain') {
                    $plain_body = true;
                }
                if(!$Document && ($html_body || $plain_body)) {
                    $content = file_get_contents($part->filepath);
                    if ( empty($content) ) {
                        echo "ERROR: file_get_contents({$part->filepath}) failed\n";
                    }

                    if(mb_detect_encoding($content, 'UTF-8, ISO-8859-1') == "ISO-8859-1"){
                        $this->writeLog("Encodage : ".mb_detect_encoding($content, 'UTF-8, ISO-8859-1'));

                        $new = fopen($part->filepath, 'w+');
                        if ( empty($new) ) {
                            echo "ERROR: fopen({$part->filepath}, w+) failed\n";
                        } else {
                            fputs($new, utf8_encode($content));
                            fclose($new);
                        }
                    }

                    # add new document with first BODY part found
                    $Document = 
                        $this->Batch->addDocument(
                            $part->filepath
                        );
                    $this->writeLog("Document " . $Document->id 
                        . " added with source " . $part->filepath);


                    for($i=0, $num_MsgOutputs=$this->MsgOutputs->length;
                        $i<$num_MsgOutputs; 
                        $i++
                    ){
                        $MsgOutput = $this->MsgOutputs->item($i);
                        if ($this->attachmentsOutputDir && $MsgOutput->getAttribute('name') == 'end_file_name') {
                            $this->endFileName = $MsgOutput->nodeValue;
                        }

                        $this->addMetadata(
                            $MsgOutput,
                            $Msg,
                            $Document
                        );
                    }


                    //if ($this->targetEntityId <> "") {
                        $Document->setMetadata(
                            "targetEntityId", 
                            $GLOBALS['username']
                        );
                    //}

                } 
                elseif($Document && $html_body) {
                    # Replace document source if HTML found after PLAIN
                    @unlink($Document->path);

                    $fromaddress = $Document->getMetadata("fromaddress");
                    $doc_date = $Document->getMetadata("doc_date");

                    libxml_use_internal_errors(true);
                    
                    $doc = new DOMDocument();
                    $doc->loadHTMLFile($part->filepath);
                    $body = $doc->getElementsByTagName('body');
                    if ($this->addHeaderInMailContent) {
                        $metadatas_doc_date= $doc->createElement("p", "");
                        $body->item(0)->insertBefore($metadatas_doc_date, $body->item(0)->firstChild);
                        $metadatas_doc_date= $doc->createElement("div", "Re&ccedil;u le : ".$doc_date);
                        $body->item(0)->insertBefore($metadatas_doc_date, $body->item(0)->firstChild);
                        $metadatas_fromaddress = $doc->createElement("div", "Envoy&eacute; par : ".$fromaddress);
                        $body->item(0)->insertBefore($metadatas_fromaddress, $body->item(0)->firstChild);
                    }
                    $doc->saveHTMLFile($part->filepath);

                    $Document->importResource($part->filepath);
                    $this->writeLog("Document " . $Document->id 
                        . " source replaced with " . $part->filepath);
                }else{
                    $this->writeLog("mime_type : ".$part->subtype." is unsupported, no process found.");
                }
                if ($this->imapErrorTriggered) {
                    $metaError = $Document->getMetadata('error');
                    if (empty($metaError)) {
                        $Document->setMetadata('error', true);
                    }
                }
                
                break;
                
            //case 'ATTACHMENT':
            default:
                //On récupère les informations de la pj pour éviter de récupérer 2 fois la meme pj car elle peut être en inline et attachment
                $sAttachmentInfos = $part->subtype . '_' . $part->bytes;
                if(file_exists($part->filepath)){
                    $sAttachmentInfos .= '_' . md5_file($part->filepath);
                }

                if ((strtoupper($part->disposition) == 'ATTACHMENT' || strtoupper($part->disposition) == 'ATTACHEMENT') && !isset($aAttachmentsInfo[$sAttachmentInfos])) {
                    echo 'captureMsg CASE ATTACHMENT' . ' Attachment ' . $part->section . PHP_EOL;
                    $this->writeLog('captureMsg CASE ATTACHMENT  Attachment ' . $part->section);
                } elseif (strtoupper($part->disposition) == 'INLINE' && !isset($aAttachmentsInfo[$sAttachmentInfos])) {
                    echo 'INLINE CASE -> IT IS AN ATTACHMENT' . PHP_EOL;
                    $this->writeLog('INLINE CASE -> IT IS AN ATTACHMENT');
                } elseif(isset($aAttachmentsInfo[$sAttachmentInfos])){
                    echo 'File already added => format_bytes_md5 : ' . $sAttachmentInfos . PHP_EOL;
                    $this->writeLog('File already added => format_bytes_md5 : ' . $sAttachmentInfos);
                    break;
                } else {
                    echo 'UNKNOW DISPOSITION!' . PHP_EOL;
                    $this->writeLog("Unknow Disposition : ".strtoupper($part->disposition));
                    break;
                }

                $this->writeLog('File added => format_bytes_md5 : ' . $sAttachmentInfos);
                $aAttachmentsInfo[$sAttachmentInfos] = true;

                if (!$isThereAnyBodyHere) {
                    $Document = 
                        $this->Batch->addDocument(
                            $part->filepath
                        );
                    $this->writeLog("Document with NO BODY " . $Document->id 
                        . " added with source " . $part->filepath);
                        
                    for($i=0, $num_MsgOutputs=$this->MsgOutputs->length;
                        $i<$num_MsgOutputs; 
                        $i++
                    ){
                        $MsgOutput = $this->MsgOutputs->item($i);
                        $this->addMetadata(
                            $MsgOutput,
                            $Msg,
                            $Document
                        );
                    }

                    if ($this->targetEntityId <> "") {
                        $Document->setMetadata(
                            "targetEntityId", 
                            $this->targetEntityId
                        );
                    }
                }
                
                $this->writeLog("Disposition is ATTACHMENT");
                /**********************************************************************
                ** Apply attachment rules (skip process if returns false)
                **********************************************************************/
                for($attRuleNo=0, $num_attRules = $this->AttRules->length;
                    $attRuleNo<$num_attRules; 
                    $attRuleNo++
                ) {
                    $process = 
                        $this->applyRule(
                            $part,
                            $this->AttRules->item($attRuleNo)
                        );
                    if(!$process) {
                        $this->writeLog(
                            "Attachment part ".$part->section." (mimetype ".$part->mimetype.", extension ".$part->extension.") excluded by rule..."
                        );
                        $_SESSION['capture']->logEvent(
                            "Attachment part ".$part->section." (mimetype ".$part->mimetype.", extension ".$part->extension.") excluded by rule...",
                            1
                        );
                        echo "Attachment part ".$part->section." (mimetype ".$part->mimetype.", extension ".$part->extension.") excluded by rule..." . PHP_EOL;
                        $Document->setMetadata(
                            "attachmentRuleError", 
                            "Attachment part ".$part->section." (mimetype ".$part->mimetype.", extension ".$part->extension.") excluded by rule..."
                        );

                        continue 2;
                    }
                }
                
                /**********************************************************************
                ** Add Attachment to batch as new document or attachment to current
                **********************************************************************/
                switch($this->AttMode) { 
                case 'document':
                    $DocMetadata = $Document->getContainer('Metadata');
                    $Attachment = 
                        $this->Batch->addDocument(
                            $part->filepath
                        );
                    $this->writeLog("Attachment added as new Document with id " 
                        . $Attachment->id . " from source ". $part->filepath);    
                     
                    $AttMetadata = $Attachment->getContainer('Metadata');
                    $Attachment->replaceChild(
                        $DocMetadata->cloneNode(true),
                        $AttMetadata
                    );
                    break;
                    
                case 'attachment':
                default:
                    $Attachment = 
                        $Document->addAttachment(
                            $part->filepath
                        );
                    $extAtt = strtolower(pathinfo($part->filepath, PATHINFO_EXTENSION));
                    if ($this->attachmentsOutputDir && $extAtt <> 'txt') {
                        if ($this->endFileName <> '') {
                            $fileName = 'maarchcapture_maarch_' 
                                . date('Ymd_His') . '_' . $Attachment->id
                                . '_' . $this->endFileName
                                . '.' . $extAtt;
                        } else {
                            for($i=0, $num_MsgOutputs=$this->MsgOutputs->length;
                                $i<$num_MsgOutputs; 
                                $i++
                            ){
                                $MsgOutput = $this->MsgOutputs->item($i);

                                if ($this->attachmentsOutputDir && $MsgOutput->getAttribute('name') == 'end_file_name') {
                                    $this->endFileName = $MsgOutput->nodeValue;
                                }
                            }
                            if ($this->endFileName <> '') {
                                $fileName = 'maarchcapture_maarch_' 
                                    . date('Ymd_His') . '_' . $Attachment->id
                                    . '_' . $this->endFileName
                                    . '.' . $extAtt;
                            } else {
                                $fileName = 'maarchcapture_maarch_' 
                                    . date('Ymd_His') . '_' . $Attachment->id
                                    . '.' . $extAtt;
                            }
                        }

                        if (!copy(
                            $part->filepath, 
                            $this->attachmentsOutputDir 
                                . DIRECTORY_SEPARATOR 
                                . $fileName
                        )) {
                            $this->writeLog('PB !!! copy ' . $part->filepath . DIRECTORY_SEPARATOR 
                                . $Attachment->id . ' to ' . $this->attachmentsOutputDir 
                                . DIRECTORY_SEPARATOR . $fileName);
                        } else {
                            $this->writeLog('copy ' . $part->filepath . DIRECTORY_SEPARATOR 
                                . $Attachment->id . ' to ' . $this->attachmentsOutputDir 
                                . DIRECTORY_SEPARATOR . $fileName);
                        }
                    }
                    
                    $this->writeLog("Attachment added as new Attachment with id " 
                        . $Attachment->id . " from source ". $part->filepath);  
                }
                
                /**********************************************************************
                ** Add attachment specific Metadata
                **********************************************************************/
                for($attOutputNo=0, $num_AttOutputs=$this->AttOutputs->length; 
                    $attOutputNo<$num_AttOutputs; 
                    $attOutputNo++
                ) {
                    $AttOutput = $this->AttOutputs->item($attOutputNo);
                    $this->addMetadata(
                        $AttOutput,
                        $part,
                        $Attachment
                    );
                }
                //break;
            //default:
            //    $this->writeLog("Unknow Disposition : ".strtoupper($part->disposition));
            break;
            }
        }
          
          return true;          
    }
    
    private function parse(
        $part,
        $partAdr = array()
    ) {
        #*********************************************************************
        # PARSE PART INFO
        #*********************************************************************
        # part address
        #*********************************************************************
        $partAdrString = implode('.', $partAdr);
        if(!$partAdrString) $partAdrString = '1';
        $part->section = $partAdrString;
        
        # Type
        #*********************************************************************
        switch($part->type) {
        case self::MIME_TYPE_TEXT:  
            $part->typename = 'text';
            break;
        case self::MIME_TYPE_MULTIPART: 
            $part->typename = 'multipart';
            break;
        case self::MIME_TYPE_MESSAGE: 
            $part->typename = 'message';
            break;
        case self::MIME_TYPE_APPLICATION: 
            $part->typename = 'application';
            break;
        case self::MIME_TYPE_AUDIO: 
            $part->typename = 'audio';
            break;
        case self::MIME_TYPE_IMAGE:
            $part->typename = 'image';
            break;
        case self::MIME_TYPE_VIDEO: 
            $part->typename = 'video';
            break;
        case self::MIME_TYPE_OTHER: 
            $part->typename = 'other';
            break;

        case self::MIME_TYPE_UNKNOWN: 
        default:
            $part->typename = 'unknown';
            break;
        }
                
        # Encoding
        #*********************************************************************
        switch((integer)$part->encoding) {
        case self::MIME_ENCODING_7BIT:
            $part->encodingname = '7BIT';
            break;
        case self::MIME_ENCODING_8BIT: 
            $part->encodingname = '8BIT';
            break;
        case self::MIME_ENCODING_BINARY: 
            $part->encodingname = 'BINARY';
            break;
        case self::MIME_ENCODING_BASE64: 
            $part->encodingname = 'BASE64';
            break;
        case self::MIME_ENCODING_QPRINT: 
            $part->encodingname = 'QPRINT';
            break;
        case self::MIME_ENCODING_OTHER:
            $part->encodingname = 'OTHER';
            break;
        }
        
        # Subtype
        #*********************************************************************
        if($part->ifsubtype)
            $part->subtype = strtolower($part->subtype);
        else 
            $part->subtype = false;
            
        # Mimetype
        #*********************************************************************
        $part->mimetype = $part->typename . '/' . $part->subtype;
        
        # Disposition
        #*********************************************************************
        if(!$part->ifdisposition)
            $part->disposition = 'BODY';
            
        # Dparameters
        #*********************************************************************
        if($part->ifdparameters) {
            foreach($part->dparameters as $i => $dparameter) {
                $dparameterName = strtolower($dparameter->attribute);
                $dparameterValue = $this->decode_string($dparameter->value);
                $part->dparameters[$dparameterName] = $dparameterValue;
            }
        }
        
        # Parameters
        #*********************************************************************
        if($part->ifparameters) {
            foreach($part->parameters as $parameter) {
                $parameterName = strtolower($parameter->attribute);
                $parameterValue = $this->decode_string($parameter->value);
                $part->parameters[$parameterName] = $parameterValue;
            }
        }
        
        # Extension of file if no filename
        #*********************************************************************
        if($part->subtype == 'plain')
            $part->extension = 'txt';
        else if(is_array($part->dparameters) && isset($part->dparameters['filename'])) 
            $part->extension = substr(strrchr($part->dparameters['filename'], '.'), 1);
        else if(is_array($part->parameters) && isset($part->parameters['name'])) 
            $part->extension = substr(strrchr($part->parameters['name'], '.'), 1);
        else
            $part->extension = $part->subtype;
        
        # CID / Filename
        #*********************************************************************
        $filename = false;
        if($part->ifid)
            $filename = substr($part->id, 1, strlen($part->id) -2);
        else
            $filename = $part->section;
        if($part->extension)
            $filename .= "." . $part->extension;
        $part->filepath = $this->Msg->MsgDirectory . DIRECTORY_SEPARATOR . $filename;
        
       
        # Get data
        #*********************************************************************
        $encodeddata = 
            imap_fetchbody(
                $this->imap_stream, 
                $this->Msg->Msgno,
                $part->section
            );
        
        $part->data = $this->decode_data($encodeddata, $part->encoding);
        
        #*********************************************************************
        # USE PARSED INFO 
        #*********************************************************************
        # Output Data / parse subParts
        #*********************************************************************
        switch((integer)$part->type) {
        case self::MIME_TYPE_MULTIPART: 
            $partCount = count($part->parts);
            for($i=0; $i<$partCount; $i++) {
                $subpart = $part->parts[$i];
                $subpartAdr = $partAdr;
                $subpartAdr[] = $i+1;
                $this->parse(
                    $subpart,
                    $subpartAdr
                );
            }
            break;
            
        case self::MIME_TYPE_MESSAGE: 
            # What ?
            break;
        
        case self::MIME_TYPE_TEXT:  
        case self::MIME_TYPE_APPLICATION: 
        case self::MIME_TYPE_AUDIO: 
        case self::MIME_TYPE_IMAGE: 
        case self::MIME_TYPE_VIDEO: 
        case self::MIME_TYPE_OTHER: 
        case self::MIME_TYPE_UNKNOWN:
        default: 
            $fp = fopen($part->filepath, "w");
            if ( empty($fp) ) {
                echo "ERROR: fopen({$part->filepath}, w) failed\n";
            } else {
                fputs($fp, $part->data);
                fclose($fp);
            }
            break;
        }
                
        $this->Msg->parser[] = &$part;
        
        $this->handle_imap_errors("imap_fetchbody", $this->Msg->Msgno);
        
    }
    
    function decode_mime(
        $data
    ) {
        if(is_object($data))
            foreach($data as $propertyName => $propertyValue) {
                $data->$propertyName = $this->decode_mime($propertyValue);
            }
        elseif(is_array($data))
            foreach($data as $propertyName => $propertyValue) {
                $data[$propertyName] = $this->decode_mime($propertyValue);
            }
        else
            $data = iconv_mime_decode($data, 2, 'UTF-8');
        
        return $data;
    }
    
    function decode_string(
        $string
    ) {
        $stringArr = imap_mime_header_decode($string);
        $decodedString = '';
        for ($i=0; $i<count($stringArr); $i++) {
            $encoding = strtoupper($stringArr[$i]->charset);
            $text = $stringArr[$i]->text;
            switch ($encoding) {
            case 'UTF-8': 
                $decodedString.= $text;
                break;
                
            case 'DEFAULT': 
                $decodedString.= utf8_encode(utf8_decode($text));
                break;
                
            default: 
                if (in_array($encoding, $this->encodings))
                    $decodedString.= mb_convert_encoding($text, 'UTF-8', $encoding);
                elseif ($conv = iconv($encoding, "UTF-8", $text))
                    $decodedString.= $conv;
                else 
                    $decodedString.= $text;  // Unknown charset
            }
        }
           
        return $decodedString;   
    }
    
    function decode_data(
        $encodeddata,
        $mimeencoding
    ) {
        switch ($mimeencoding) {
        case self::MIME_ENCODING_7BIT:
            $decodeddata = $encodeddata;
            break;
            
        case self::MIME_ENCODING_8BIT:
            $decodeddata = 
                quoted_printable_decode(
                    imap_8bit($encodeddata)
                );
            break;
            
        case self::MIME_ENCODING_BINARY:
            $decodeddata = $encodeddata;
            break;
            
        case self::MIME_ENCODING_BASE64:
            $decodeddata = imap_base64($encodeddata);
            break;
            
        case self::MIME_ENCODING_QPRINT:
            $decodeddata = imap_qprint($encodeddata);
            break;
            
        case self::MIME_ENCODING_OTHER:
            break;
        }
        
        return $decodeddata;
    
    }
    
    private function addMetadata(
        $output,
        $structure,
        $owner
    ) {
        # Structure Info
        if($info = $output->getAttribute('info') )
            $value = 
                $this->getInfo(
                    $info,
                    $structure
                );
        
        else 
            $value = $output->nodeValue;
        
        if($formatter = $output->getAttribute('formatter')) {
            $formatterConfig =
                $this->query(
                    '/MailCapture/formatters/formatter[@name="'.$formatter.'"]'
                )->item(0);
            if(!$formatterConfig) {
                $_SESSION['capture']->sendError(
                    "Undefined formatter $formatter"
                );
            }
            $formatterScript = __DIR__ . DIRECTORY_SEPARATOR . $formatterConfig->getAttribute('script');
            if(!is_file($formatterScript))
                $_SESSION['capture']->sendError(
                    "Can not open formatter script $formatterScript"
                );
 
            require_once $formatterScript;
            $formatterFunc = $formatterConfig->getAttribute('func');
            $value = call_user_func($formatterFunc, $value);
        }   
        
        if (!is_bool($owner)) {
            $owner->setMetadata(
                $output->getAttribute('name'), 
                $value
            );
        } else {
            //var_dump($owner);
            //echo "is bool so no setMetadata ! ";
        }
        
    
    }
       
    private function getInfo(
        $info,
        $structure
    ) {
        $infopath = explode('/', $info);
        $value = $structure;
        foreach($infopath as $infostep) {
            //echo "eval " . '$value = $value->' . $infostep . ";" . PHP_EOL;
            eval('$value = $value->' . $infostep . ";");
        }
        
        if(is_scalar($value)) {
            $value = html_entity_decode($value);
            return $value;
        }
    }
    
    function createHtml(
        $part
    ) {
        
        $htmldata = $part->data;
        
        //convert all chars in htmlentities if not in utf-8 encoding
        $htmldata = str_replace("é", "&eacute;",$htmldata);
        $htmldata = str_replace("è","&egrave;",$htmldata);
        $htmldata = str_replace("ê","&ecirc;",$htmldata);
        $htmldata = str_replace("ë","&euml;",$htmldata);

        $htmldata = str_replace("É", "&Eacute;",$htmldata);
        $htmldata = str_replace("È","&Egrave;",$htmldata);
        $htmldata = str_replace("Ê","&Ecirc;",$htmldata);
        $htmldata = str_replace("Ë","&Euml;",$htmldata);

        $htmldata = str_replace("à","&agrave;",$htmldata);
        $htmldata = str_replace("À","&Agrave;",$htmldata);
        $htmldata = str_replace("â","&acirc;",$htmldata);
        $htmldata = str_replace("î","&icirc;",$htmldata);
        $htmldata = str_replace("ç","&ccedil;",$htmldata);
        $htmldata = str_replace("ô","&ocirc;",$htmldata);
        $htmldata = str_replace("Ô","&Ocirc;",$htmldata);
        $htmldata = str_replace("ù","&ugrave;",$htmldata);
        $htmldata = str_replace("Ù","&Ugrave;",$htmldata);
        $htmldata = str_replace("û","&ucirc;",$htmldata);
        $htmldata = str_replace("œ","&oelig;",$htmldata);
        $htmldata = str_replace("æ","&aelig;",$htmldata);

        $htmldata = str_replace("'","&lsquo;",$htmldata);
        $htmldata = str_replace("’","&rsquo;",$htmldata);
        $htmldata = str_replace("‘","&lsquo;",$htmldata);
        $htmldata = str_replace("'","&lsquo;",$htmldata);
        $htmldata = str_replace("“","&ldquo;",$htmldata);
        $htmldata = str_replace("”","&rdquo;",$htmldata);
        $htmldata = str_replace("–","&ndash;",$htmldata);
        $htmldata = str_replace(chr(0xC2).chr(0xA0),"&nbsp;",$htmldata);

        echo "detect encoding" . PHP_EOL;
        echo ("Encodage : " . mb_detect_encoding($htmldata, 'UTF-8, ISO-8859-1')) . PHP_EOL;

        # Force UTF-8 encoding
        /*if(strtoupper($part->parameters['charset']) == 'UTF-8') {
            echo "in UTF-8 so decode" . PHP_EOL;
            $htmldata = utf8_decode($htmldata);
        }*/
        

        if(mb_detect_encoding($htmldata, 'UTF-8, ISO-8859-1') == "ISO-8859-1"){
            echo ("Resolve Encoding : " . mb_detect_encoding($htmldata, 'UTF-8, ISO-8859-1')) . PHP_EOL;
            utf8_encode($htmldata);
            echo ("after resolve : " . mb_detect_encoding($htmldata, 'UTF-8, ISO-8859-1')) . PHP_EOL;
        }


        # Clean up qprint data and other particularities
        #$htmldata = str_replace("=3D", "=", $htmldata);
        #$htmldata = str_replace("’", "'", $htmldata);
        #$htmldata = str_replace("€", "E", $htmldata);
        
        # Outlook namespace tags
        #$htmldata = str_replace('<o:', '<', $htmldata);
        #$htmldata = str_replace('</o:', '</', $htmldata);
        
        # Processing instructions
        #$htmldata = preg_replace('/<!\[.[^\]]*\]>/i', ' ', $htmldata);
        # Comments
        #$htmldata = preg_replace('/<!\-\-.[^\-\-]]*\-\->/i', ' ', $htmldata);
        # wrong tags
        #$htmldata = str_replace('<br>', '<br/>', $htmldata);

        $tmpVar = trim($htmldata);
        if (empty($tmpVar)) {
            $htmldata = '<!DOCTYPE html>
<html>
<head>
   <meta name="AUTHOR" content="Maarch"/>
   <meta name="CHANGEDBY" content="Maarch"/>
</hea>
<body>

</body>
</html>
';
        }

        $htmlDoc = new DOMDocument();
        @$htmlDoc->loadHTML($htmldata);
        $htmlXpath = new DOMXPath($htmlDoc);
        
        # Structure
        #********************************************************************************
        $html = $htmlDoc->documentElement;
        //var_dump($htmldata);
        //exit;
        //var_dump($html);
        # BODY
        $body = $htmlXpath->query('./body', $html)->item(0);
        if(!$body) {
            $contents = $html->childNodes;
            $body = $htmlDoc->createElement('body');
            $html->appendChild($body);
            # Append original content to body
            for($i=0, $l=$contents->length; 
                $i<$l; 
                $i++ 
            )
                $body->appendChild(
                    $contents->item($i)
                );
        }
        
        # HEAD
        $head = $htmlXpath->query('./head', $html)->item(0);
        if(!$head) {
            $head = $htmlDoc->createElement('head');
            $html->insertBefore($head, $body);
        }
        # META
        $meta = $htmlXpath->query('./meta[@http-equiv and @content]', $head)->item(0);
        if(!$meta) {
            $meta = $htmlDoc->createElement('meta');
            $head->appendChild($meta);  
        } 
        # Set encoding to UTF-8
        $meta->setAttribute('http-equiv', "Content-Type");
        $meta->setAttribute('content', "text/html; charset=UTF-8");

        $metaConvert1 = $htmlDoc->createElement('meta');
        $head->appendChild($metaConvert1);
        $metaConvert1->setAttribute('name', "AUTHOR");
        $metaConvert1->setAttribute('content', "Maarch");

        $metaConvert2 = $htmlDoc->createElement('meta');
        $head->appendChild($metaConvert2);
        $metaConvert2->setAttribute('name', "CHANGEDBY");
        $metaConvert2->setAttribute('content', "Maarch");
        
        # File sources
        #********************************************************************************
        //echo PHP_EOL . 'avant inclusion fichiers' . PHP_EOL;
        $srcCidTags = $htmlXpath->query('/'.'/*[starts-with(@src, "cid:")]', $html);
        for($i=0, $l=$srcCidTags->length; 
                $i<$l; 
                $i++ 
        ) {
            //echo ' element ' . $srcCidTags->item($i) . PHP_EOL;
            $srcCidTag = $srcCidTags->item($i);
            //echo 'an element : ' . PHP_EOL;
            //var_dump($srcCidTag);
            $src = $srcCidTag->getAttribute('src');
            //echo 'src : ' . $src . PHP_EOL;
            
            $cid = substr($src, 4);
            //echo 'cid : ' . $cid . PHP_EOL;
            $filename = $this->Msg->MsgDirectory . DIRECTORY_SEPARATOR . $cid;
            //echo 'filename : ' . $filename . PHP_EOL;
            
            if (is_file($filename . '.jpg')) {
                $filename .= '.jpg';
                //echo 'the good filename : ' . $filename . PHP_EOL;
            } elseif (is_file($filename . '.jpeg')) {
                $filename .= '.jpeg';
                //echo 'the good filename : ' . $filename . PHP_EOL;
            } elseif (is_file($filename . '.png')) {
                $filename .= '.png';
                //echo 'the good filename : ' . $filename . PHP_EOL;
            } elseif (is_file($filename . '.gif')) {
                $filename .= '.gif';
                //echo 'the good filename : ' . $filename . PHP_EOL;
            } elseif (is_file($filename . '.bmp')) {
                $filename .= '.bmp';
                //echo 'the good filename : ' . $filename . PHP_EOL;
            }
            //echo 'is file : ' . is_file($filename) . PHP_EOL;
            if(is_file($filename)) {
                //echo 'iciiiiiiiiiiiiiiiiiiiiiiii ' . PHP_EOL;
                $filecontent = chunk_split(base64_encode(file_get_contents($filename)));
                //echo 'filecontent : ' . $filecontent . PHP_EOL;
                $srcCidTag->setAttribute( 
                    'src',
                    "data:image/gif;base64," . $filecontent
                );
            }
        }
        //exit;
        $part->data = $htmlDoc->saveHTML();
        
        $htmlDoc->saveHTMLFile($part->filepath);
    }


    /*************************************************/
    /*          Format du mail attendu :              */
    /*************************************************/
    // Civilité: Monsieur
    // Nom: Dubois
    // Prénom: Jean
    // Société: EDF
    // Fonction: Président
    // Message: Prise en charge des frais de dossiers
    // Votre adresse de courriel (nom@exemple.fr): jean.dubois@test.com
    // Adresse: 11 boulevard du sud-est
    // Code postal: 92000
    // Ville: NANTERRE
    // Pays: FRANCE 
    function extractContactInfo($ContentPath)
    {
         $Batch = $_SESSION['capture']->Batch;
         $BatchId = $Batch->id;
        // /********************************************************************************
        // ** Loop on DOCUMENT Elements
        // ********************************************************************************/
        $OriginElements = $this->Batch->query($ContentPath);
        $l = $OriginElements->length;
        for ($i=0;$i<$l;$i++) {
            $Element = $OriginElements->item($i);
            if (substr($Element->resourcepath, -4) == 'html') {
                $txtpath = substr($Element->resourcepath, 0, -6).'1.txt';
            } else {
                $txtpath = $Element->resourcepath;
            }

            $_SESSION['capture']->logEvent(
                    "Open resource : " . $txtpath
                );

            $txtmail = fopen($txtpath, 'r');
            $contents = fread($txtmail, filesize($txtpath));

            $_SESSION['capture']->logEvent(
                    "Set Contact Metadata"
                );

         $contact_title = substr($contents, strpos($contents, "Civilité:") + 10);
            $contact_title = substr($contact_title, 0, strpos($contact_title, "Nom:"));

         if(trim($contact_title) == "Monsieur"){
          $Element->setMetadata("contact_title", "title1");
         }else{
          $Element->setMetadata("contact_title", "title2");
         }
            $contact_name = substr($contents, strpos($contents, "Nom:") + 4);
            $contact_name = substr($contact_name, 0, strpos($contact_name, "Prénom:"));
            $contact_name = preg_replace("/(\r\n|\n|\r)/", " ", $contact_name);
            $Element->setMetadata("contact_name", mb_strtoupper(trim($contact_name)));

            $contact_firstname = substr($contents, strpos($contents, "Prénom:") + 8);
            $contact_firstname = substr($contact_firstname, 0, strpos($contact_firstname, "Raison sociale:"));
            $contact_firstname = preg_replace("/(\r\n|\n|\r)/", " ", $contact_firstname);
            $Element->setMetadata("contact_firstname", trim($contact_firstname));

            $contact_society = substr($contents, strpos($contents, "Raison sociale:") + 15);
            $contact_society = substr($contact_society, 0, strpos($contact_society, "Type de contact:"));
            $contact_society = preg_replace("/(\r\n|\n|\r)/", " ", $contact_society);
            $Element->setMetadata("contact_society", mb_strtoupper(trim($contact_society)));

            $contact_type = substr($contents, strpos($contents, "Type de contact:") + 16);
            $contact_type = substr($contact_type, 0, strpos($contact_type, "Numéro de SIREN:"));
            $contact_type = preg_replace("/(\r\n|\n|\r)/", " ", $contact_type);
            $Element->setMetadata("contact_type", mb_strtoupper(trim($contact_type)));

            $contact_other_data = substr($contents, strpos($contents, "Numéro de SIREN:"));
            $contact_other_data = substr($contact_other_data, 0, strpos($contact_other_data, "Message:"));
            $contact_other_data = preg_replace("/(\r\n|\n|\r)/", " ", $contact_other_data);
            $Element->setMetadata("contact_other_data", mb_strtoupper(trim($contact_other_data)));

            $res_subject = substr($contents, strpos($contents, "Message:") + 8);
            $res_subject = substr($res_subject, 0, strpos($res_subject, "Votre adresse de courriel (nom@exemple.fr):"));
            $res_subject = preg_replace("/(\r\n|\n|\r)/", " ", $res_subject);
            $Element->setMetadata("res_subject", mb_strtoupper(trim($res_subject)));

            $contact_mail = substr($contents, strpos($contents, "Votre adresse de courriel (nom@exemple.fr):") + 43);
            $contact_mail = substr($contact_mail, 0, strpos($contact_mail, "Adresse:"));
            $contact_mail = preg_replace("/(\r\n|\n|\r)/", " ", $contact_mail);
            $Element->setMetadata("email", trim($contact_mail));
            $Element->setMetadata("fromaddress", trim($contact_firstname) . " " .  trim($contact_name)  ." <".trim($contact_mail).">");
            $Element->setMetadata("frompersonal", trim($contact_firstname) . " " .  trim($contact_name));
            
            $contact_address = substr($contents, strpos($contents, "Adresse:") + 8);
            $contact_address = substr($contact_address, 0, strpos($contact_address, "Code postal:"));
            $contact_address = preg_replace("/(\r\n|\n|\r)/", " ", $contact_address);
            $Element->setMetadata("address_street", trim($contact_address));

            $contact_cp = substr($contents, strpos($contents, "Code postal:") + 12);
            $contact_cp = substr($contact_cp, 0, strpos($contact_cp, "Ville:"));
            $contact_cp = preg_replace("/(\r\n|\n|\r)/", " ", $contact_cp);
            $Element->setMetadata("address_postal_code", trim($contact_cp));

            $contact_town = substr($contents, strpos($contents, "Ville:") + 6);
            $contact_town = substr($contact_town, 0, strpos($contact_town, "Pays:"));
            $contact_town = preg_replace("/(\r\n|\n|\r)/", " ", $contact_town);
            $Element->setMetadata("address_town", mb_strtoupper(trim($contact_town)));

            $contact_country = substr($contents, strpos($contents, "Pays:") + 5);
            $contact_country = preg_replace("/(\r\n|\n|\r)/", " ", $contact_country);
            $Element->setMetadata("address_country", trim($contact_country));

            /*echo "1".$contact_name;
            echo "1".$contact_firstname;
            echo "1".$contact_mail;
            echo "1".$contact_address;
            echo "1".$contact_cp;
            echo "1".$contact_town;
            echo "1".$contact_country;*/

            fclose($txtmail);

        }
    }

    //Configuration du sendmail php obligatoire pour utiliser cette fonction !
    function notifyContact($ContentPath)
    {
        $Batch = $_SESSION['capture']->Batch;
        $BatchId = $Batch->id;
        // /********************************************************************************
        // ** Loop on DOCUMENT Elements
        // ********************************************************************************/
        $OriginElements = $this->Batch->query($ContentPath);
        $l = $OriginElements->length;
        for ($i=0;$i<$l;$i++) {
            $Element = $OriginElements->item($i);

            //recuperation des element du formulaire contact
            $dest_mail = $Element->getMetadata("email");
            $subject = $Element->getMetadata("res_subject");

            //recuperation du res_id de la step sendToMaarch
            $res_id = $Element->getMetadata("resId");

            

            if (!preg_match("#^[a-z0-9._-]+@(hotmail|live|msn).[a-z]{2,4}$#", $dest_mail)) // On filtre les serveurs qui rencontrent des bogues.
            {
                $passage_ligne = "\r\n";
            }
            else
            {
                $passage_ligne = "\n";
            }

            //=====Déclaration des messages au format texte et au format HTML.
            $message_txt = "Vous avez saisi par voie électronique [NOM DE LA COLLECTIVITE], enregistré le ".date('d/m/Y').". Votre saisie concerne « ".$subject." ».Le présent accusé de réception N°".$res_id." (que nous vous invitons à conserver) atteste de la réception de votre saisine par l’administration compétente et vous informe des prochaines étapes de la procédure. Cela ne préjuge pas de la complétude ou de la recevabilité du dossier qui dépend notamment de l’examen à venir des pièces fournies ou à fournir. Si l’instruction de votre dossier nécessite des informations ou pièces complémentaires, [NOM DE LA COLLECTIVITE] vous contactera afin de les obtenir, dans un délai de production qui vous sera mentionné. Cordialement. Ceci est un message automatique, veuillez ne pas répondre ! Pour tout renseignement concernant votre dossier, vous pouvez nous contacter par téléphone [COORDONNEES TELEPHONIQUES] ou par messagerie électronique [ADRESSE ELECTRONIQUE].";
            $message_html = "<html><head></head><body style='background:white;color: #808080;font-size: 14px;text-align: justify;line-height: 25px;font-family: Trebuchet MS, Arial, Verdana, sans-serif;'>Bonjour,<br/><p>Vous avez saisi par voie électronique [NOM DE LA COLLECTIVITE], enregistré le <b>".date('d/m/Y')."</b>.<br/>Votre saisie concerne « <b>".$subject."</b> ».</p><p>Le présent accusé de réception <b>N°".$res_id."</b> (que nous vous invitons à conserver) atteste de la réception de votre saisine par l’administration compétente et vous informe des prochaines étapes de la procédure.<br/><i style='color:red;'>Cela ne préjuge pas de la complétude ou de la recevabilité du dossier qui dépend notamment de l’examen à venir des pièces fournies ou à fournir.</i><br/>Si l’instruction de votre dossier nécessite des informations ou pièces complémentaires, [NOM DE LA COLLECTIVITE] vous contactera afin de les obtenir, dans un délai de production qui vous sera mentionné.</p><br/>Cordialement.<br/><hr/><p style='text-align:center;'><i style='color:#A4A4A4;font-size:12px;'>Ceci est un message automatique, veuillez ne pas répondre !<br/>Pour tout renseignement concernant votre dossier, vous pouvez nous contacter par téléphone [COORDONNEES TELEPHONIQUES] ou par messagerie électronique [ADRESSE ELECTRONIQUE].</i></p></body></html>";
            //==========
             
            //=====Création de la boundary
            $boundary = "-----=".md5(rand());
            //==========
             
            //=====Définition du sujet.
            $sujet = "Accusé de dépôt : ".$subject;
            //=========
             
            //=====Création du header de l'e-mail.
            $header = "From: \"NoReply\"<NoReply@gmail.com>".$passage_ligne;
            $header.= "Reply-to: \"NoReply\" <NoReply@gmail.com>".$passage_ligne;
            $header.= "MIME-Version: 1.0".$passage_ligne;
            $header.= "Content-Type: multipart/alternative;".$passage_ligne." boundary=\"$boundary\"".$passage_ligne;
            //==========
             
            //=====Création du message.
            $message = $passage_ligne."--".$boundary.$passage_ligne;
            //=====Ajout du message au format texte.
            $message.= "Content-Type: text/plain; charset=\"ISO-8859-1\"".$passage_ligne;
            $message.= "Content-Transfer-Encoding: 8bit".$passage_ligne;
            $message.= $passage_ligne.$message_txt.$passage_ligne;
            //==========
            $message.= $passage_ligne."--".$boundary.$passage_ligne;
            //=====Ajout du message au format HTML
            $message.= "Content-Type: text/html; charset=\"ISO-8859-1\"".$passage_ligne;
            $message.= "Content-Transfer-Encoding: 8bit".$passage_ligne;
            $message.= $passage_ligne.$message_html.$passage_ligne;
            //==========
            $message.= $passage_ligne."--".$boundary."--".$passage_ligne;
            $message.= $passage_ligne."--".$boundary."--".$passage_ligne;
            //==========
             
            //=====Envoi de l'e-mail.
            mail($dest_mail,$sujet,$message,$header);

        }
    }
    
}
