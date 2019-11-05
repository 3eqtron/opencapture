<?php
# TET_char_info character types with real geometry info.
define("TET_CT__REAL", 0);
define("TET_CT_NORMAL", 0);
define("TET_CT_SEQ_START", 1);

# TET_char_info character types with artificial geometry info.
define("TET_CT__ARTIFICIAL", 10);
define("TET_CT_SEQ_CONT", 10);
define("TET_CT_INSERTED", 12);

# TET_char_info text rendering modes.
define("TET_TR_FILL", 0);			# fill text
define("TET_TR_STROKE", 1);			# stroke text (outline)
define("TET_TR_FILLSTROKE", 2);		# fill and stroke text
define("TET_TR_INVISIBLE", 3);		# invisible text
define("TET_TR_FILL_CLIP", 4);		# fill text and addd it to the clipping path
define("TET_TR_STROKE_CLIP", 5);	# stroke text and add it to the clipping path
define("TET_TR_FILLSTROKE_CLIP", 6);# fill and stroke text and add it to the clipping path
define("TET_TR_CLIP", 7);			# add text to the clipping path

# TET_char_info attributes
define("TET_ATTR_NONE", 0x00000000); 
define("TET_ATTR_SUB", 0x00000001);	                    # subscript
define("TET_ATTR_SUP", 0x00000002);	                    # superscript
define("TET_ATTR_DROPCAP", 0x00000004);	                # initial large letter
define("TET_ATTR_SHADOW", 0x00000008);	                # shadowed text
define("TET_ATTR_DEHYPHENATION_PRE", 0x00000010);       # character before hyphenation
define("TET_ATTR_DEHYPHENATION_ARTIFACT", 0x00000020);  # hyphenation artifact, i.e. the dash
define("TET_ATTR_DEHYPHENATION_POST", 0x00000040);      # character after hyphenation


class PDFExtractor
    extends TET
{
    private $Batch;
    private $logFile;
    private $optlists;
    
    function __construct() {
        $this->Batch = $_SESSION['capture']->Batch;
        parent::__construct();
    }
    
    function openLog()
    {
        $this->LogFile = 
            fopen( 
                __DIR__ . DIRECTORY_SEPARATOR .
                "log.txt",
                "w"
            );
    }
    
    function log(
        $message
    ) {
        fwrite(
            $this->LogFile,
            date('c') . " " .$message . "(" . memory_get_usage() . ")\r\n"
        );
    }
    
    function closeLog()
    {
        fclose($this->LogFile);
    }
    
    function load_optlists(
        $optlists=array()
    ) {
        if(count($optlists) === 0) return;
        foreach($optlists as $type => $name) {
            $this->load_optlist($type, $name);
        }
    }
    
    function load_optlist(
        $type,
        $name
    ) {
        $optlistfile =
            __DIR__ . DIRECTORY_SEPARATOR 
                . 'optlists' . DIRECTORY_SEPARATOR
                . $name . '.' . $type;
                
        if(is_file($optlistfile)) {
            $this->optlists[$type] = 
                file_get_contents(
                    __DIR__ . DIRECTORY_SEPARATOR 
                        . 'optlists' . DIRECTORY_SEPARATOR
                        . $name . '.' . $type
                );
        } 
    }
    
    function get_optlist(
        $type
    ) {
        if(!isset($this->optlists[$type]))
            $this->load_optlist($type, 'default');
        
        return (string)$this->optlists[$type];

    }
    
    function Extract(
        $Elements,
        $optlists=array()
    ) {        
        /********************************************************************************
        ** Load optlists
        ********************************************************************************/        
        $this->load_optlists($optlists);

        /********************************************************************************
        ** Loop on Batch Elements and extract text of pages + resources 
        ********************************************************************************/
        $l = $Elements->length;
        
        //$this->Log("Extraction of batch " . $this->Batch->id . " on $l elements");
        
        for($di=0; $di<$l; $di++) {
            $Element = $Elements->item($di);
            $type = $Element->tagName;
            $eid = $Element->id;
            $path = $Element->path;
            $extension = $Element->extension;
            
            //$this->Log("Extraction of $type $di using source file $path...");
            
            if(strtolower($extension) != 'pdf') {
                $_SESSION['capture']->logEvent(
                    $type . ' ' . $eid . " is not a PDF file, skipping extraction",
                    1
                ); 
                continue;
            }
            
            $_SESSION['capture']->logEvent(
                "Extracting text for " . $type . ' ' . $eid
            );
            
            /********************************************************************************
            ** Extract Text with PDFLib TET
            ********************************************************************************/
            try {
                $this->set_option($this->get_optlist('globaloptlist'));
                // **********************************************************************
                // Open document
                // **********************************************************************
                $doc = $this->open_document($path, (string)$this->get_optlist('open_document'));
                if ($doc == -1) {
                    $_SESSION['capture']->sendError(
                        "ERROR: ". $this->get_errnum() 
                            . " in " . $this->get_apiname() 
                            . "(): " . $this->get_errmsg()
                    );
                }
                //$this->Log("Document open");
                // **********************************************************************
                // Loop on pages to extract text and generate TETML
                // **********************************************************************
                $num_pages = $this->pcos_get_number($doc, "length:pages");
                //$this->Log("Process $num_pages pages...");
                $page_num = false;
                              
                for ($page_num=1; $page_num<=$num_pages; $page_num++) {
                    //$this->Log("Process page $page_num...");
                    # Retrieve TETML
                    $res = $this->process_page($doc, $page_num, (string)$this->get_optlist('process_page'));
                    # not used if tetml
                    if ($res == -1) {
                        $_SESSION['capture']->logEvent(
                            "ERROR: ". $this->get_errnum() ." in ". $this->get_apiname() . "(): " . $this->get_errmsg(),
                            2
                        );
                        next;  # try next page
                    }
                    
                    //$this->Log("Page processed.");
                }
                
                # Process trailer info
                //$this->Log("Process trailer information...");
                $this->process_page($doc, 0, "tetml={trailer}");
                
                // **********************************************************************
                // Extract images
                // **********************************************************************
                $placed_images = 0;
                for ($page_num=1; $page_num<=$num_pages; $page_num++) {
                    # open page to allow images[] array to contain merged images
                    $page = $this->open_page($doc, $page_num, (string)$this->get_optlist('open_page'));
                    while ($this->get_image_info($page)) {
                        $placed_images++;
                    }
                    $this->close_page($page);
                }
               
                $num_images = $this->pcos_get_number($doc, "length:images");
                for ($image_num = 0; $image_num < $num_images; $image_num++) {
                    //$this->Log("Process image $image_num...");
                    $mergetype = $this->pcos_get_number($doc, "images[" . $image_num . "]/mergetype");
                    if($mergetype == 0 || $mergetype == 1) {             
                        $imagename = 
                            (string)$this->Batch->directory 
                                . DIRECTORY_SEPARATOR . $eid . '_I' . $image_num;
                        $this->write_image_file(
                            $doc, 
                            $image_num, 
                            $this->get_optlist('write_image_file') . " filename=".$imagename
                        );
                    }
                    //$this->Log("Image processed.");
                }

                //$this->Log("Get XML data...");
                $tetml = $this->get_tetml($doc, '');
                
                //$this->Log("Close document...");
                $this->close_document($doc);
                
            }
            catch (TETException $e) {
                if ($page_num == 0)
                    $_SESSION['capture']->sendError(
                        "TET exception occurred during extraction: " 
                            . "[" . $e->get_errnum() . "] " 
                            . $e->get_apiname() . ": " 
                            . $e->get_errmsg()
                    );
                else
                    $_SESSION['capture']->sendError(
                        "TET exception occurred during extraction: " 
                            . "[" . $e->get_errnum() . "] " 
                            . $e->get_apiname() 
                            . " on page $page_num: " 
                            .  $e->get_errmsg()
                    );
            }
            catch (Exception $e) {
                $_SESSION['capture']->sendError($e);
            }
            
            // **********************************************************************
            // Load TETML Capture Batch Document
            // **********************************************************************
            //$this->Log("Load TETML document...");
            $tetml = 
                preg_replace(
                    '/xmlns=.[^\s]*/',
                    '',
                    $tetml
                );
                    
            $tetDOM = new DOMDocument();
            $tetDOM->loadXML($tetml);
            
            unset($tetml);
            
            $tetDOM->save($this->Batch->directory . DIRECTORY_SEPARATOR . $this->Batch->id . '.tetml');
            $tetXPath = new DOMXPath($tetDOM);
            //$this->Log("TETML document loaded");
            
            // Load TET Document informations
            // **********************************************************************
            /*
                TET
                    Creation
                    Document
                        DocInfo
                        Options
                        Pages
                            Page
                                Options
                                Content
                                    Para
                                    Table
									Line
									...
                            Resources
                                Fonts
                                Images
                                ColorSpaces
            
            */
            //$this->Log("Getting TETML Document...");
            
            $tetDocument = $tetXPath->query('/TET/Document')->item(0);
            $Element->setAttribute(
                'PDFVersion', 
                $tetDocument->getAttribute('PdfVersion')
            );
            $Element->setAttribute('OCR', true);
            $Element->setAttribute('uom', 'dot');
            $Pages = $this->Batch->createElement("Pages");
            $Element->appendChild($Pages);
                
            
            # Import TETML Document DocInfo
            $tetDocInfos = $tetXPath->query('./DocInfo/*', $tetDocument);
            $dil = $tetDocInfos->length;
            for($dii=0; $dii<$dil; $dii++) {
                $tetDocInfo = $tetDocInfos->item($dii);
                $Element->setMetadata(
                    $tetDocInfo->tagName,
                    $tetDocInfo->nodeValue
                );
            }
            unset($tetDocInfos);
            
            $tetPages = $tetXPath->query('./Pages/Page', $tetDocument);
            $pl = $tetPages->length;
            $pid = $this->Batch->getNextId('P');
            $wid = $this->Batch->getNextId('W');
            
            for($pi=0; $pi<$pl; $pi++) {
                $tetPage = $tetPages->item($pi);
                $Page = $this->Batch->importNode($tetPage->cloneNode(false), true);
                $Pages->appendChild($Page);
                $Page->setAttribute("id", $pid);
                $Page->setIdAttribute("id", true);
                $pid++;
                $Page->setAttribute('OCR', true);
                
                # Import TETML Document Page Contents: Para, Line, Table, Word
                $tetContents = $tetXPath->query('./Content/*', $tetPage);
                $cl = $tetContents->length;
                for($ci=0; $ci<$cl; $ci++) {
                    $tetContent = $tetContents->item($ci);
                    $Content = $this->Batch->importNode($tetContent, true);
                    $Page->appendChild($Content);
                }
                unset($tetContents);
                
                # Set Document Page Words ids
                $Words = $this->Batch->query(".//Word", $Page);
                $wl = $Words->length;
                for($wi=0; $wi<$wl; $wi++) {
                    $Word = $Words->item($wi);
                    $Word->setAttribute("id", $wid);
                    //$Word->setIdAttribute("id", true);
                    $wid++;
                    if($Box = $Word->getElementsByTagName("Box")->item(0))
                        $Box->setAttribute('unit', 'dot');
                    
                }
                unset($Words);
            }
            unset($tetPages);
            
            //$this->Log("Getting TETML Document Resources...");
            $tetResources = $tetXPath->query('./Pages/Resources/*', $tetDocument);
            $rl = $tetResources->length;
            for($ri=0; $ri<$rl; $ri++) {
                $tetResource = $tetResources->item($ri); 
                //$this->Log("Importing resource $ri...");
                $Resource = $this->Batch->importNode($tetResource, true);
                $Element->appendChild($Resource);
                /*$idResources = $this->Batch->query(".//*[@id]", $Resource);
                $il = $idResources->length;
                for($ii=0; $ii<$il; $ii++) {
                    $idResource = $idResources->item($ii);
                }*/
            }
            unset($tetResources);
            
            
            $Element->setAttribute('OCR', true);
        }
        
        //$this->closeLog();
        
    }
}

?>