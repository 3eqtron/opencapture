<?php

/******************************************************************************
**
**                                  MAARCH CAPTURE
** 
******************************************************************************/
echo 
    "********************************************************************************" . PHP_EOL .
    "**                                Maarch Capture                              **" . PHP_EOL .
    "**  (c) since 2008 Maarch SAS                                                 **" . PHP_EOL .
    "********************************************************************************" . PHP_EOL;

/******************************************************************************
**										INIT
******************************************************************************/
set_time_limit(1800);
ini_set('max_execution_time', 1800);
ini_set('memory_limit', -1);
ini_set('mbstring.substitute_character', 'none');
set_include_path(get_include_path() . PATH_SEPARATOR . getcwd());

//set_error_handler('capture_error_handler');
define('MC_STATUS_NOT_STARTED', 1); # NotStarted
define('MC_STATUS_FAILED_ON_START', 2); # FailedOnStart
define('MC_STATUS_IN_PROGRESS', 3); # InProgress
define('MC_STATUS_ERROR', 4); # ErrorOccurred
define('MC_STATUS_STOPPED', 5); # StoppedByUser
define('MC_STATUS_COMPLETED', 6); # Completed
define('MC_STATUS_RETRYING', 7); # FailedOnStartRetrying  

require_once "class/Workflow.php";
require_once "class/Batch.php";
require_once "class/Capture.php";
require_once "tools/log4php/Logger.php";

/******************************************************************************
**	DEFINE COMMANDS AND ARGS
******************************************************************************/
require_once "Maarch_CLITools/ArgsParser.php";
$ArgsParser = new ArgsParser();
$CommandArgsParser = new ArgsParser();

# Commands
#*****************************************************************************
# init (BatchName [, WorkflowName, [Args...]])
#   creates a new batch and initializes workflow with given workflow name or default workflow
$ArgsParser->add_command(
    "init",
    $aliases = array('i'),
    $CommandArgsParser,
    "Creates a new batch and initializes workflow with given workflow name or default workflow." . PHP_EOL
        . "     -BatchName : Name of batch configuration" . PHP_EOL
        . "     -WorkflowName : Name of workflow configuration to initialize" . PHP_EOL
);


# continue (BatchName, BatchId)
#   loads an existing batch and continues process from next not completed 
$ArgsParser->add_command(
    "continue",
    $aliases = array('c'),
    $CommandArgsParser,
    "Load an existing batch and continues workflow at given step name or next step." . PHP_EOL
        . "     -BatchName : Name of batch configuration" . PHP_EOL
        . "     -BatchId : Identifier of the batch" . PHP_EOL
);

# step (BatchName, BatchId, stepName)
#   loads an existing batch and processes only given stepname 
$ArgsParser->add_command(
    "step",
    $aliases = array('s'),
    $CommandArgsParser,
    "Load an existing batch and processes given step name." . PHP_EOL
        . "     -BatchName : Name of batch configuration" . PHP_EOL
        . "     -BatchId : Identifier of the batch" . PHP_EOL
        . "     -StepName : Nae of the step to process" . PHP_EOL
);

# Common arguments
#*****************************************************************************
# BatchName
$CommandArgsParser->add_arg(
    "BatchName",
    array(
        "short" => 'n',
        "long" => 'BatchName',
        "default" => null,
        "help" => "Batch configuration name",
        "mandatory" => true
    )
);

$CommandArgsParser->add_arg(
    "ConfigName",
    array(
        "short" => 'n',
        "long" => 'ConfigName',
        "default" => 'Capture',
        "help" => "Capture configuration name",
        "mandatory" => false
    )
);

/******************************************************************************
**	PARSE COMMAND
******************************************************************************/
/*
return  ['executable'] : name of this script
        ['positional'] : list of positional arguments
        ['command']
        ['command']['name'] : name of the command
        ['command']['opts']
        ['command']['opts']['executable']
        ['command']['opts']['positional']
        ['command']['opts']['xxxxxxxxxx'] :value of argument xxxxxxxxxx
*/
try {
    $instrArgs = $ArgsParser->parse_args($argv);
} catch (MissingArgumentError $e) {
    die($e->getMessage());
}
#echo "MaarchCapture instruction: " . print_r($instrArgs,true) . PHP_EOL;

#******************************************************************************
# CREATE OR LOAD BATCH
#******************************************************************************
$command = $instrArgs['command']['name'];
$BatchName = $instrArgs['command']['opts']['BatchName'];
$ConfigName = $instrArgs['command']['opts']['ConfigName'];

switch($command) {
case 'init':
    echo "Initialize new Capture process..." . PHP_EOL;
    # Add optional WorkflowName for creation
    $CommandArgsParser->add_arg(
        "WorkflowName",
        array(
            "short" => 'w',
            "long" => 'WorkflowName',
            "default" => null,
            "help" => "Workflow name",
            "mandatory" => false
        )
    );
    
    /******************************************************************************
    **	PARSE COMMAND + DEFAULT ARGS
    ******************************************************************************/
    try {
        $commandArgs = $ArgsParser->parse_args($argv);
    } catch (MissingArgumentError $e) {
        die($e->getMessage());
    }
    /******************************************************************************
    **	LOAD CAPTURE APPLICATION
    ******************************************************************************/
    echo "Instanciate new Capture processor..." . PHP_EOL;
    if (file_exists("config/".$ConfigName.".xml")) {
        $Capture = new Capture($ConfigName);
    } else {
        die($ConfigName." not found !". PHP_EOL);
    }   
    
    # Store in session for modules that will acces through requests
    $_SESSION['capture'] = $Capture;

    $BatchId = false;
    echo "Create new batch '$BatchName'..." . PHP_EOL;
    $BatchId = $Capture->createBatch($BatchName);
    if (!$BatchId) {
        die();
    }
    echo "Batch created with id '$BatchId'" . PHP_EOL;
    
    if (isset($commandArgs['command']['opts']['WorkflowName'])) {
        $WorkflowName = $commandArgs['command']['opts']['WorkflowName'];
    } else {
        $WorkflowName = $Capture->defaultWorkflow();
    }
    echo "Initialize workflow '$WorkflowName'..." . PHP_EOL;
    $WorkflowId = $Capture->initWorkflow($WorkflowName);
    echo "Workflow initialized with id '$WorkflowId'" . PHP_EOL;
    
    echo "Get first workflow step name..." . PHP_EOL;
    $StepName = $Capture->nextStep();
    echo "Next step name is '$StepName'" . PHP_EOL;
    
    break;

case 'continue':
    echo "Continue Capture process..." . PHP_EOL;
    # Add required BatchId for load/process
    $CommandArgsParser->add_arg(
        "BatchId",
        array(
            "short" => 'i',
            "long" => 'BatchId',
            "default" => null,
            "help" => "Batch identifier",
            "mandatory" => true
        )
    );
    
    /******************************************************************************
    **	PARSE COMMAND + DEFAULT ARGS
    ******************************************************************************/
    try {
        $commandArgs = $ArgsParser->parse_args($argv);
    } catch (MissingArgumentError $e) {
        die($e->getMessage());
    }

    /******************************************************************************
    **  LOAD CAPTURE APPLICATION
    ******************************************************************************/
    echo "Instanciate new Capture processor..." . PHP_EOL;
    if (file_exists("config/".$ConfigName.".xml")) {
        $Capture = new Capture($ConfigName);
    } else {
        die($ConfigName." not found !". PHP_EOL);
    }   
    
    # Store in session for modules that will acces through requests
    $_SESSION['capture'] = $Capture;
<<<<<<< MaarchCapture.php
    
=======
>>>>>>> MaarchCapture.php
    
    #echo "MaarchCapture continue: " . print_r($commandArgs,true) . PHP_EOL;
    
    $BatchId = $commandArgs['command']['opts']['BatchId'];
    
    echo "Load batch '$BatchName' with id '$BatchId'..." . PHP_EOL;
    $Batch = $Capture->loadBatch($BatchName, $BatchId);
    echo "Batch loaded" . PHP_EOL;
    
    echo "Load workflow..." . PHP_EOL;
    $Workflow = $Capture->loadWorkflow();
    echo "Workflow '".$Workflow->name."' loaded" . PHP_EOL;
    
    if($Workflow->status == MC_STATUS_COMPLETED)
        $Capture->endWorkflow();
    
    echo "Get workflow next step name..." . PHP_EOL;
    $StepName = $Capture->nextStep();
    
    if(!$StepName)
        $Capture->endWorkflow();
    
    echo "Next step name is '$StepName'" . PHP_EOL;
    
    break;

case 'step':
    # Add required BatchId for load/process
    $CommandArgsParser->add_arg(
        "BatchId",
        array(
            "short" => 'i',
            "long" => 'BatchId',
            "default" => null,
            "help" => "Batch identifier",
            "mandatory" => true
        )
    );
    
    # Add optional StepName
    $CommandArgsParser->add_arg(
        "StepName",
        array(
            "short" => 's',
            "long" => 'StepName',
            "default" => null,
            "help" => "Workflow Step name",
            "mandatory" => true
        )
    );
    
    /******************************************************************************
    **	PARSE COMMAND + DEFAULT ARGS
    ******************************************************************************/
    try {
        $commandArgs = $ArgsParser->parse_args($argv);
    } catch (MissingArgumentError $e) {
        die($e->getMessage());
    }
    
    $BatchId = $commandArgs['command']['opts']['BatchId'];
    $StepName = $commandArgs['command']['opts']['StepName'];

    $Capture->loadBatch($BatchName, $BatchId);
    $Capture->loadWorkflow();
    $Capture->loadStepConfig($StepName);
    break;    
    
default:
    die(
        "MaarchCapture syntax : " . PHP_EOL
        ."  init " . PHP_EOL
        ."    -BatchName [name of a batch configuration)" . PHP_EOL
        ."    -WorkflowName [name of a batch configuration]"
        . PHP_EOL
        ."  continue " . PHP_EOL
        ."    -BatchName [name of a batch configuration]" . PHP_EOL
        ."    -BatchId [identifier of a batch configuration]" . PHP_EOL
        . PHP_EOL
        ."  step " . PHP_EOL
        ."    -BatchName [name of a batch configuration]" . PHP_EOL
        ."    -BatchId [identifier of a batch configuration]" . PHP_EOL
        ."    -StepName [name of a step in workflow]" . PHP_EOL
    );
}

$inputNames = $Capture->getStepInputs($StepName);
echo "MaarchCapture step inputs: " . print_r($inputNames, true) . PHP_EOL;
foreach ($inputNames as $i => $inputName) {
    # Add optional Step Input
    $CommandArgsParser->add_arg(
        $inputName,
        array(
            "short" => "A" . $i,
            "long" => $inputName,
            "default" => null,
            "help" => "Workflow step input " . $inputName,
            "mandatory" => false
        )
    );
}
/******************************************************************************
**	PARSE COMMAND + DEFAULT ARGS + STEP ARGS
******************************************************************************/
try {
    $stepArgs = $ArgsParser->parse_args($argv);
} catch (MissingArgumentError $e) {
    die($e->getMessage());
}

echo "MaarchCapture step: " . print_r($stepArgs, true) . PHP_EOL;

$inputArgs = array();
foreach ($inputNames as $i => $inputName) {
    if (isset($stepArgs['command']['opts'][$inputName])) {
        $inputArgs[$inputName] = $stepArgs['command']['opts'][$inputName];
    }
}

$Result = $Capture->processWorkflow($inputArgs);

/******************************************************************************
**								ERROR HANDLER
******************************************************************************/
/*function capture_error_handler(
    $errno,
    $errstr,
    $errfile=false,
    $errline=false, 
    $errcontext=array()
) {
    $errorlvl = 0;
    switch ($errno) {
    case E_NOTICE:
    case E_USER_NOTICE:
        $errmsg = "Notice";
        $errorlvl = 0;
        break;
        
    case E_WARNING:
    case E_USER_WARNING:
        $errmsg = "Warning";
        $errorlvl = 1;
        break;
        
    case E_DEPRECATED:
    case E_USER_DEPRECATED:
        $errmsg = "Deprecated";
        $errorlvl = 1;
        break;
    
    case E_STRICT:
        $errmsg = "Strict";
        $errorlvl = 1;
        break;
    
    case E_ERROR:
    case E_USER_ERROR:
    default:
        $errmsg = "Error";
        $errorlvl = 2;
        break;
    }
    
    $errmsg .= " : " . $errstr;
    
    if($errfile)
        $errmsg .= ' in ' . $errfile;
    
    if($errline)
        $errmsg .= ' on line ' . $errline;
    
    if($errcontext) 
        $errmsg .= "\n" . print_r($errcontext,true);
    
    if($Capture = $GLOBALS['MaarchCapture']) {
        if($Batch = $Capture->Batch) {
            $Batch->logEvent(
                $errmsg,
                $errorlvl,
                'Maarch Capture Error Handler'
            );
            if($errorlvl > 1)
                $Batch->error();
        }
    }
    throw new Exception($errmsg);
}*/

?>