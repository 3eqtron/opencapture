<?php

class Capture
    extends DOMXPath
{
    public $Batch;
    public $Workflow;
    public $Step;
    
    protected $BatchConfig;
    protected $WorkflowConfig;
    protected $StepConfig;
    
    public function Capture($ConfigName)
    {
        $Config = new DOMDocument();
        $Config->load("config/".$ConfigName.".xml");
        parent::__construct($Config);        
    }
    
    #**********************************************************************
    #
    #                   PUBLIC METHODS FOR BATCH CONTROL 
    # 
    #**********************************************************************
    
    #**********************************************************************
    # Create a new Batch based on a configuration name
    #**********************************************************************
    public function createBatch(
        $BatchName
    ) {
        //echo "Capture::createBatch($BatchName)" . PHP_EOL;
        
        $this->loadBatchConfig($BatchName);
        
        $BatchLock = (bool)$this->BatchConfig->getAttribute('lock');

        $BatchDirectory = (string)$this->BatchConfig->getAttribute('directory');
        
        $Batch = new Batch();
        
        if(!$BatchId = (string)$this->BatchConfig->getAttribute('id'))
            $BatchId = 'B' . date("U");
        else {
            $l = preg_match_all("/{[^{]*}/", $BatchId, $tags);
            for($i=0, $l=count($tags[0]); $i<$l; $i++) {
                $tag = $tags[0][$i];
                $instr = substr($tag, 1, strlen($tag) - 2);
                switch($instr) {
                case 'timestamp':
                    $val = date("U");
                    break;
                case 'date':
                    $val = date("Ymd-His");
                    break;
                case 'batchname':
                    $val = $BatchName;
                    break;
                case 'uniqid':
                    $val = uniqid();
                    break;
                case 'rand':
                    $val = mt_rand();
                    break;
                }
                $BatchId = str_replace($tag, $val, $BatchId);
            }
            $forbiddenChars = array_merge(array_map('chr', range(0,31)), array(" ", "<", ">", ":", '"', "/", "\\", "|", "?", "*"));
            $BatchId = 'B' . str_replace($forbiddenChars, "_", $BatchId);
        }

        if ($BatchLock) {
            $lockfile = $BatchDirectory . DIRECTORY_SEPARATOR . $BatchName . ".lck";
            if (is_file($lockfile)) {
                $lockBatchId = file_get_contents($lockfile);
                echo "Another instance of batch $BatchName already exists with id '$lockBatchId'. Check running process of previous batch status. To lauch another instance, delete file $lockfile." . PHP_EOL;

                return false;
            } else {
               file_put_contents($lockfile, $BatchId);
            }
        }
        
        $Batch->init($BatchName, $BatchId, $BatchDirectory);
        
        $Batch->save();
        
        $this->Batch = $Batch;

        return $BatchId;
    }
        
    #**********************************************************************
    # Load a batch with configuration name and id
    #**********************************************************************
    public function loadBatch(
        $BatchName,
        $BatchId
    ) {
        //echo "Capture::loadBatch($BatchName, $BatchId)" . PHP_EOL;
        
        $this->loadBatchConfig($BatchName);
        
        $BatchDirectory = (string)$this->BatchConfig->getAttribute('directory');
        
        $Batch = new Batch();
        
        $Batch->load($BatchId, $BatchDirectory);

        $this->Batch = $Batch;
        
        return $Batch;
    }
    
    
    #**********************************************************************
    #
    #                 PUBLIC METHODS FOR WORKFLOW CONTROL 
    # 
    #**********************************************************************
    #**********************************************************************
    # Initialize a workflow for a Batch
    #**********************************************************************
    public function initWorkflow(
        $WorkflowName
    ) {
        //echo "Capture::initWorkflow($WorkflowName)" . PHP_EOL;
             
        $this->loadWorkflowConfig($WorkflowName); 
        
        $WorkflowDirectory = (string)$this->Batch->directory;
        $WorkflowId = "W" . substr($this->Batch->id, 1);

        $logsParam = array();
        $logsParam['logMode'] = (string)$this->WorkflowConfig->getAttribute('logMode');
        if ($logsParam['logMode'] == 'Maarch') {
            $logsParam['maarchLogParam'] = (string)$this->WorkflowConfig->getAttribute('maarchLogParam');
            $logsParam['maarchLoggerName'] = (string)$this->WorkflowConfig->getAttribute('maarchLoggerName');
        } else {
            $logsParam['logMode'] = "default";
        }

        if(!$WorkflowLogDir = (string)$this->WorkflowConfig->getAttribute('log')) {
            $WorkflowLogDir = $WorkflowDirectory;
        }
        $WorkflowLog = $WorkflowLogDir . DIRECTORY_SEPARATOR . $WorkflowId . '.log';
        
        $logsParam['WorkflowLog'] = $WorkflowLog;

        $this->Batch->WorkflowId = $WorkflowId;
        
        $Workflow = new Workflow();

        $Workflow->init(
            $WorkflowName,
            $WorkflowId,
            $WorkflowDirectory,
            $logsParam
        );
            
        $Workflow->setStatus(
            MC_STATUS_NOT_STARTED,
            "Initializing workflow " . $WorkflowName
        );
        
        $this->Workflow = $Workflow;
        
        return $WorkflowId;
    }
    
    #**********************************************************************
    # Load a workflow with Batch
    #**********************************************************************
    public function loadWorkflow() 
    {
        //echo "Capture::loadWorkflow()" . PHP_EOL;
       
        $WorkflowDirectory = (string)$this->Batch->directory;
        $WorkflowId = "W" . substr($this->Batch->id, 1);
        
        $Workflow = new Workflow();
        
        $Workflow->load($WorkflowId, $WorkflowDirectory);
        
        $WorkflowName = $Workflow->documentElement->getAttribute('name');
        
        $this->Workflow = $Workflow;
        
        $this->loadWorkflowConfig($WorkflowName); 
        
        return $Workflow;
    }
    
    #**********************************************************************
    # Process a batch workflow
    #**********************************************************************    
	public function processWorkflow(
        $inputArgs = array()
    ) {        
        //echo "Capture::processWorkflow()" . PHP_EOL;
        
        $this->Workflow->setStatus(
             MC_STATUS_IN_PROGRESS,
            "(Re)Starting workflow " . $this->Workflow->name
        );
        
        while (
            $StepName = $this->nextStep()
        ) {
            $this->processStep($StepName, $inputArgs);
        }
        
        $this->endWorkflow();
	}
    
    public function endWorkflow()
    {
        $this->Workflow->setStatus(
            MC_STATUS_COMPLETED,
            "No more step to process, end of the workflow."
        );

        $BatchName = (string)$this->BatchConfig->getAttribute('name');
        $BatchLock = (bool)$this->BatchConfig->getAttribute('lock');
        $BatchDirectory = (string)$this->BatchConfig->getAttribute('directory');
        if ($BatchLock) {
            $lockfile = $BatchDirectory . DIRECTORY_SEPARATOR . $BatchName . ".lck";
            if (is_file($lockfile)) {
                unlink($lockfile);
            }
        }

        if($this->WorkflowConfig->getAttribute('debug') != 'true')
            $this->Batch->delete();
            
        die();
    }
    
        
    #**********************************************************************
    # Retrieve next workflow input names
    #********************************************************************** 
    public function getStepInputs($StepName) 
    {
        $inputNames = array();
        
        $this->LoadStepConfig($StepName);

        $inputConfigs = 
            $this->query(
                './input',
                $this->StepConfig
            );
        $l = $inputConfigs->length;
        for($i=0; $i<$l; $i++) {
            $inputConfig = $inputConfigs->item($i);
            
            if(!$inputName = $inputConfig->getAttribute('name'))
                $inputName = (string)$i;
            $inputNames[] = $inputName;
        }
        return $inputNames;
    }
    
    #**********************************************************************
    # Log event in current step or workflow
    #********************************************************************** 
    public function logEvent(
        $message,
        $level = 0
    ) {
        $this->Step->logEvent(
            $message,
            $level
        );
    }
    
    #**********************************************************************
    # Trigger error on step/workflow
    #********************************************************************** 
    public function sendError(
        $message
    ) {
        $this->Workflow->logEvent($message, 2);
        $this->Step->setStatus(
             MC_STATUS_ERROR,
            $message
        );
        $this->Workflow->setStatus(MC_STATUS_ERROR);
        
        //echo "Capture::sendError with message '$message'" . PHP_EOL;
        
        # TO DO : manage transactions on steps to rollback to previous valide state by not saving batch
        $this->Batch->save();
        die();
    }
    
    #**********************************************************************
    # Process a batch workflow step
    #********************************************************************** 
    public function processStep(
        $StepName,
        $inputArgs = array()
    ) {
        // Load step config
        $this->loadStepConfig($StepName);
        
        $ModuleName = $this->StepConfig->getAttribute("module");
        $FuncName   = $this->StepConfig->getAttribute('function');
        
        echo "Capture::processStep($StepName)" . PHP_EOL;
        
        $Step = $this->Workflow->initStep($StepName);
        $Step->setStatus(
             MC_STATUS_NOT_STARTED,
            "Initializing step " . $StepName
        );
        
        $this->Step = $Step;
        
        $this->loadModuleConfig($ModuleName);
            
        $ModuleSrc   = $this->ModuleConfig->getAttribute('src');
        $ModuleType  = $this->ModuleConfig->getAttribute('type');
        $ModuleInterface = $this->ModuleConfig->getAttribute('interface');
        
        $inputs = array();
        
        # How to send information about Capture object, Batch and workflow to called module
        
        switch($ModuleInterface) {
        case 'session_id':
            # For executables and .NET modules, acces through session_id 
            #   with a look in COOKIE sess_<id>
            $inputs['session_id'] = session_id();
            break;

        case 'none':
        default:
            # PHP modules can access in $_SESSION['capture']
        }
        
        // Input arguments
        $inputConfigs = 
            $this->query(
                './input',
                $this->StepConfig
            );
        $l = $inputConfigs->length;
        for($i=0; $i<$l; $i++) {
            $inputConfig = $inputConfigs->item($i);
            
            if(!$inputName = $inputConfig->getAttribute('name'))
                $inputName = (string)$i;
                
            if(isset($inputArgs[$inputName]))
                $inputValue = $inputArgs[$inputName];
            else
                $inputValue = $this->parseArgument($inputConfig);
                
            $inputs[$inputName] = $inputValue;
        }
        
        // Ouptut
        if($outputConfig = 
            $this->query(
                './output',
                $this->StepConfig
            )->item(0)
        ) {
            $outputName = $outputConfig->getAttribute('name');   
        } else {
            $outputName = false;
        }
        
        // Call to function - method - execution
        $starttime = microtime(true);
        
        $Step->setStatus(
             MC_STATUS_IN_PROGRESS,
            "Starting step " . $StepName
        );

        switch($ModuleType) {
        case 'class':
            require_once $ModuleSrc;
            $Module = new $ModuleName();
            $ReflectionClass = new ReflectionClass($Module);
            $ReflectionMethod = $ReflectionClass->getMethod($FuncName);
            $output = 
                $ReflectionMethod->invokeArgs(
                    $Module,
                    $inputs
                );
            break;
            
        case "script":
            require_once $ModuleSrc;
            $output = 
                call_user_func_array(
                    $FuncName,
                    $inputs
                );
            break;
            
        case "exec":
            //TO DO
            exec(
                escapeshellcmd($command),
                $output, 
                $StepResult
            );
            break;
        }
        $endtime = microtime(true);
        $processtime = number_format(($endtime - $starttime), 3);
        
        $Step->setStatus(
             MC_STATUS_COMPLETED,
            'Step completed in ' .$processtime . ' seconds'
        );
        
        # Save batch XML
        $this->Workflow->logEvent("Saving batch on disk...");        
        $starttime = microtime(true);
        $this->Batch->save();
        $endtime = microtime(true);
        $processtime = number_format(($endtime - $starttime), 3);
        $this->Workflow->logEvent('Batch saved in ' .$processtime . ' seconds');      
        
        return $output;
    }
    
    private function parseArgument(
        $arg
    ) {
        // Argument is an xpath query
        if($arg->hasAttribute('xpath'))
            return
                $this->Batch->query(
                    $arg->getAttribute('xpath')
                );
                
        // Argument is an xpath evaluation
        if($arg->hasAttribute('xvalue')) {
            $node =
                $this->Batch->query(
                    $arg->getAttribute('xvalue')
                )->item(0);
            if($node && $node->nodeType == XML_ATTRIBUTE_NODE)
                return (string)$node->value;
            if($node && $node->nodeType == XML_ELEMENT_NODE)
                return (string)$node->nodeValue;
        }
        
        // Arg has content
        $argContents = $arg->childNodes;
        $l = $argContents->length;
        
        // Arg has scalar value
        if($l == 1 && (string)$arg->nodeValue)
            return (string)$arg->nodeValue;
        
        // Arg has multiple values
        $argValue = array();
        for($i=0; $i<$l; $i++){
            $argContent = $argContents->item($i);
            if($argContent->nodeType != XML_ELEMENT_NODE) continue;
            if(!$argContentName = $argContent->getAttribute('name'))
                $argContentName = (string)$i;
            
            $argContentValue = $this->parseArgument($argContent);
            $argValue[$argContentName] = $argContentValue;
        }
        return $argValue;

    }
    
    public function defaultWorkflow()
    {
        $WorkflowConfig = 
            $this->query(
                ".//workflow",
                $this->BatchConfig
            )->item(0);
            
        if(!$WorkflowConfig) {
            trigger_error("No workflow defined for batch");
            $this->Workflow->logEvent("No workflow defined for batch", 1);
        }
        
        return $WorkflowConfig->getAttribute('name');
    }
    
    public function nextStep()
    {
        # Retrieve the last step initialized on workflow
        if($lastStep = $this->Workflow->lastStep()) {
            switch($lastStep->status) {
            case  MC_STATUS_COMPLETED:
                $StepXPath = './step[@name="'.$lastStep->name.'"]/following-sibling::step';
                break;
                
            case  MC_STATUS_NOT_STARTED:
            case  MC_STATUS_FAILED_ON_START:
            case  MC_STATUS_IN_PROGRESS:
            case  MC_STATUS_ERROR:
            case  MC_STATUS_STOPPED:
            case  MC_STATUS_RETRYING:
            default:
                $StepXPath = './step[@name="'.$lastStep->name.'"]';
            }   
        } else {
            $StepXPath = './step'; 
        }
        $nextStep = $this->query($StepXPath, $this->WorkflowConfig)->item(0);
        
        if($nextStep)
            return $nextStep->getAttribute('name');
        
        return false;
    }
    
    #**********************************************************************
    #
    #                PROTECTED METHODS FOR CONFIG CONTROL 
    # 
    #**********************************************************************
    
    #**********************************************************************
    # Load batch configuration
    #********************************************************************** 
    public function loadBatchConfig(
        $BatchName
    ) {
		//echo "Capture::loadBatchConfig($BatchName)" . PHP_EOL;
        
        $BatchConfig = 
            $this->query(
                "//batch[@name='".$BatchName."']"
            )->item(0);
            
		if(!$BatchConfig)
			die ("Unable to load batch definition $BatchName");
            
        $this->BatchConfig = $BatchConfig;
        
        return true;
    }
    
    #**********************************************************************
    # Load workflow configuration
    #********************************************************************** 
    public function loadWorkflowConfig(
        $WorkflowName
    ) {
        //echo "Capture::loadWorkflowConfig($WorkflowName)" . PHP_EOL;
        $WorkflowConfig = 
            $this->query(
                ".//workflow[@name='".$WorkflowName."']",
                $this->BatchConfig
            )->item(0);
        
        if(!$WorkflowConfig)
            die ("Undefined workflow '$WorkflowName'");
                
        $this->WorkflowConfig = $WorkflowConfig;
    }
       
    #**********************************************************************
    # Load workflow step configuration
    #**********************************************************************
    public function loadStepConfig(
        $stepName
    ) {
        //echo "Capture::loadStepConfig($stepName)" . PHP_EOL;
        
        $StepConfig = 
            $this->query(
                './step[@name="'.$stepName.'"]',
                $this->WorkflowConfig
            )->item(0);
        
        if(!$StepConfig) {
            trigger_error("Undefined workflow step '$StepName'");
            $this->Workflow->logEvent("Undefined workflow step '$StepName'", 1);
        }
            
        $this->StepConfig = $StepConfig;
        
        return true;
    }
        
    #**********************************************************************
    # Load module configuration
    #**********************************************************************
	protected function loadModuleConfig(
        $ModuleName
    ) {
        $ModuleConfig = 
            $this->query(
                '//module[@name="'.$ModuleName.'"]'
            )->item(0);
        if(!$ModuleConfig)
            die ("Unable to load module configuration with name '$ModuleName'");
        
        $this->ModuleConfig = $ModuleConfig;
        
        return true;
    }
    
}
