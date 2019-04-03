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
        //$this->Workflow = $_SESSION['capture']->Workflow;
        if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . "MaarchWSClient.xml")) {
            $_SESSION['MaarchWSClient'] = 'MaarchWSClient.xml';
            $Config = new DOMDocument();
            $Config->load(
                __DIR__ . DIRECTORY_SEPARATOR . "MaarchWSClient.xml"
            );
            parent::__construct($Config);
        }
    }

    public function processBatch(
        $WSName,
        $ProcessName,
        $CatchError = "false",
        $configFile = false
    ) {
        if ($configFile) {
            $_SESSION['MaarchWSClient'] = $configFile;
            $Config = new DOMDocument();
            $Config->load(
                __DIR__ . DIRECTORY_SEPARATOR . $configFile
            );
            parent::__construct($Config);
        }

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
        
        $this->uri = $WSConfig->getAttribute('uri');
        
        if ($WSConfig->hasAttribute('cacheUse')) {
            $cacheUse = $WSConfig->getAttribute('cacheUse');
        } else {
            if (defined('WSDL_CACHE_USE')) {
                $cacheUse = WSDL_CACHE_USE;
            }
        }

        if ($WSConfig->hasAttribute('cacheMaxAge')) {
            $cacheMaxAge = $WSConfig->getAttribute('cacheMaxAge');
        } else {
            if (defined('WSDL_CACHE_MAX_AGE')) {
                $cacheMaxAge = WSDL_CACHE_MAX_AGE;
            }
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
        if (isset($this->log) && $this->log) {
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
        
        if (isset($this->log) && $this->log) {
            $_SESSION['capture']->logEvent("Process Call of service '"
                . $serviceName . "' for " . $Element->nodeName . " " . $Element->id);
        }

        if ($this->type == 'SOAP' || $this->type == '') {
            $args = $this->parseSOAPArguments($service, $Element);
            $WSReturn = $this->callSOAPService($serviceName, $args);
            $result = $this->processSOAPReturn($Element, $service, $WSReturn);
        } else {
            $serviceMethod = $service->getAttribute('method');
            $args = $this->parseRESTArguments($service, $Element);
            $WSReturn = $this->callRESTService($serviceName, $args, $serviceMethod);
            $result = $this->processRESTReturn($Element, $service, $WSReturn);
        }
    }
    
    public function callSOAPService(
        $serviceName,
        $args
    ) {
        if ($this->log) {
            $_SESSION['capture']->logEvent("Call service '" . $serviceName . "'...");
        }
        
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
                $_SESSION['capture']->CatchError("ERROR No return from web service!");
            }
        }
        
        return $WSReturn;
    }

    protected function callRESTService(
        $serviceName,
        $args,
        $serviceMethod = "GET"
    ) {
        if (isset($this->log) && $this->log) {
            $_SESSION['capture']->logEvent("Call service '" . $serviceName . "'...");
        }

        try {
            // Replace URI template variables by template type parameters
            if (preg_match_all('#\{\w+\}#', $serviceName, $matches)) {
                foreach ($matches[0] as $templateVar) {
                    $templateVarName = substr($templateVar, 1, -1);
                    $templateValue = '';
                    if (isset($args['template'][$templateVarName])) {
                        $templateValue = $args['template'][$templateVarName];
                    }
                    $serviceName = str_replace($templateVar, $templateValue, $serviceName);
                }
            }
            $uriCalled = $this->uri . $serviceName;

            $httpRequest = new Maarch\Http\Message\Request($uriCalled);
            $httpRequest->withMethod($serviceMethod);

            // Compose query string
            if (!empty($args['query'])) {
                $queryParts = [];
                foreach ($args['query'] as $name => $value) {
                    if (is_array($value)) {
                        $glu = '&'.$name.'[]=';
                        $queryParts[] = $name.'[]='.implode($glu, $value);
                    } else {
                        $queryParts[] = $name.'='.urlencode($value);
                    }
                }

                $queryString = implode('&', $queryParts);
                $httpRequest->getUri()->withQuery($queryString);
            }
            
            // Compose body in json
            if (!empty($args['entity'])) {
                $httpRequest->withHeader('Content-Type', 'application/json');
                $httpRequest->withSerializedBody(json_encode($args['entity'], JSON_PRETTY_PRINT));
            }

            //LOG
            // file_put_contents($this->Batch->directory . "/httpRequest.txt", (string) $httpRequest);


            $client = new Maarch\Http\Transport\StreamClient();
            $client->sendRequest($httpRequest);
            $httpResponse = $client->receiveResponse();
            
            //LOG
            // file_put_contents($this->Batch->directory . "/httpResponse.txt", (string) $httpResponse);
            $returnCode = $httpResponse->statusCode;

            //echo $returnCode . PHP_EOL;
            //var_dump((string) $httpResponse->getBody());

            $WSReturn = json_decode((string) $httpResponse->getBody(), true);

            //var_dump($httpResponse);
            //var_dump($WSReturn);
        } catch (Exception $fault) {
            $_SESSION['capture']->logEvent($fault, 2);
        }
        if (!$WSReturn && $returnCode <> '200') {
            //var_dump($httpResponse);
            //$httpResponse->getBody()->rewind();
            $WSReturn = [];
            $WSReturn['returnCode'] = 1;
            $WSReturn['error'] = 'ERROR WITH REST WS !';
            $WSReturn['errorContent'] = (string) $httpResponse->getBody();
            if ($this->CatchError == "false") {
                $_SESSION['capture']->sendError("ERROR No return from web service!");
            } else {
                $_SESSION['capture']->CatchError("ERROR No return from web service!");
            }
        }

        return $WSReturn;
    }
            
    public function parseSOAPArguments(
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

            if (
                isset($argValue['value']) &&
                is_array($argValue['value']) &&
                empty($argValue['value'])
            ) {
                $argValue['value'] = '';
            }
            
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

    public function parseRESTArguments(
        $service,
        $Element
    ) {
        $argValues = [
            'template' => [],
            'query' => [],
            'header' => [],
            'entity' => [],
        ];
        $args = $this->query(
            './argument',
            $service
        );

        $usedArgNames = [];
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

            $argType = 'entity';
            if ($arg->hasAttribute('type')) {
                $argType = $arg->getAttribute('type');
            }

            if (
                isset($argValue['value']) &&
                is_array($argValue['value']) &&
                empty($argValue['value'])
            ) {
                $argValue['value'] = '';
            }

            if (!isset($argValues[$argType][$argName])) {
                $argValues[$argType][$argName] = $argValue;
            } else {
                if (is_array($argValues[$argType][$argName])) {
                    if (is_string(key($argValues[$argType][$argName]))) {
                        $argValues[$argType][$argName] = [$argValues[$argType][$argName], $argValue];
                    } else {
                        $argValues[$argType][$argName][] = $argValue;
                    }
                } else {
                    $argValues[$argType][$argName] = [$argValues[$argType][$argName], $argValue];
                }
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
            if (!empty($argValue[$argContentName]) && count($argValue[$argContentName]) > 0) {
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
    
    public function processSOAPReturn(
        $Element,
        $service,
        $WSReturn
    ) {
        // Root return
        $return = $this->query('./return', $service)->item(0);
        if (!$return) {
            return true;
        }
            
        $this->processSOAPReturnValue(
            $Element,
            $return,
            $WSReturn,
            $service->getAttribute('name')
        );
        
        return true;
    }
    
    public function processSOAPReturnValue(
        $Element,
        $return,
        $WSReturn,
        $serviceName
    ) {
        $WSReturnContent = $WSReturn;

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
                    . str_replace('/', "#", $serviceName) . "__return.log";
                $f = fopen($dmpfile, "w");
                fwrite($f, print_r($WSReturnContent, true));
                fclose($f);
                if ($this->CatchError == "false") {
                    $_SESSION['capture']->sendError(
                        "ERROR Bad WS Response format: return "
                        . $returnContentName . " is not set. Return dump output generated in file '"
                        . $dmpfile . "'."
                    );
                } else {
                    $_SESSION['capture']->CatchError(
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
            
            $this->processSOAPReturnValue($Element, $returnContent, $returnContentValue, $serviceName);
        }
    }

    public function processRESTReturn(
        $Element,
        $service,
        $entity
    ) {
        // Root return
        $return = $this->query('./return', $service)->item(0);
        if (!$return) {
            return true;
        }

        $this->processRESTReturnValue($Element, $return, $entity, $service->getAttribute('name'));
        
        return true;
    }

    public function processRESTReturnValue(
        $Element,
        $return,
        $entity,
        $serviceName
    ) {
        //var_dump($entity);
        // Return has metadata name, add metadata
        if ($return->hasAttribute('metadata')) {
            return $Element->setMetadata($return->getAttribute('metadata'), $entity);
        }

        // Return has attribute
        if ($return->hasAttribute('attribute')) {
            return $Element->setAttribute($return->getAttribute('attribute'), $entity);
        }
            
        // Return has children
        //***********************************************************
        $returnContents = $this->query("./*", $return);
        $l = $returnContents->length;

        
        for ($i=0; $i<$l; $i++) {
            $returnContent = $returnContents->item($i);
            //var_dump($entity);
            
            $returnContentName = $returnContent->nodeName;

            if (!isset($entity[$returnContentName])) {
                $dmpfile = $this->Batch->directory . "/" . $Element->id . "__MaarchWSClient__"
                    . str_replace(DIRECTORY_SEPARATOR, "#", $serviceName) . "__return.log";
                $f = fopen($dmpfile, "a");
                fwrite($f, print_r($entity, true));
                fclose($f);
                if ($this->CatchError == "false") {
                    $_SESSION['capture']->sendError(
                        "ERROR Bad WS Response format: return "
                        . $returnContentName . " is not set. Return dump output generated in file '"
                        . $dmpfile . "'."
                    );
                } else {
                    $_SESSION['capture']->CatchError(
                        "ERROR Bad WS Response format: return "
                        . $returnContentName . " is not set. Return dump output generated in file '"
                        . $dmpfile . "'."
                    );
                }
            }

            $returnContentValue = $entity[$returnContentName];
           
            $_SESSION['capture']->logEvent(
                "return value from web service " . $serviceName . " "
                . $returnContentName . ' : ' . $returnContentValue
            );
            
            $this->processRESTReturnValue($Element, $returnContent, $returnContentValue, $serviceName);
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
                $_SESSION['capture']->catchError(
                    "ERROR SOAP fault occured on " . $step . " : SOAP Fault: (faultcode: {"
                    . $result->faultcode . "}, faultstring: {$result->faultstring})"

                );
                // $_SESSION['capture']->logEvent(
                //     "ERROR SOAP fault occured on " . $step . " : SOAP Fault: (faultcode: {"
                //     . $result->faultcode . "}, faultstring: {$result->faultstring})"
                // );
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
                    $_SESSION['capture']->catchError(
                        "ERROR SOAP fault occured on $step : "
                        . $this->WSDL->fault->message
                    );
                }
            }
        }
    }
}
