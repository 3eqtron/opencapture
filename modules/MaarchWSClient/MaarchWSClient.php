<?php

$path = __DIR__;
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require_once 'Maarch/Loader.php';

class MaarchWSClient extends DOMXPath
{
    private $Batch;
    private $WS;
    private $WSType;
    private $SoapClient;
    private $catchError = "false";
    private $uri;
    
    public function __construct()
    {
        $this->Batch = $_SESSION['capture']->Batch;
        $Config = new DOMDocument();
        $Config->load(__DIR__ . "/MaarchWSClient.xml");
        parent::__construct($Config);
    }

    public function processBatch(
        $WSName,
        $ProcessName,
        $CatchError = "false"
    ) {
        $this->CatchError = $CatchError;

        $this->getWsClient($WSName);
        
        $Process = $this->getProcess($ProcessName);
        
        $this->processInstructions(
            $this->Batch->documentElement,
            $Process
        );
    }

    public function getWsClient(
        $WSName
    ) {
        $WSConfig =
            $this->query(
                '//WS[@name="'.$WSName.'"]'
            )->item(0);
        if (!$WSConfig) {
            die("Undefined WS $WSName !");
        }
        
        $this->type = $WSConfig->getAttribute('type');
        
        echo 'WS TYPE:' . $this->type . PHP_EOL;

        $this->uri = $WSConfig->getAttribute('uri');
        
        if ($WSConfig->hasAttribute('cacheUse')) {
            $cacheUse = $WSConfig->getAttribute('cacheUse');
        } else {
            $cacheUse = WSDL_CACHE_USE;
        }

        if ($WSConfig->hasAttribute('cacheMaxAge')) {
            $cacheMaxAge = $WSConfig->getAttribute('cacheMaxAge');
        } else {
            $cacheMaxAge = WSDL_CACHE_MAX_AGE;
        }
        $SSL = $WSConfig->getAttribute('SSL');
            
        $proxyArgs =
            $this->query(
                './proxy/*',
                $WSConfig
            );
        $l = $proxyArgs->length;
        $proxy = array();
        for ($i=0; $i<$l; $i++) {
            $proxyArg = $proxyArgs->item($i);
            $proxyArgName = $proxyArg->nodeName;
            $proxyArgValue = $proxyArg->nodeValue;
            $proxy[$proxyArgName] = (string)$proxyArgValue;
        }

        if ($this->type == 'SOAP' || $this->type == '') {
            if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
                $opts = array(
                    'http'=>array(
                        'user_agent' => 'PHPSoapClient'
                        )
                    );
                $context = stream_context_create($opts);

                if (!empty($proxy['user'])) {
                    $proxy['login'] = $proxy['user'];
                }
                if (!empty($proxy['pass'])) {
                    $proxy['password'] = $proxy['pass'];
                }
                $proxy['exceptions'] = false;
                $proxy['trace'] = true;
                $this->SoapClient = new SoapClient($this->uri, $proxy);
            } else {
                require_once('SOAP/Client.php');
                $this->WSDL =
                    new SOAP_WSDL(
                        $this->uri,
                        $proxy,
                        false
                    );
                
                $this->checkFault('new SOAP_WSDL');

                $this->SoapClient = $this->WSDL->getProxy();
                
                $this->checkFault('SOAP_WSDL::getProxy');
                
                if ($SSL == 'true') {
                    $this->SoapClient->setOpt('curl', CURLOPT_SSL_VERIFYPEER, 0);
                    $this->SoapClient->setOpt('curl', CURLOPT_SSL_VERIFYHOST, 0);
                    $this->SoapClient->setOpt('curl', CURLOPT_TIMEOUT, 30);
                    $this->checkFault('SOAP_PROXY::setOpt');
                }
            }
        } elseif ($this->type == 'REST') {
            //nothing to do here for REST CALL
        } else {
            die("WS type:. " . $WSName . " not managed !");
        }
    }
    
    public function getProcess(
        $ProcessName
    ) {
        $Process =
            $this->query(
                '//process[@name="'.$ProcessName.'"]'
            )->item(0);
            
        if (!$Process) {
            die("Undefined process $ProcessName !");
        }
            
        return $Process;
    }
    
    public function processInstructions(
        $Element,
        $parentInstruction
    ) {
        $instructions =
            $this->query(
                './*',
                $parentInstruction
            );
        $l = $instructions->length;
        if ($this->log) {
            $_SESSION['capture']->logEvent("Process $l instructions on "
                . $Element->nodeName . " " . $Element->id);
        }
        
        for ($i=0; $i<$l; $i++) {
            $instruction = $instructions->item($i);
            switch ($instruction->nodeName) {
                case 'loop':
                    $this->processLoop(
                        $Element,
                        $instruction
                    );
                    break;
                case 'call':
                    $this->processCall(
                        $Element,
                        $instruction
                    );
                    break;
            }
        }
    }
    
    public function processLoop(
        $parentElement,
        $loop
    ) {
        $xpath = $loop->getAttribute('xpath');
        $Elements = $this->Batch->query(
            $xpath,
            $parentElement
        );
        $l = $Elements->length;
        for ($i=0; $i<$l; $i++) {
            $Element = $Elements->item($i);
            $this->processInstructions(
                $Element,
                $loop
            );
        }
    }
    
    public function processCall(
        $Element,
        $service
    ) {
        $serviceName = $service->getAttribute('name');
        $serviceMethod = $service->getAttribute('method');
        
        if ($this->log) {
            $_SESSION['capture']->logEvent("Process Call of service '"
                . $serviceName . "' for " . $Element->nodeName . " " . $Element->id);
        }
        
        $args = $this->parseArguments($service, $Element);
        
        $WSReturn = $this->callService($serviceName, $args, $serviceMethod);
        
        $result = $this->processReturn($Element, $service, $WSReturn);
    }
    
    public function callService(
        $serviceName,
        $args,
        $serviceMethod = NULL
    ) {
        if ($this->log) {
            $_SESSION['capture']->logEvent("Call service '" . $serviceName . "'...");
        }


        if ($this->type == 'SOAP' || $this->type == '') {
            try {
                // Call service
                $WSReturn = call_user_func_array(
                    array(
                        $this->SoapClient,
                        $serviceName
                    ),
                    $args
                );
                
                $this->checkFault("SOAP_PROXY::$serviceName", $WSReturn);
            } catch (SoapFault $fault) {
                $_SESSION['capture']->logEvent($fault, 2);
            }
            if (!$WSReturn) {
                if ($this->CatchError == "false") {
                    $_SESSION['capture']->sendError("ERROR No return from web service!");
                } else {
                    $_SESSION['capture']->logEvent("ERROR No return from web service!");
                }
            }

        } else {
            try {
                $uriCalled = $this->uri . $serviceName;
                $httpRequest = new Maarch\Http\Message\Request($uriCalled);
                $httpRequest->withMethod($serviceMethod);
                //var_dump($httpRequest);
                $client = new Maarch\Http\Transport\StreamClient();
                $client->sendRequest($httpRequest);
                $httpResponse = $client->receiveResponse();
                $WSReturn = json_decode($httpResponse->getBody());
                $WSReturn->returnCode = 0;
                $WSReturn->error = '';
                var_dump($WSReturn);
            } catch (Exception $fault) {
                $_SESSION['capture']->logEvent($fault, 2);
            }
            if (!$WSReturn) {
                $WSReturn->returnCode = 1;
                $WSReturn->error = 'ERROR WITH REST WS !';
                if ($this->CatchError == "false") {
                    $_SESSION['capture']->sendError("ERROR No return from web service!");
                } else {
                    $_SESSION['capture']->logEvent("ERROR No return from web service!");
                }
            }
            
        }
        
        return $WSReturn;
    }
            
    public function parseArguments(
        $service,
        $Element
    ) {
        $argValues = array();
        $args = $this->query(
            './argument',
            $service
        );
        $l = $args->length;
        for ($i=0; $i<$l; $i++) {
            $arg = $args->item($i);
            if (!$argName = $arg->getAttribute('name')) {
                $argName = (string)$i;
            }
                
            $argValue = $this->parseArgument(
                $arg,
                $Element
            );
            
            $argHasValue = $argIsArray = false;
            if (count($argValues[$argName]) > 0) {
                $argHasValue = true;
            }
            if (isset($argValues[$argName][0])) {
                $argIsArray = true;
            }
                
            if (!$argHasValue) {
                // First value of name: add as associative value
                $argValues[$argName] = $argValue;
            } elseif ($argHasValue && !$argIsArray) {
                // Second value with name : move first in indexed array
                $argValues[$argName] = array($argValues[$argName], $argValue);
            } elseif ($argHasValue && $argIsArray) {
                // Not first value : append in indexed array
                $argValues[$argName][] = $argValue;
            }
        }
        
        return $argValues;
    }
    
    public function parseArgument(
        $arg,
        $Element
    ) {
        // Arg has no value but special attribute
        //***********************************************************
        // xpath -> nodelist
        if ($arg->hasAttribute('xpath')) {
            return $this->Batch->query(
                $arg->getAttribute('xpath'),
                $Element
            );
        }
        
        //xvalue -> xpath query first result node value
        if ($arg->hasAttribute('xvalue')) {
            $result = $this->Batch->query(
                $arg->getAttribute('xvalue'),
                $Element
            )->item(0);
            if ($result) {
                switch ($result->nodeType) {
                    case XML_ELEMENT_NODE:
                        return $result->nodeValue;
                    case XML_ATTRIBUTE_NODE:
                        return $result->value;
                }
            }
        }

        // metadata -> value of element metadata
        if ($arg->hasAttribute('metadata')) {
            return $Element->getMetadata(
                $arg->getAttribute('metadata')
            );
        }
        
        // Attribute -> value of element attribute
        if ($arg->hasAttribute('attribute')) {
            return $Element->getAttribute(
                $arg->getAttribute('attribute')
            );
        }
        
        // Node Name
        if ($arg->hasAttribute('property')) {
            $propertyName = $arg->getAttribute('property');
            return $Element->$propertyName;
        }

        // Eval -> eval expression
        if ($arg->hasAttribute('eval')) {
            eval('$value=' . html_entity_decode($arg->getAttribute('eval')) . ";");
            return $value;
        }
        
        // Arg has value
        //***********************************************************
        $argContents = $arg->childNodes;
        $l = $argContents->length;
        
        // Arg has scalar value
        if ($l == 1 && (string)$arg->nodeValue) {
            return (string)$arg->nodeValue;
        }
        
        // Arg has multiple values -> array
        $argValue = array();
        for ($i=0; $i<$l; $i++) {
            $argContent = $argContents->item($i);
            if ($argContent->nodeType != XML_ELEMENT_NODE) {
                continue;
            }
            $argContentName = $argContent->nodeName;
            $argContentValue = $this->parseArgument($argContent, $Element);
            
            $argContentHasValue = $argContentIsArray = false;
            if (count($argValue[$argContentName]) > 0) {
                $argContentHasValue = true;
            }
            if (isset($argValue[$argContentName][0])) {
                $argContentIsArray = true;
            }
                
            if (!$argContentHasValue) {
                // First value of name: add as associative value
                $argValue[$argContentName] = $argContentValue;
            } elseif ($argContentHasValue && !$argContentIsArray) {
                // Second value with name : move first in indexed array
                $argValue[$argContentName] = array($argValue[$argContentName], $argContentValue);
            } elseif ($argContentHasValue && $argContentIsArray) {
                // Not first value : append in indexed array
                $argValue[$argContentName][] = $argContentValue;
            }
        }
        return $argValue;
    }
    
    public function processReturn(
        $Element,
        $service,
        $WSReturn
    ) {
        // Root return
        $return = $this->query('./return', $service)->item(0);
        if (!$return) {
            return true;
        }
            
        $this->processReturnValue(
            $Element,
            $return,
            $WSReturn,
            $service->getAttribute('name')
        );
        
        return true;
    }
    
    public function processReturnValue(
        $Element,
        $return,
        $WSReturn,
        $serviceName
    ) {
        // Return has metadata name, add metadata
        if ($return->hasAttribute('metadata')) {
            return $Element->setMetadata($return->getAttribute('metadata'), $WSReturn);
        }

        // Return has attribute
        if ($return->hasAttribute('attribute')) {
            return $Element->setAttribute($return->getAttribute('attribute'), $WSReturn);
        }
            
        // Return has children
        //***********************************************************
        $returnContents = $this->query("./*", $return);
        $l = $returnContents->length;
        
        for ($i=0; $i<$l; $i++) {
            $returnContent = $returnContents->item($i);
            
            $returnContentName = $returnContent->nodeName;
            
            if (!isset($WSReturn->$returnContentName)) {
                $dmpfile = $this->Batch->directory . "/" . $Element->id . "__MaarchWSClient__"
                    . str_replace(DIRECTORY_SEPARATOR, "#", $serviceName) . "__return.log";
                $f = fopen($dmpfile, "w");
                fwrite($f, print_r($WSReturn, true));
                fclose($f);
                if ($this->CatchError == "false") {
                    $_SESSION['capture']->sendError(
                        "ERROR Bad WS Response format: return "
                        . $returnContentName . " is not set. Return dump output generated in file '"
                        . $dmpfile . "'."
                    );
                } else {
                    $_SESSION['capture']->logEvent(
                        "ERROR Bad WS Response format: return "
                        . $returnContentName . " is not set. Return dump output generated in file '"
                        . $dmpfile . "'."
                    );
                }
            }
            
            $returnContentValue = $WSReturn->$returnContentName;
            
            $_SESSION['capture']->logEvent(
                "return value from web service " . $serviceName . " "
                . $returnContentName . ' : ' . $returnContentValue
            );
            
            $this->processReturnValue($Element, $returnContent, $returnContentValue, $serviceName);
        }
    }

    public function checkFault($step, $result = false)
    {
        if (version_compare(PHP_VERSION, '7.0.0') >= 0 &&
            $result->faultstring <> ''
        ) {
            $dmpfile = $this->Batch->directory . "/MaarchWSClient__SOAPFault.log";
            $f = fopen($dmpfile, "w");
            fwrite($f, print_r($result, true));
            fclose($f);
            if ($this->CatchError == "false") {
                $_SESSION['capture']->sendError(
                    "ERROR SOAP fault occured on " . $step . " : SOAP Fault: (faultcode: {"
                    . $result->faultcode . "}, faultstring: {$result->faultstring})"
                );
            } else {
                $_SESSION['capture']->logEvent(
                    "ERROR SOAP fault occured on " . $step . " : SOAP Fault: (faultcode: {"
                    . $result->faultcode . "}, faultstring: {$result->faultstring})"
                );
            }
        } else {
            if ($this->WSDL->fault) {
                $dmpfile = $this->Batch->directory . "/MaarchWSClient__SOAPFault.log";
                $f = fopen($dmpfile, "w");
                fwrite($f, print_r($this->WSDL->fault, true));
                fclose($f);
                if ($CatchError == "false") {
                    $_SESSION['capture']->sendError(
                        "ERROR SOAP fault occured on $step : "
                        . $this->WSDL->fault->message
                    );
                } else {
                    $_SESSION['capture']->logEvent(
                        "ERROR SOAP fault occured on $step : "
                        . $this->WSDL->fault->message
                    );
                }
            }
        }
    }
}
