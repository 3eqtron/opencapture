<?php

class MaarchWSClient
    extends DOMXPath
{
    private $Batch;
    private $WSDL;
    private $SoapClient;
    private $log = false;
    
    function __construct()
    {
        $this->Batch = $_SESSION['capture']->Batch;
        //$this->Workflow = $_SESSION['capture']->Workflow;
        
        $Config = new DOMDocument();
        $Config->load(
            __DIR__ . DIRECTORY_SEPARATOR . "MaarchWSClient.xml"
        );
        parent::__construct($Config);
        
    }
       
    function checkFault(
        $step,
        $result=false
    ) {
        if (
            version_compare(PHP_VERSION, '7.0.0') >= 0 && 
            $result->faultstring <> ''
        ) {
            $dmpfile = $this->Batch->directory . DIRECTORY_SEPARATOR . "MaarchWSClient__SOAPFault.log";
            $f = fopen($dmpfile, "w");
            fwrite($f, print_r($result,true));
            fclose($f);
            $_SESSION['capture']->sendError("SOAP fault occured on $step : SOAP Fault: (faultcode: {$result->faultcode}, faultstring: {$result->faultstring})");
        } else {
            if($this->WSDL->fault) {
                $dmpfile = $this->Batch->directory . DIRECTORY_SEPARATOR . "MaarchWSClient__SOAPFault.log";
                $f = fopen($dmpfile, "w");
                fwrite($f, print_r($this->WSDL->fault,true));
                fclose($f);
                $_SESSION['capture']->sendError("SOAP fault occured on $step : " . $this->WSDL->fault->message);
            }
        }
    }
    
    function getSoapClient(
        $WSDLName
    ) {
        $WSDLConfig =
            $this->query(
                '//WSDL[@name="'.$WSDLName.'"]'
            )->item(0);
        if(!$WSDLConfig)
            die("Undefined WSDL $WSDLName !");
        
        $uri = $WSDLConfig->getAttribute('uri');
        
        if($WSDLConfig->hasAttribute('cacheUse'))
            $cacheUse = $WSDLConfig->getAttribute('cacheUse');
        else 
            $cacheUse = WSDL_CACHE_USE;
            
        if($WSDLConfig->hasAttribute('cacheMaxAge'))
            $cacheMaxAge = $WSDLConfig->getAttribute('cacheMaxAge');
        else 
            $cacheMaxAge = WSDL_CACHE_MAX_AGE;
            
        $SSL = $WSDLConfig->getAttribute('SSL');
            
        $proxyArgs =
            $this->query(
                './proxy/*',
                $WSDLConfig
            );
        $l = $proxyArgs->length;
        $proxy = array();
        for($i=0; $i<$l; $i++) {
            $proxyArg = $proxyArgs->item($i);
            $proxyArgName = $proxyArg->nodeName;
            $proxyArgValue = $proxyArg->nodeValue;
            $proxy[$proxyArgName] = (string)$proxyArgValue;
        }

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
            $this->SoapClient = new SoapClient($uri, $proxy);

        } else {
            require_once('SOAP/Client.php');
            $this->WSDL = 
                new SOAP_WSDL(
                    $uri, 
                    $proxy,
                    false
                );
            
            $this->checkFault('new SOAP_WSDL');

            $this->SoapClient = $this->WSDL->getProxy();
            
            $this->checkFault('SOAP_WSDL::getProxy');
            
            if($SSL == 'true') {
                $this->SoapClient->setOpt('curl', CURLOPT_SSL_VERIFYPEER, 0);
                $this->SoapClient->setOpt('curl', CURLOPT_SSL_VERIFYHOST, 0);
                $this->SoapClient->setOpt('curl', CURLOPT_TIMEOUT, 30);
                $this->checkFault('SOAP_PROXY::setOpt');  
            }
        }        
    }
    
    function getProcess(
        $ProcessName
    ) {
        $Process = 
            $this->query(
                '//process[@name="'.$ProcessName.'"]'
            )->item(0);
            
        if(!$Process)
            die("Undefined process $ProcessName !");
            
        return $Process;
    }
    
    function processBatch(
        $WSDLName,
        $ProcessName,
        $log = false
    ) {
        
        $this->log = $log;
                
        $this->getSoapClient($WSDLName);
        
        $Process = $this->getProcess($ProcessName);
        
        $this->processInstructions(
            $this->Batch->documentElement,
            $Process
        );
    }
    
    function processInstructions(
        $Element,
        $parentInstruction
    ) {
        $instructions = 
            $this->query(
                './*',
                $parentInstruction
            );
        $l = $instructions->length;
        if($this->log) $_SESSION['capture']->logEvent("Process $l instructions on " . $Element->nodeName . " " . $Element->id);
        
        for($i=0; $i<$l; $i++) {
            $instruction = $instructions->item($i);
            switch($instruction->nodeName) {
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
    
    function processLoop(
        $parentElement,
        $loop
    ) {
        $xpath = $loop->getAttribute('xpath');
        $Elements = 
            $this->Batch->query(
                $xpath,
                $parentElement
            );
        $l = $Elements->length;
        for($i=0; $i<$l; $i++) {
            $Element = $Elements->item($i);
            $this->processInstructions(
                $Element,
                $loop
            );
        }    
    }
    
    function processCall(
        $Element,
        $service
    ) {
        $serviceName = $service->getAttribute('name');
        
        if($this->log) $_SESSION['capture']->logEvent("Process Call of service '" . $serviceName . "' for " . $Element->nodeName . " " . $Element->id);
        
        $args = $this->parseArguments($service, $Element);
        
        $SoapReturn = $this->callService($serviceName, $args);
        
        $result = $this->processReturn($Element, $service, $SoapReturn);
    }
    
    function callService(
        $serviceName,
        $args
    ) {
        if($this->log) $_SESSION['capture']->logEvent("Call service '" . $serviceName . "'...");
        
        try {
            # Call service
            $SoapReturn = 
                call_user_func_array(
                    array(
                        $this->SoapClient,
                        $serviceName
                    ),
                    $args
                );
            
            $this->checkFault("SOAP_PROXY::$serviceName", $SoapReturn);
            
        } catch (SoapFault $fault) {
            $_SESSION['capture']->logEvent($fault, 2);
        }
        if(!$SoapReturn)
            $_SESSION['capture']->sendError("No return from web service!");
        
        return $SoapReturn;
    }    
            
    function parseArguments(
        $service,
        $Element
    ) {
        $argValues = array();
        $args = 
            $this->query(
                './argument',
                $service
            );
        $l = $args->length;
        for($i=0; $i<$l; $i++) {
            $arg = $args->item($i);
            if(!$argName = $arg->getAttribute('name'))
                $argName = (string)$i;
                
            $argValue = 
                $this->parseArgument(
                    $arg,
                    $Element
                );
            
            $argHasValue = $argIsArray = false;
            if(count($argValues[$argName]) > 0)
                $argHasValue = true;
            if(isset($argValues[$argName][0]))
                $argIsArray = true;
                
            # First value of name: add as associative value
            if(!$argHasValue)
                $argValues[$argName] = $argValue;

            # Second value with name : move first in indexed array
            else if($argHasValue && !$argIsArray)
                $argValues[$argName] = array($argValues[$argName], $argValue);   

            # Not first value : append in indexed array
            else if($argHasValue && $argIsArray) {
                $argValues[$argName][] = $argValue;
            }
        }
        
        ##if($this->log) $_SESSION['capture']->logEvent("Arguments = " . htmlentities(print_r($argValues,true)));
        
        return $argValues;
    }
    
    function parseArgument(
        $arg,
        $Element
    ) {               
        // Arg has no value but special attribute
        //***********************************************************
        // xpath -> nodelist
        if($arg->hasAttribute('xpath'))
            return
                $this->Batch->query(
                    $arg->getAttribute('xpath'),
                    $Element
                );
        
        //xvalue -> xpath query first result node value
        if($arg->hasAttribute('xvalue')) {
            $result = 
                $this->Batch->query(
                    $arg->getAttribute('xvalue'),
                    $Element
                )->item(0);
            if($result)
                switch($result->nodeType) {
                case XML_ELEMENT_NODE:
                    return $result->nodeValue;
                case XML_ATTRIBUTE_NODE:
                    return $result->value;
                }   
        } 
        
        // metadata -> value of element metadata
        if($arg->hasAttribute('metadata'))
            return
                $Element->getMetadata(
                    $arg->getAttribute('metadata')
                );
        
        // Attribute -> value of element attribute
        if($arg->hasAttribute('attribute'))
            return
                $Element->getAttribute(
                    $arg->getAttribute('attribute')
                );
        
        // Node Name
        if($arg->hasAttribute('property')) {
            $propertyName = $arg->getAttribute('property');
            return $Element->$propertyName;
        }

        // Eval -> eval expression
        if($arg->hasAttribute('eval')) {
            eval('$value=' . html_entity_decode($arg->getAttribute('eval')) . ";");
            return $value;
        }
        
        // Arg has value
        //***********************************************************
        $argContents = $arg->childNodes;
        $l = $argContents->length;
        
        // Arg has scalar value
        if($l == 1 && (string)$arg->nodeValue)
            return (string)$arg->nodeValue;
        
        // Arg has multiple values -> array
        $argValue = array();
        for($i=0; $i<$l; $i++){
            $argContent = $argContents->item($i);
            if($argContent->nodeType != XML_ELEMENT_NODE) continue;
            $argContentName = $argContent->nodeName;
            $argContentValue = $this->parseArgument($argContent, $Element);
            
            $argContentHasValue = $argContentIsArray = false;
            if(count($argValue[$argContentName]) > 0)
                $argContentHasValue = true;
            if(isset($argValue[$argContentName][0]))
                $argContentIsArray = true;
                
            # First value of name: add as associative value
            if(!$argContentHasValue)
                $argValue[$argContentName] = $argContentValue;
            # Second value with name : move first in indexed array
            elseif($argContentHasValue && !$argContentIsArray)
                $argValue[$argContentName] = array($argValue[$argContentName], $argContentValue);
 
            # Not first value : append in indexed array
            elseif($argContentHasValue && $argContentIsArray)
                $argValue[$argContentName][] = $argContentValue;
        }
        return $argValue;
    
    }
    
    function processReturn(
        $Element,
        $service,
        $SoapReturn
    ) {
        # Root return
        $return = $this->query('./return', $service)->item(0);
        if(!$return)
            return true;
            
        $this->processReturnValue(
            $Element,
            $return,
            $SoapReturn
        );
        
        return true;
    }
    
    function processReturnValue(
        $Element,
        $return,
        $SoapReturn
    ) {
        // Return has metadata name, add metadata
        if($return->hasAttribute('metadata'))
            return $Element->setMetadata($return->getAttribute('metadata'), $SoapReturn);

        // Return has attribute
        if($return->hasAttribute('attribute'))
            return $Element->setAttribute($return->getAttribute('attribute'), $SoapReturn);
            
        // Return has children
        //***********************************************************
        $returnContents = $this->query("./*", $return);
        $l = $returnContents->length;
        
        for($i=0; $i<$l; $i++){
            $returnContent = $returnContents->item($i);
            
            $returnContentName = $returnContent->nodeName;
            
            if(!isset($SoapReturn->$returnContentName)) {
                $dmpfile = $this->Batch->directory . DIRECTORY_SEPARATOR . $Element->id . "__MaarchWSClient__" . $serviceName . "__return.log";
                $f = fopen($dmpfile, "w");
                fwrite($f, print_r($SoapReturn,true));
                fclose($f);
                
                $_SESSION['capture']->sendError(
                    "Bad SOAP Response format: return $returnContentName is not set. Return dump output generated in file '$dmpfile'."
                );
            }   
            
            $returnContentValue = $SoapReturn->$returnContentName;
            
            $_SESSION['capture']->logEvent(
                "return value from web service " . $returnContentName . ' : ' . $returnContentValue
            );
            
            $this->processReturnValue($Element, $returnContent, $returnContentValue);
        }

    }
}
