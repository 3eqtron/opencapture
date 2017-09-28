<?php

/********************************************************************************
    Batch (B)
        Metadata
        Folders
            Folder (id=Fn, path)
                Folders...
                Files...
        Files
            File (id=Fn, filename)
                Documents...
        Documents
            Document (id=Dn, filename )
                Metadata
                Notes...
                Pages
                    Page (number=n, offset, length)
                        Metadata
                        
                        Table
                            Row
                                Cell
                                    Para...
                        Para
                            Line...
                        Line (number=n, offset, length)
                            Word
                                Text
                                Box (llx, lly, urx, ury)
                Resources
                    Images
                        Image (id="D1I1" filename)
                    Fonts
                        Font 
                    ColorSets
                        ColorSet
                Attachments
                    Attachment (id=An)
                        Metadata
        Notes
            Note (author, date)     
    


********************************************************************************/

class Batch 
    extends DOMDocument 
{
    
    public $XPath;
    
    public function __construct(
    ) {
        $version="1.0";
        $encoding="UTF-8";
        
        parent::__construct($version, $encoding);
        $this->registerNodeclass('DOMElement', 'BatchElement');
        $this->XPath = new DOMXPath($this);    
        
    }
    
    public function __toString()
    {
        return @$this->saveXML();
    }
   
    function init(
        $name,
        $id,
        $rootDirectory,
        $errorDirectory
    ) {
        /** Create batch root element
        ******************************************************************************/
        $Batch = $this->createElement('Batch');
        $this->appendChild($Batch);
        
        /** Set batch attributes
        ******************************************************************************/
        $Batch->setAttribute("name", $name);

        $Batch->setAttribute("id", $id);
        $Batch->setIdAttribute("id", true);
        
        /** Create directory
        ******************************************************************************/
        $directory = $rootDirectory . DIRECTORY_SEPARATOR . $id;
        $Batch->setAttribute("directory", $directory);
        mkdir($directory, 0777);

        $Batch->setAttribute("errorDirectory", $errorDirectory);
        if (!is_dir($errorDirectory)) {
            mkdir($errorDirectory);
        }

        echo 'error directory ' . $errorDirectory . PHP_EOL;
               
        return $id;
    }
    
    function load(
        $BatchId,
        $envDirectory = null
    ) {
        parent::load($envDirectory . DIRECTORY_SEPARATOR . $BatchId . DIRECTORY_SEPARATOR . $BatchId . '.xml', $options);
        $this->XPath = new DOMXPath($this);
    }
        
    function query(
        $query
    ) {
        if(!$this->XPath)
            $this->XPath = new DOMXPath($this);
        
        $num_args = func_num_args();
        if($num_args == 1)
            $result = $this->XPath->query($query);
            
        if($num_args == 2)
            $result = $this->XPath->query($query, func_get_arg(1));
            
        return $result;      
    }
    
    function evaluate(
        $query
    ) {
        if(!$this->XPath)
            $this->XPath = new DOMXPath($this);
        
        $num_args = func_num_args();
        if($num_args == 1)
            $result = $this->XPath->evaluate($query);
            
        if($num_args == 2)
            $result = $this->XPath->evaluate($query, func_get_arg(1));
            
        return $result;      
    }
    
    function registerNamespace(
        $prefix,
        $namespaceURI 
    ) {
        $this->XPath->registerNamespace(
            $prefix,
            $namespaceURI 
        );
    }
    
    function __get(
        $name
    ) {
        if($this->documentElement->hasAttribute($name))
            return $this->documentElement->getAttribute($name);
        if(isset($this->{$name}))
            return $this->{$name};
    }  
    
    #*************************************************************************
    #
    #                           CREATE STRUCTURES
    #
    #*************************************************************************
    function createDocument(
        $file = false
    ) {
        $id = $this->getNextId('D');
        
        $Document = $this->createElement("Document");
        $Document->setAttribute('id', $id);
        $Document->setIdAttribute('id', true);
        
        $resultImportResource = false;
        
        if($file)
            $resultImportResource = $Document->importResource(
                $file
            );
        
        if ($resultImportResource) {
            return $Document;
        } else {
            return false;
        }
    }
    
    function createWord(
        $textContent = false
    ) {
        $Word = $this->createElement("Word");
        
        $Text = $this->createElement('Text', htmlentities($textContent));
        $Word->appendChild($Text);
       
        return $Word;
    }
    
    #*************************************************************************
    #  ADD
    #*************************************************************************
    public function appendContent(
        $Content,
        $type
    ) {
        return $this->documentElement->appendContent($Content, $type);
    }
    
    function addDocument(
        $sourcePath=false
    ) {
        return $this->documentElement->addDocument($sourcePath);
    }
    
    function addFolder(
        $dirPath=false
    ) {
        return $this->documentElement->addFolder($dirPath);
    }
    
    function addFile(
        $sourcePath
    ) {
        return $this->documentElement->addFile($sourcePath);
    }
    
    /**************************************************************************
    **  REMOVE
    **************************************************************************/
    public function removeFile(
        $File
    ) {
        unlink($File->path);
        $File->parentNode->removeChild($File);
    }
    
    function save(
        $filename=false
    ) {
        if(!$filename)
            $filename = $this->directory . DIRECTORY_SEPARATOR . $this->id . '.xml';
        parent::save($filename);
    }
       
    /**************************************************************************
    **  GET
    **************************************************************************/
    public function getDocuments()
    {
        return $this->query('//Documents/Document');
    }
    
    public function getNextId(
        $prefix
    ) {
        $i=0;
        do {
            $i++;
            $id = $prefix . $i;
        } while (
            $this->getElementById($id)
        );
        return $id;
    }
        
    public function delete() 
    {
        $batchDirectory = $this->directory;
        $this->empty_dir($batchDirectory);
        @rmdir($batchDirectory);
    }
    
    private function empty_dir(
        $dir_path
    ) {
        $dirhdl = opendir($dir_path);
        while($entry_name = readdir($dirhdl)) {
            if($entry_name == '.' || $entry_name == '..') 
                continue;
                
            $entry_path = $dir_path . DIRECTORY_SEPARATOR . $entry_name;
            if(is_dir($entry_path)) {
                $this->empty_dir($entry_path);
                @rmdir($entry_path);
            }
            else
                @unlink($entry_path);
        }
    }

}

class BatchElement
    extends DOMElement
{
    public function __get(
        $name
    ) {

        if($this->hasAttribute($name))
            return $this->getAttribute($name);

        return $this->{$name};   
    }
    
    public function __set(
        $name,
        $value = false
    ) {
        $this->setAttribute($name, htmlentities($value)); 
    }
    
    public function __toString()
    {
        return @$this->ownerDocument->saveXML($this);
    }
    
    #*************************************************************************
    #  CONTAINERS
    #************************************************************************/
    public function getContainer(
        $type
    ) {
        return 
            $this->ownerDocument->query(
                './'.$type,
                $this
            )->item(0);
    }
    
    public function addContainer(
        $type
    ) {
        $Container = $this->ownerDocument->createElement($type);
        $this->appendChild($Container);
        return $Container;
    }
    
    public function appendContent(
        $Content,
        $type
    ) {
        if(!$Container = $this->getContainer($type))
            $Container = $this->addContainer($type);
        
        $Container->appendChild($Content);
    }
    
    #*************************************************************************
    #  CONTENT : METADATA
    #************************************************************************/
    public function setMetadata(
        $name,
        $value
    ) {
        $Batch = $this->ownerDocument;

        $Metadata = 
            $Batch->query(
                './Metadata/'.$name,
                $this
            )->item(0);
        if (is_scalar($value)) {
            $value = str_replace('&', '&#160;', $value);

            if($Metadata) {
                $Metadata->nodeValue = $value;
            } else {
                $Metadata = $Batch->createElement($name, $value);
                $this->appendContent($Metadata, 'Metadata');
            }
        } elseif (is_array($value)) {
            foreach ($value as $key => $item) {
                $itemNode = $this->itemToNode($item, $name);
                $this->appendContent($itemNode, 'Metadata');
            }
        }

        return $Metadata;
    }

    protected function itemToNode($value, $name)
    {
        $Batch = $this->ownerDocument;

        if (is_scalar($value)) {
            return $Batch->createElement($name, $value);
        } 
        if (is_object($value)) {
            $value = get_object_vars($value);
        }
        if (is_array($value)) {
            $element = $Batch->createElement($name);
            foreach ($value as $key => $item) {
                if (is_numeric($key)) {
                    $key = 'item';
                }
                $itemNode = $this->itemToNode($item, $key);

                $element->appendChild($itemNode);
            }

            return $element;
        }
    }
    
    public function getMetadata(
        $name=false
    ) {
        if(!$name)
            return 
                $this->ownerDocument->query(
                    './Metadata/*',
                    $this
                );
        
        if($Metadata = $this->ownerDocument->query(
                './Metadata/'.$name,
                $this
            )->item(0)
        )
            return $Metadata->nodeValue;
    }
        
    #*************************************************************************
    #  CONTENT : FOLDERS
    #************************************************************************/
    public function getFolders()
    {
        return 
            $this->ownerDocument->query(
                './Folders/Folder',
                $this
            );
    }
    
    public function addFolder(
        $dir=false
    ) {
        $Batch = $this->ownerDocument;
        
        $id = $Batch->getNextId('R');
        
        $Folder = $Batch->createElement("Folder");
        $Folder->setAttribute('id', $id);
        $Folder->setIdAttribute('id', true);
        
        if($dir)
            $Folder->setAttribute(
                'path',
                $dir
            );
        
        $this->appendContent($Folder, 'Folders');
        
        return $Folder;
    }
    
    #*************************************************************************
    #  CONTENT : FILES
    #************************************************************************/
    public function getFiles()
    {
        return 
            $this->ownerDocument->query(
                './Files/File',
                $this
            );
    }
    
    public function addFile(
        $source=false
    ) {
        $Batch = $this->ownerDocument;
        
        $id = $Batch->getNextId('F');
        
        $File = $Batch->createElement("File");
        $File->setAttribute('id', $id);
        $File->setIdAttribute('id', true);
        
        $resultImportResource = false;
        
        if($source)
            $resultImportResource = $File->importResource(
                $source
            );
        
        if ($resultImportResource) {
            $this->appendContent($File, 'Files');
            return $File;
        } else {
            return false;
        }
    }
    
    #*************************************************************************
    #  CONTENT : DOCUMENTS
    #************************************************************************/
    public function getDocuments()
    {
        return 
            $this->ownerDocument->query(
                './Documents/Document',
                $this
            );
    }
    
    public function addDocument(
        $resource = false
    ) {
        $Batch = $this->ownerDocument;
        
        $id = $Batch->getNextId('D');
        
        $Document = $Batch->createElement("Document");
        $Document->setAttribute('id', $id);
        $Document->setIdAttribute('id', true);
        
        $resultImportResource = false;
        
        if($resource)
            $resultImportResource = $Document->importResource(
                $resource
            );
        
        if ($resultImportResource) {
            $this->appendContent($Document, 'Documents');
            return $Document;
        } else {
            return false;
        }
    }
    
    #*************************************************************************
    #  CONTENT : PAGES
    #************************************************************************/
    public function addPage() 
    {
        $Batch = $this->ownerDocument;
        
        $Page = $Batch->createElement("Page");
                
        $this->appendContent($Page, 'Pages');
        
        return $Page;
    }
        
    public function getPages()
    {
        return 
            $this->ownerDocument->query(
                './Pages/Page', 
                $this
            );
    }
    
    public function getPage(
        $number
    ) {
        return 
            $this->ownerDocument->query(
                './Pages/Page[@number="'.$number.'"]', 
                $this
            )->item(0);
    }
    
    #*************************************************************************
    #  CONTENT : ATTACHMENTS
    #************************************************************************/
    public function addAttachment(
        $resource
    ) {
        $Batch = $this->ownerDocument;
        $id = $Batch->getNextId('A');
        
        $Attachment = $Batch->createElement("Attachment");
        $Attachment->setAttribute('id', $id);
        $Attachment->setIdAttribute('id', true);
        
        $resultImportResource = false;
        
        if($resource)
            $resultImportResource = $Attachment->importResource(
                $resource
            );
        
        if ($resultImportResource) {
            $this->appendContent($Attachment, 'Attachments');
            return $Attachment;
        } else {
            return false;
        }
    }

    public function getAttachments()
    {
        return 
            $this->ownerDocument->query(
                './Attachments/Attachment', 
                $this
            );
    }
    
    #*************************************************************************
    #  CONTENT : IMAGES
    #************************************************************************/
    public function getImages()
    {
        return 
            $this->ownerDocument->query(
                './Images/Image', 
                $this
            );
    }
    
    #*************************************************************************
    #  CONTENT : FONTS
    #************************************************************************/    
    public function getFonts()
    {
        return 
            $this->ownerDocument->query(
                './Fonts/Font', 
                $this
            );
    }
    
    public function getFont(
        $id
    ) {
        $Font = 
            $this->ownerDocument->query(
                './Font[@id="'.$id.'"]',
                $this
            )->item(0);
        
        if(!$Font) return false;
        
        $nameTokens = explode('-', $Font->name);
        $Font->setAttribute('family', $nameTokens[0]);
        if($nameTokens[1])
            $Font->setAttribute('style', $nameTokens[1]);
        
        return $Font;
    }
    
    #*************************************************************************
    #  STRUCTURE : WORD / TEXT / BOX
    #************************************************************************/
    public function addWord(
        $wordText = false
    ) {
        $Batch = $this->ownerDocument;
        $Word = $Batch->createElement('Word');
        $this->appendChild($Word);
        
        if(mb_strlen($wordText))
            $Text = $Word->addText($wordText);

        return $Word;
    }
    
    function addBox(
        $llx,
        $lly,
        $urx,
        $ury
    ) {
        $Batch = $this->ownerDocument;
        
        $Box = $Batch->createElement('Box');
        
        $Box->llx = $llx;
        $Box->lly = $lly;
        $Box->urx = $urx;
        $Box->ury = $ury;
        
        $this->appendChild($Box);
        
        return $Box;
    }
    
    public function getBox()
    {
        return $this->getElementsByTagName('Box')->item(0);
    }
    
    public function addText(
        $wordText = false
    ) {
        $Batch = $this->ownerDocument;
        $Text = $Batch->createElement('Text', htmlentities($wordText));
        $this->appendChild($Text);
        return $Text;
    }
    
    public function setText(
        $text
    ) {
        if(!$Text = 
            $this->ownerDocument->query(
                './Text', 
                $this
            )->item(0)
        )
            $Text = $this->addText($text);
        else 
            $Text->nodeValue = $text;
    }
    
    public function getText() 
    {
        if($Text = 
            $this->ownerDocument->query(
                './Text', 
                $this
            )->item(0)
        )
            return $Text->nodeValue;
    }
    
    public function getFullText(
        $separator = ' '
    ) {
        $Texts = 
            $this->ownerDocument->query(
                './/Text', 
                $this
            );
        $l = $Texts->length;
        $FullText = false;
        for($i=0; $i<$l; $i++) {
            $Text = $Texts->item($i);
            $FullText = $Text->nodeValue . $separator;
        }
        return trim($FullText);
    }
        
    #*************************************************************************
    #  STRUCTURE : VARIOUS
    #************************************************************************/
    public function get(
        $name
    ) {
        return $this->getElementsByTagName($name);
    }
    
    public function add(
        $name,
        $value = false
    ) {
        $Batch = $this->ownerDocument;
        
        $Element = $Batch->createElement($name, htmlentities($value));
        
        $this->appendChild($Element);
        
        unset($Batch);
        
        return $Element;
    }

    #*************************************************************************
    #  SOURCE MANAGEMENT
    #************************************************************************/
    public function importResource(
        $resourcePath
    ) {
        $Batch = $this->ownerDocument;
        
        $extension = pathinfo($resourcePath,  PATHINFO_EXTENSION); 
        
        $filename = basename($resourcePath, '.' . $extension); 

        $copyOK = true;

        $path = (string)$Batch->directory  . DIRECTORY_SEPARATOR . $this->id;
        if($extension) $path .= '.' . $extension;
        # If imported file already has batch path, no copy

        //echo 'COPY FROM ' . $resourcePath . PHP_EOL . ' TO ' . $path . PHP_EOL;
        if($resourcePath != $path) {
            $copy = @copy($resourcePath, $path);
            if(!$copy) {
                /*$_SESSION['capture']->sendError(
                    "Copy of file '$resourcePath' into batch directory failed"
                );*/
                $_SESSION['capture']->logEvent(
                    "Copy of file '$resourcePath' into batch directory failed " 
                        . PHP_EOL . 'COPY FROM ' . $resourcePath . PHP_EOL 
                        . 'COPY TO ' . $path . PHP_EOL , 2
                );
                $copyOK = false;
            }
        }
        if ($copyOK) {
            //ADDED BY LGI
            $this->setAttribute('resourcepath', $resourcePath);
        
            $this->setAttribute('filename', $filename);
            $this->setAttribute('path', $path);
            $this->setAttribute('extension', $extension);
            $this->setAttribute('size', filesize($path));
        
            //ADDED BY LGI
            $filemtime = filemtime($resourcePath);
            //echo $filemtime . " " . date("d/m/Y H:i:s", $filemtime) . PHP_EOL;
            $filectime = filectime($resourcePath);
            //echo $filectime . " " . date("d/m/Y H:i:s", $filectime) . PHP_EOL;
            $creationDate = min($filemtime,$filectime);
            //echo $creationDate . " " . date("d/m/Y H:i:s", $creationDate) . PHP_EOL;
            $creationDate = date("d/m/Y H:i:s", $creationDate);
            $this->setAttribute("creationdate", $creationDate);
            /* TO DO : check format using software
            ********************************************************************************/
            /* get mimetype
            ********************************************************************************/
            if($finfo = new finfo()) {
                $this->setAttribute(
                    'mimetype', 
                    $finfo->file($path, FILEINFO_MIME_TYPE)
                );
                $this->setAttribute(
                    'mimeencoding', 
                    $finfo->file($path, FILEINFO_MIME_ENCODING)
                );
            }
        }
        return $copyOK;
    }
}

