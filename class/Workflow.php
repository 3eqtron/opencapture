<?php

class Workflow 
    extends DOMDocument 
{
    
    
    private $XPath;
    private $Step;
    private $WFName;
    private $logMode;
    
    public function __construct(
    ) {
        $version="1.0";
        $encoding="UTF-8";
        
        parent::__construct($version, $encoding);
        $this->registerNodeclass('DOMElement', 'WorkflowElement');
        $this->XPath = new DOMXPath($this);
    }
    
    public function init(
        $name,
        $id,
        $directory,
        $log
    ) {
        $_SESSION['logs'] = array();
        $Workflow = $this->createElement('Workflow');
        $this->appendChild($Workflow);
        
        $Workflow->setAttribute('name', $name);
        $this->WFName = $name;
        $_SESSION['logs']['WFName'] = $name;
        $Workflow->setAttribute('directory', $directory);
        $Workflow->setAttribute('id', $id);
        $Workflow->setAttribute('date', date('c'));
        
        $Workflow->setAttribute('logMode', $log['logMode']);
        $_SESSION['logs']['logMode'] = $log['logMode'];
        if($log['logMode'] == 'Maarch') {
            $Workflow->setAttribute('maarchLogParam', $log['maarchLogParam']);
            $_SESSION['logs']['maarchLogParam'] = $log['maarchLogParam'];
            $Workflow->setAttribute('maarchLoggerName', $log['maarchLoggerName']);
            $_SESSION['logs']['maarchLoggerName'] = $log['maarchLoggerName'];
        }

        $Workflow->setAttribute('log', $log['WorkflowLog']);
                
        $this->save();
    }
    
    function load(
        $id,
        $directory = null
    ) {
        $source = file_get_contents($directory . DIRECTORY_SEPARATOR . $id . '.xml');

        $source = str_replace("&nbsp;"," ",$source);
        $source = str_replace("&eacute;", "e",$source);
        $source = str_replace("&egrave;","e",$source);
        $source = str_replace("&ecirc;","e",$source);
        $source = str_replace("&agrave;","a",$source);
        $source = str_replace("&acirc;","a",$source);
        $source = str_replace("&icirc;","i",$source);
        $source = str_replace("&ocirc;","o",$source);
        $source = str_replace("&ucirc;","u",$source);
        $source = str_replace("&acute;","",$source);
        $source = str_replace("&deg;","o",$source);
        $source = str_replace("&rsquo;", "'",$source);

        unlink($directory . DIRECTORY_SEPARATOR . $id . '.xml');

        $fp = fopen($directory . DIRECTORY_SEPARATOR . $id . '.xml', 'a');
        fwrite($fp, $source);
        fclose($fp);

        parent::load($directory . DIRECTORY_SEPARATOR . $id . '.xml');
        $this->XPath = new DOMXPath($this);
    }
    
    function save(
        $filename=false
    ) {
        if(!$filename)
            $filename = $this->directory . DIRECTORY_SEPARATOR . $this->id . '.xml';
        parent::save($filename);
    }
    
    public function setStatus(
        $status,
        $message = false
    ) {
        $this->documentElement->setStatus(
            $status,
            $message
        );
    }
    
    public function logEvent(
        $message,
        $level = 0
    ) {
        $this->documentElement->logEvent(
            $message,
            $level
        );
    }
    
    function initStep(
        $StepName
    ) {
        $Step = $this->createElement('Step');
        $this->documentElement->appendChild($Step);
        
        $Step->setAttribute('name', $StepName);
        $Step->setAttribute('date', date('c'));
        
        $this->Step = $Step;
        
        return $Step;
    }
       
    function lastStep() {
        $Steps = $this->getElementsByTagName('Step');
        if($Steps->length)
            return $Steps->item(($Steps->length - 1));
    }
    
    function __get(
        $name
    ) {
        if($this->documentElement->hasAttribute($name))
            return $this->documentElement->getAttribute($name);
            
        if(isset($this->{$name}))
            return $this->{$name};
    }
        
}

class WorkflowElement
    extends DOMElement
{
    public function __get(
        $name
    ) {
        if($this->hasAttribute($name))
            return $this->getAttribute($name);

        return $this->{$name};   
    }
    
    public function setStatus(
        $status,
        $message = false
    ) {
        $this->setAttribute('status', $status);
        
        switch($status) {
        case MC_STATUS_NOT_STARTED:
            if(!$message) $message = 'Initializing...';
            $level = 0;
            break;
        case MC_STATUS_FAILED_ON_START:
            if(!$message) $message = 'Process failed on start!';
            $level = 3;
            break;
        case MC_STATUS_IN_PROGRESS:
            if(!$message) $message = 'Starting process...';
            $level = 0;
            break;
        case MC_STATUS_ERROR:
            if(!$message) $message = 'Process error! See previous messages for more information.';
            $level = 3;
            break;
        case MC_STATUS_STOPPED:
            if(!$message) $message = 'Process stopped.';
            $level = 1;
            break;
        case MC_STATUS_COMPLETED:
            if(!$message) $message = 'Process completed.';
            $level = 0;
            break;
        case MC_STATUS_RETRYING:
            if(!$message) $message = 'Retrying process...';
            $level = 0;
            break;
        }
        
        $this->logEvent(
            $message,
            $level
        );
        
    }
    
    public function logEvent(
        $message,
        $level = 0
    ) {
        switch($level) {
        case 0: 
            $lvlname = 'Notice';
            $maarchLevel = 'INFO';
            break;
        case 1: 
            $lvlname = 'Warning';
            $maarchLevel = 'WARNING';
            break;
        case 2: 
            $lvlname = 'Error';
            $maarchLevel = 'ERROR';
            break;
        case 3: 
            $lvlname = 'Fatal Error';
            $maarchLevel = 'FATAL';
            break;
        }
        
        $date = date('c');
        
        /* Log to xml */
        $Event = $this->ownerDocument->createElement('Event');
        $Event->setAttribute('date', $date);
        $Event->setAttribute('level', (int)$level);
        $Event->nodeValue = htmlentities($message);
        
        $this->appendChild($Event);
        
        $this->ownerDocument->save();
        //echo 'logMode:' . $_SESSION['logs']['logMode'] . PHP_EOL;
        //echo 'message:' . $message . PHP_EOL;
        if($_SESSION['logs']['logMode'] == 'Maarch') {
            if ($level > 0) {
                $result = 'KO';
            } else {
                $result = 'OK';
            }
            $this->logEventWithLog4PHP($message, $result, $maarchLevel);
        }
        
        /* Log to txt */
        if($log = $this->ownerDocument->documentElement->getAttribute('log')) {
            $logFile = fopen($log, 'a');
            if ( empty($logFile) ) {
                echo "ERROR : fopen($log) failed\n";
            } else if ( $logFile ) {
                $logTxt = $date . " [" . (int)$level . "] " . $message . PHP_EOL;
                fwrite($logFile, $logTxt);
                fclose($logFile);
            }
        }
    }

    /**
     * Write on the log file
     * @param  $eventInfo string text which is written in the log file
     */
    public function logEventWithLog4PHP($message, $result = 'OK', $level = 'INFO')
    {
        $remote_ip = '127.0.0.1';
        Logger::configure($_SESSION['logs']['maarchLogParam']);
        $logger = Logger::getLogger($_SESSION['logs']['maarchLoggerName']);
        $searchPatterns = array(
            '%ACCESS_METHOD%',
            '%RESULT%',
            '%CODE_METIER%',
            '%HOW%',
            '%WHAT%',
            '%REMOTE_IP%'
        );
        $replacePatterns = array(
            'Script',
            $result,
            'MaarchCapture' . '_' . $_SESSION['logs']['WFName'],
            'ADD',
            $message,
            $remote_ip
        );
        $logLine = str_replace(
            $searchPatterns, 
            $replacePatterns, 
            "[%ACCESS_METHOD%][%RESULT%][%CODE_METIER%][%HOW%][%WHAT%][%REMOTE_IP%]"
        );
        $this->writeLog4php($logger, $logLine, $level);
    }

    /**
     *
     * write a log entry with a specific log level
     * @param object $logger Log4php logger
     * @param string $logLine Line we want to trace
     * @param enum $level Log level
     */
    function writeLog4php($logger, $logLine, $level)
    {
        $logLine = $this->wd_remove_accents($logLine);
        switch ($level) {
            case 'DEBUG':
                $logger->debug($logLine);
                break;
            case 'INFO':
                $logger->info($logLine);
                break;
            case 'WARN':
                $logger->warn($logLine);
                break;
            case 'ERROR':
                $logger->error($logLine);
                break;
            case 'FATAL':
                $logger->fatal($logLine);
                break;
        }
    }

    /**
     * Delete accents
     *
     * @param String $str
     * @param String $charset
     */
    function wd_remove_accents($str, $charset ='utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES, "utf-8");
        $str = preg_replace(
            '#\&([A-za-z])(?:uml|circ|tilde|acute|grave|cedil|ring)\;#',
            '\1',
            $str
        );
        $str = preg_replace('#\&([A-za-z]{2})(?:lig)\;#', '\1', $str);
        $str = preg_replace('#\&[^;]+\;#','', $str);
        return $str;
    }
    
    public function __toString()
    {
        return @$this->ownerDocument->saveXML($this);
    }
    
}  
