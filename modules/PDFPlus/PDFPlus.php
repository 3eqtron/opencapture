<?php

class PDFPlus
    extends DOMXPath
{
    private $Batch;
    private $Directory_out;
    private $Prefix;
    private $Config_File;
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
        /*$Config->load(
            __DIR__ . DIRECTORY_SEPARATOR . "PDFPlus.xml"
        );*/
        parent::__construct($Config);
        
    }
    
    function ImportFiles(
        $Directory_in,
        $Directory_out,
        $Config_File,
        $Prefix = "",
        $Extensions=array(),
        $Recursive=true,
        $CreateFolders=false,
        $NbMaxFoldersToImport = 0,
        $DeleteSubFolders = false
    ) { 
        $this->Directory_out = $Directory_out;
        $this->Prefix = $Prefix;
        $this->Config_File = $Config_File;
        $this->Recursive = $Recursive;
        $this->CreateFolders = $CreateFolders;
        if (!is_array($Extensions)) $Extensions = explode(",",$Extensions);
        $this->Extensions = $Extensions;
        $this->NbMaxFoldersToImport = $NbMaxFoldersToImport;
        $this->DeleteSubFolders = $DeleteSubFolders;
        
        
        $_SESSION['capture']->logEvent(
            "Scanning directory $Directory_in for file import..."
        );

        $_SESSION['capture']->logEvent(
            "Config_File = $Config_File"
        );

        $_SESSION['capture']->logEvent(
            "Extensions = ".print_r($this->Extensions,true)
        );

        if(!file_exists($Config_File)){
            $_SESSION['capture']->logEvent(
                "Unable to open configuration file '$Config_File' !", 2
            );
            trigger_error(
                "Unable to open configuration file '$Config_File' !",
                E_USER_ERROR
            );
        }
       
        $result = 
            $this->ScanDirectory(
                $Directory_in,
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
            
            $_SESSION['capture']->logEvent(
                "pdfplus '$entry_path' -config".$this->Config_File
            );

            copy($entry_path, $this->Directory_out.$this->Prefix.$entry_name);
            sleep(rand(1,20));
            unlink($entry_path);
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

}



?>