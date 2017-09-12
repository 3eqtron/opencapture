<?php

class FileImport
    extends DOMXPath
{
    private $Batch;
    private $Target = "File";
    private $Action = "none";
    private $MoveDirectory;
    private $Recursive = false;
    private $CreateFolders = false;
    private $Extensions = array();
    private $NbMaxFoldersToImport = 0;
    private $DeleteSubFolders = false;
    private $SubFoldersToDel = array();
    
    function __construct()
    {
        $this->Batch = $_SESSION['capture']->Batch;
        $Config = new DOMDocument();
        $Config->load(
            __DIR__ . DIRECTORY_SEPARATOR . "FileImport.xml"
        );
        parent::__construct($Config);
    }
    
    function ImportFiles(
        $Directory,
        $Target='File',
        $Action='none',
        $MoveDirectory=false,
        $Recursive=false,
        $CreateFolders=false,
        $Extensions=array(),
        $NbMaxFoldersToImport = 0,
        $DeleteSubFolders = false
    ) { 
        $this->Target = $Target;
        $this->Action = $Action;
        $this->MoveDirectory = $MoveDirectory;
        $this->Recursive = $Recursive;
        $this->CreateFolders = $CreateFolders;
        $this->Extensions = $Extensions;
        $this->NbMaxFoldersToImport = $NbMaxFoldersToImport;
        $this->DeleteSubFolders = $DeleteSubFolders;
        
        $_SESSION['capture']->logEvent(
            "Scanning directory $Directory for file import..."
        );
        
        $result = 
            $this->ScanDirectory(
                $Directory,
                $this->Batch
            );
        
        if ($DeleteSubFolders) {
            $cptFoldersToDel = count($this->SubFoldersToDel);
            for ($i=0;$i<$cptFoldersToDel;$i++) {
                $_SESSION['capture']->logEvent(
                    "Delete directory " . $this->SubFoldersToDel[$i]
                );
                rmdir($this->SubFoldersToDel[$i]);
            }
        }
        
        return $result;
    }

    function ScanDirectory(
        $Directory,
        $Parent
    ) {
        /********************************************************************************
        ** Open Directory and import files to batch on new nodes
        ********************************************************************************/
        $dirhdl = opendir($Directory);
        
        if(!$dirhdl) {
            $_SESSION['capture']->logEvent(
                "Unable to open directory '$Directory' !", 2
            );
            trigger_error(
                "Unable to open directory '$Directory' !",
                E_USER_ERROR
            );
        }
        
        if($this->CreateFolders) {
            $_SESSION['capture']->logEvent(
                "Adding Folder with path '$Directory'"
            );
            $Container = 
                $Parent->addFolder(
                    $Directory
                );
        } else {
            $Container = $Parent;
        }
        
        $nbDir = 0;
        while($entry_name = readdir($dirhdl)) {
            $entry_path = $Directory  . DIRECTORY_SEPARATOR . $entry_name;
            
            /* not a file or sub folder
            ********************************************************************************/
            if($entry_name == '.' || $entry_name == '..') 
                continue;
            
            /* sub folder, process recursively if requested
            ********************************************************************************/
            if(is_dir($entry_path)) {
                if($this->Recursive) {
                    $nbDir++;
                    if ($nbDir <= $this->NbMaxFoldersToImport) {
                        array_push($this->SubFoldersToDel, $entry_path);
                        $this->ScanDirectory(
                            $entry_path,
                            $Container
                        );
                    } else {
                        break;
                    }
                }
                continue;
            }
            
            /* If extensions filtered, check extension
            ********************************************************************************/
            $entry_ext = substr(strrchr($entry_path , '.'), 1);
            if(count($this->Extensions) > 0) {
                if(!in_array($entry_ext, $this->Extensions)) {
                    $this->discard(
                        $entry_path,
                        $entry_name
                    );
                    continue;
                }
            }

            echo "control of " . $entry_path . PHP_EOL;
            $ofile = @fopen($entry_path, "r");
            if ($this->isCompleteFile($ofile)) {
                //continue
                fclose($ofile);
            } else {
                $_SESSION['capture']->logEvent(
                    "file '$entry_path' not complete, will be processed next batch"
                );
                continue;
            }
            
            $_SESSION['capture']->logEvent(
                "Adding ".$this->Target." with source '$entry_path'"
            );
            
            switch($this->Target) {
                case 'Document':
                    $Content = 
    					$Container->addDocument(
    						$entry_path
    					);
                    break;
                    
                case 'File':
                default:
                    $Content = 
    					$Container->addFile(
    						$entry_path
    					);
                    break;
            }
			if (!$Content) {
				//do nothing ! The resource will be processed next batch
			} else {
				$this->discard($entry_path, $entry_name);
			}
        }

    }

    function discard(
        $entry_path,
        $entry_name
    ) {
        /********************************************************************************
        ** Original File action
        ********************************************************************************/			
        switch ($this->Action) {
        case 'move':
            $_SESSION['capture']->logEvent(
                "Moving imported document to directory $MoveDirectory"
            );
            rename($entry_path, $this->MoveDirectory . DIRECTORY_SEPARATOR . $entry_name);
            break;
            
        case 'delete':
            $_SESSION['capture']->logEvent(
                "Deleting imported document"
            );
            unlink($entry_path);
            break;
        
        case 'none':
        default:
            // Nothing
        }

    }

    /**
     * Return true when the file is completed
     * @param  $file
     * @param  $delay
     * @param  $pointer position in the file
     */
    function isCompleteFile($file, $delay=200, $pointer=0)
    {
        if ($file == null) {
            return false;
        }
        fseek($file, $pointer);
        $currentLine = fgets($file);
        while (!feof($file)) {
            $currentLine = fgets($file);
        }
        $currentPos = ftell($file);
        //Wait $delay ms
        usleep($delay * 1000);
        //echo "is complete ? " . $file . PHP_EOL;
        if ($currentPos == $pointer) {
            return true;
        } else {
            return $this->isCompleteFile($file, $delay, $currentPos);
        }
    }

}
