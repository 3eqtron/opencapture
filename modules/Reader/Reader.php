<?php

class Reader
    extends DOMXpath
{
    private $Batch;
    
    private $ElementUom = 'dot';
    private $TemplateUom = 'dot';
    private $resolution = 72;
    private $precision = 1;
    
    private $Template;
   
    private $zones = array();
    
    public function __construct() 
    {
        $this->Batch = $_SESSION['capture']->Batch;
        
        $Config = new DOMDocument();
        $Config->load(
            __DIR__ . DIRECTORY_SEPARATOR . "Reader.xml"
        );
        parent::__construct($Config);
        
        // **********************************************************************
		//  Load Dictionaries
		// **********************************************************************
		/*$dictionaries = $this->queryConfig("//Dictionary");
        $l = $dictionaries->length;
		for($i=0; $i<$l; $i++) {
			$this->loadDictionary($dictionaries->item($i));
		}*/
		
		// **********************************************************************
		//  Load Formatters
		// **********************************************************************
		$formatters = $this->query("//Formatter");
        $l = $formatters->length;
		for($i=0;$i<$l;$i++) {
			require_once($formatters->item($i)->getAttribute("script"));
		}
    }
            
    // **********************************************************************
	// READ Main method
	// Process batch document automatic reading
	// **********************************************************************
	function read(
        $Elements,
        $DefaultTemplate
    ) {
        /********************************************************************************
        ** Loop on Batch Elements and read
        ********************************************************************************/
        $l = $Elements->length;
        $_SESSION['capture']->logEvent($l . " elements to read");
        
        for($i=0; $i<$l; $i++) {
            $Element = $Elements->item($i);
            
            if($Element->hasAttribute("type")) 
                $TemplateName = $Element->getAttribute("type");
            else 
                $TemplateName = $DefaultTemplate;
                
            /********************************************************************************
            ** Read
            ********************************************************************************/
            //$_SESSION['capture']->logEvent("Reading ".$Element->tagName." $i with template '$TemplateName'");
            $this->readElement(
                $Element, 
                $TemplateName
            );
        }
    }

    private function readElement(
        $Element, 
        $TemplateName
    ) {
        
        $Reader = $this->Batch->createElement('Reader');
        $Element->appendChild($Reader);
        
        if($ElementUom = $Element->uom)
            $this->ElementUom = $ElementUom;
        
		// **********************************************************************
		//  Load Element Type config & import as Reader element
		// **********************************************************************
		$TemplateConfig = 
            $this->query(
                "/Reader/Templates/Template[@name='".$TemplateName."']"   
            )->item(0);
		if(!$TemplateConfig) {
			$_SESSION['capture']->logEvent(
                "Template '$TemplateName' is not defined!",
                3
            );
            die("Template '$TemplateName' is not defined!");
		}
        
        $Template = $this->Batch->importNode($TemplateConfig, true);
        $Reader->appendChild($Template);
        
        if($TemplateUom = $Template->uom)
            $this->TemplateUom = $TemplateUom;
                       
        // **********************************************************************
		//  Apply Registration fields
		// **********************************************************************
        $Registrations = 
            $this->Batch->query(
                "./Registrations/Registration", 
                $Template
            );
        $l = $Registrations->length;
		for($i=0; $i<$l; $i++) {
            $Registration = $Registrations->item($i);
			$this->register(
                $Registration,
                $Element
            );
		}
		// **********************************************************************
		// Load Main Document to Document Search Zone
		// Make fulltexts and calc word offsets for document
		// **********************************************************************
		/*$zones = 
            $this->query(
                ".//Zones", 
                $ElementType
            );
		if(!$zones) {
			$zones = $this->createElement("Zones"); 
            $ElementType->appendChild($zones);
		}
        $documentZone = $this->createElement("Zone");
		$documentZone->setAttribute("name", "document");
        $zones->appendChild($documentZone);
        
        $documentPages = $this->importNode($batchDocument->getElementsByTagName("Pages")->item(0), true);
		$documentZone->appendChild($documentPages);
		
        $fulltext = $this->makeFullText($documentZone);
        $this->zones['document']['fulltext']['false'] = $fulltext;
        $this->zones['document']['fulltext']['true'] = str_replace(' ', '', $fulltext);
        */
        //$this->zones['document']['wordindex']['true'] = $this->getWordIndex($documentZone, true);
        //$this->zones['document']['wordindex']['false'] = $this->getWordIndex($documentZone, false);
        
		// **********************************************************************
		// Make zones
		// Make fulltexts and calc word offsets for each zone
		// **********************************************************************
		/*$zones = $this->xPath(".//Zones/Zone[@name!='document']", $documentType);
		for($zi=0;$zi<$zones->length;$zi++) {
			$zone = $zones->item($zi);
			$zoneName = $zone->getAttribute('name');
            $this->makeZone($zone);
			$fulltext = $this->makeFullText($zone);
            $this->zones[$zoneName]['fulltext']['false'] = $fulltext;
            $this->zones[$zoneName]['fulltext']['true'] = str_replace(' ', '', $fulltext);
            //$this->zones[$zoneName]['wordindex']['true'] = $this->getWordIndex($zone, true);
            //$this->zones[$zoneName]['wordindex']['false'] = $this->getWordIndex($zone, false);
		}*/
		
		// **********************************************************************
		// Locators
		// **********************************************************************
        $Locators = 
            $this->Batch->query(
                "./Locators/Locator", 
                $Template
            );
        $l = $Locators->length;
		for($i=0; $i<$l; $i++) {
            $Locator = $Locators->item($i);
            $this->locate(
                $Locator,
                $Element
            );
		}
        
        // **********************************************************************
		// Evaluators
		// **********************************************************************
        $Evaluators = 
            $this->Batch->query(
                "./Evaluators/Evaluator", 
                $Template
            );
        $l = $Evaluators->length;
		for($i=0; $i<$l; $i++) {
            $Evaluator = $Evaluators->item($i);
            $this->evaluate(
                $Evaluator,
                $Element
            );
		}
        
		// **********************************************************************
		// Export Metadata
		// **********************************************************************
		$Exports = 
            $this->Batch->query(
                "./Exports/Export", 
                $Template
            );
        $l = $Exports->length;
		for($i=0; $i<$l; $i++) {
            $Export = $Exports->item($i);
			$this->export(
                $Export,
                $Element
            );
		}
	}
	
	// **********************************************************************
	// INIT functions
	// **********************************************************************
	private function loadDictionary(
        $dictionary
    ) {
		switch((string)$dictionary->getAttribute("type")) {
		case 'csv':
			$path = (string)$dictionary->getAttribute("path");
			$delimiter = (string)$dictionary->getAttribute("delimiter");
			$enclosure = (string)$dictionary->getAttribute("enclosure");
			if (($handle = fopen($path, "r")) !== false) {
				while (($data = fgetcsv($handle, 1000, $delimiter, $enclosure)) !== false) {
					if($data[0] != false) {
						$entry = $this->XDoc->createElement("Entry");
                        $fromValue = $this->XDoc->createElement("Input", $data[0]);
						$toValue = $this->XDoc->createElement("Output", $data[1]);
                        $entry->appendChild($fromValue);
                        $entry->appendChild($toValue);
                        $dictionary->appendChild($entry);
					}
				}
				fclose($handle);
			}
		}
	}
	
    // **********************************************************************
	// REGISTRATION functions
	// **********************************************************************
    private function register(
        $Registration,
        $Element
    ) {
        
        $regType = $Registration->getAttribute('type');
		switch($regType) {
        case 'translation':
            $this->translate(
                $Registration,
                $Element
            );
            break;
        case 'format':
            break;
        
        case 'keyword':
            break;
        }
    }
    
    private function translate(
        $Registration,
        $Element
    ) {
        
        $this->inflate(
            $Registration,
            100
        );
        
        $RegistrationQuery = 
            $this->getFieldQuery(
                $Registration,
                $Element
            );
        $WordList = 
            $this->Batch->query(
                $RegistrationQuery, 
                $Element
            );
    }
    
	// **********************************************************************
	// ZONE functions
	// **********************************************************************
    private function makeZone($zone) {
		// **********************************************************************
		// Create query from attributes
		// **********************************************************************
		$page = (string)$zone->getAttribute("page");
		switch ($page) {
			case false :
			case '0':
			case 'All':
			case '':
				break;
			
			case 'Last':
			case 'last':
			case '999':
				$pagePredicat = '@number < parent::[last()/@number]';
				break;
				
			case 'Pair':
			case 'pair':
				$pagePredicat = '@number mod 2 = 1';
				break;			
			
			case 'Impair':
			case 'impair':
				$pagePredicat = '@number mod 2 = 0';
				break;
			
			default :
				$pagePredicat = '@number != '.$page.'';
		}
		$llx = $this->convertUOM($zone->getAttribute("x"));
		$lly = $this->convertUOM($zone->getAttribute("y") + $zone->getAttribute("height"));
		$urx = $this->convertUOM($zone->getAttribute("x") + $zone->getAttribute("width"));
		$ury = $this->convertUOM($zone->getAttribute("y"));
		$boxPredicat = '@llx < '.$llx.' or @lly > '.$lly.' or @urx > '.$urx.' or @ury < '.$ury;
		$query = './/Word';
		if($pagePredicat) $query = './/Page['.$pagePredicat.']//Word';
		if($boxPredicat) $query .= '/Box['.$boxPredicat.']/parent::*';
		
		// **********************************************************************
		// Master document is imported and used for query
		// **********************************************************************
		$documentPages = $this->query('//Zones/Zone[@name="document"]/Pages')->item(0);
		$pages = $documentPages->cloneNode(true);
		$zone->appendChild($pages);
		$results = $this->xPath($query, $pages);
		// **********************************************************************
		// Remove Results from page structure on zone
		// **********************************************************************
		for($ri=0; $ri<$results->length; $ri++) {
			$result = $results->item($ri);
			$result->parentNode->removeChild($result);
		}
	}
		
	private function makeFullText($zone) {
		$textNodes = $this->xPath(".//Text", $zone);
		$textValues = array();
		for($ti=0; $ti<$textNodes->length; $ti++) {
			$textValues[] = (string)$textNodes->item($ti)->nodeValue;
		}
		$fullText = implode(' ', $textValues);
		return $fullText;
	}
	
	private function setWordOffset($zone) {
		$words = $this->xPath(".//Word", $zone);
		$wordOffsetNoSpace = $wordOffsetSpace = 0;
		$wordEndNoSpace = $wordEndSpace = 0;
		for($wi=0;$wi<$words->length;$wi++) {
			$word = $words->item($wi);
			$wordText = $word->getElementsByTagName("Text")->item(0)->nodeValue;
			if($wi > 0) {
				$wordOffsetNoSpace = $wordEndNoSpace;
				$wordOffsetSpace = $wordEndSpace + 1;
			}
			$wordEndNoSpace = $wordOffsetNoSpace + strlen($wordText);
			$wordEndSpace = $wordOffsetSpace + strlen($wordText);
			
			$word->setAttribute("cts", $wordOffsetNoSpace);
			$word->setAttribute("cte", $wordEndNoSpace);
			$word->setAttribute("fts", $wordOffsetSpace);
			$word->setAttribute("fte", $wordEndSpace);
		}
	}
    
    private function getWordIndex($zone, $ignoreSpaces=false) {
        $words = $this->xPath(".//Word", $zone);
		$wordOffset = 0;
		$wordEnd = 0;
		for($i=0;$i<$words->length;$i++) {
			$word = $words->item($i);
            $wordId = $word->getAttribute('id');
            $text = $this->xpath('./Text', $word)->item(0)->nodeValue;
			if($i > 0) {
                if($ignoreSpaces) {
                    $wordOffset = $wordEnd;
                } else {
                    $wordOffset = $wordEnd + 1;
                }
			}
			$wordEnd = $wordOffset + strlen($text);
			
			$wordIndex[] = 
                array(
                    'id' => $wordId,
                    'text' => $text,
                    'offset' => $wordOffset,
                    'end' => $wordEnd
                    
                );
		}
		return $wordIndex;
    
    }
	
    private function getLocatingZones(
        $locator, 
        $Template
    ) {
        $locatingZonesList = 
            $this->xPath(
                "./LocatingZones/LocatingZone", 
                $locator
            );
		if($locatingZonesList->length > 0) {
			$locatingZonesPredicats = array();
			for($sz=0; $sz<$locatingZonesList->length; $sz++){
				$locatingZoneName = $locatingZonesList->item($sz)->getAttribute("name");
				$locatingZonesPredicats[] = "@name='".$locatingZoneName."'";
			}
			$locatingZones = $this->xPath("./Zones/Zone[".implode(' or ', $locatingZonesPredicats)."]", $Template);
		} else {
			$locatingZones = $this->xPath("./Zones/Zone[@name='document']", $Template);
		}
        return $locatingZones;
    }
    
    private function convertUOM(
        $value
    ) {
		if($this->TemplateUom == $this->ElementUom)
            return $value;
        
        
        switch($this->ElementUom) {
        # x -> dot
        case 'dot':
            switch($this->templateUom) {
            case 'mm':
                $ratio = (25.4 / $this->resolution);
                break;
            case 'inch':
                $ratio = (1 / $this->resolution);
                break;
            }
            break;
            
        case 'mm':
            switch($this->templateUom) {
            case 'dot':
                $ratio = ($this->resolution / 25.4);
                break;
            case 'inch':
                $ratio = $this->resolution;
                break;
            }
            break;
            
        case 'lc':
            break;
        }
        
        return round($value / $ratio, $this->precision);

	}
    
    // **********************************************************************
	// LOCATING functions
	// **********************************************************************
    private function locate(
        $Locator,
        $Element
    ) {       
        // **********************************************************************
		// Get Locating Zones (whole element if none)
		// **********************************************************************
		/*$locatingZones = 
            $this->getLocatingZones(
                $locator, 
                $documentType
            );*/
        //echo "<pre>Locator ".$locator->getAttribute("name")." - Found ".$locatingZones->length." locating zones</pre>";
		// **********************************************************************
		// Execute locating methods
		// **********************************************************************
        $locatorType = $Locator->getAttribute('type');
        $locatorName = $Locator->getAttribute('name');
        //$_SESSION['capture']->logEvent("Locating " . $locatorName . " of type " . $locatorType . "...");
		switch($locatorType) {
        case 'field':
            $this->FieldLocator(
                $Locator,
                $Element
            );
            break;
        case 'format':
            $this->FormatLocator(
                $Locator,
                $Element
            );
            break;
        
        case 'keyword':
            break;
        }
		
		                    
		// **********************************************************************
		// Execute Evaluation methods
		// **********************************************************************
		/*$evaluationMethods = $this->queryFirstItem("./EvaluationMethods", $locator);
		$locatingResults = $this->xPath(".//Results", $locatingMethods);
        $this->evaluateResults($evaluationMethods, $locatingResults, $locatingZones);*/
    
    }
    
    // FIELD locator
	// **********************************************************************
    private function FieldLocator(
        $Locator,
        $Element 
    ) {
        $Fields = 
            $this->Batch->query(
                './Field',
                $Locator
            );
        $l = $Fields->length;
        for($i=0; $i<$l; $i++) {
            $Field = $Fields->item($i);
            $this->locateField(
                $Field,
                $Element
            );
        }
    }
    
    private function locateField(
        $Field,
        $Element,
        $Item = 'Word'
    ) {	
		// **********************************************************************
		// Create query from attributes
		// **********************************************************************
        $FieldQuery = 
            $this->getFieldQuery(
                $Field, 
                $Element,
                $Item
            );
        
		//if($Item != "Word") echo "<pre>Search result query = $query on locating zones ".(int)$queryZones->length."</pre>";
		$WordList = 
            $this->Batch->query(
                $FieldQuery, 
                $Element
            );
		//if($Item != "Word") echo "<pre>Found ".$fieldResultsList->length." results</pre>";
		$this->createResults(
            $WordList, 
            $Field
        );
		
	}
	
    private function getFieldQuery(
        $Field, 
        $Element
    ) {
        $FieldQuery = '.';
        
        // Page predicat
        if($PagePredicat = 
            $this->getPagePredicat(
                $Field, 
                $Element
            )
        ) 
            $FieldQuery .= '//Page['.$PagePredicat.']';
        
        // Word predicats
        $WordPredicats = array();
        if($BoxPredicat = 
            $this->getBoxPredicat(
                $Field
            )
        )
            $WordPredicats[] = './Box['.$BoxPredicat.']';
            
        if($TextPredicat = 
            $this->getTextPredicat(
                $Field
            )
        ) 
            $WordPredicats[] = './Text['.$TextPredicat.']';
        
        if(count($WordPredicats) > 0)
            $FieldQuery .= '//Word['. implode(" and ", $WordPredicats). ']';
        
		//$_SESSION['capture']->logEvent("Field Query = $FieldQuery");
		return $FieldQuery;
	}
	
    private function getPagePredicat(
        $Field,
        $Element
    ) {
        $page = (string)$Field->getAttribute("page");
		
		switch ($page) {
			case false :
			case '0':
            case 'all':
			case '':
                return false;
			
            case 'l':
			case 'last':
                return 'last()';
			
            case 'p':
            case 'penultimate':
                return 'last()-1';
            
            case 'e':
            case 'even':
				return 'position() mod 2 = 0';
				
			case 'o':
            case 'odd':
				return 'position() mod 2 = 1';
			
			default :
				return 'position() = '.$page;
		}
        
    }
    
    private function getBoxPredicat(
        $Field
    ) {
		$llx = $this->convertUOM($Field->getAttribute("x"));
		$lly = $this->convertUOM($Field->getAttribute("y") + $Field->getAttribute("height"));
		$urx = $this->convertUOM($Field->getAttribute("x") + $Field->getAttribute("width"));
		$ury = $this->convertUOM($Field->getAttribute("y"));
        
		return '@llx >= '.$llx.' and @lly <= '.$lly.' and @urx <= '.$urx.' and @ury >= '.$ury;
    }
    
    private function getTextPredicat(
        $Field
    ) {
        if($Field->nodeValue)
            return '. = "'.$Field->nodeValue.'"';
        else 
            return false;
    }
    
    // FORMAT locator
	// **********************************************************************
	private function FormatLocator(
        $Locator,
        $Element 
    ) {
        $Formats = 
            $this->Batch->query(
                './Formats',
                $Locator
            );
        $l = $Formats->length;
        for($i=0; $i<$l; $i++) {
            $Format = $Formats->item($i);
            $this->locateFormat(
                $Format,
                $Element
            );
        }
    }
    
    function locateFormat(
        $Format, 
        $Element
    ) {			
        // **********************************************************************
		// Create query from attributes
		// **********************************************************************
		$searchFormat = $this->getFormatString((string)$format->nodeValue);
        //echo "<br/>Locating format $searchFormat ignoreSpaces:" . $format->getAttribute("ignoreSpaces");
        
		// **********************************************************************
		// Get fulltext and offset attributes to search
		// **********************************************************************
		$searchText = $Element->getFullText();
        $wordIndex = $this->zones[$queryZoneName]['wordindex'][$ignoreSpaces];
        
		//echo "<pre>Search format $formatName [$searchFormat] in zone $queryZoneName in text = $searchText</pre>";
		// **********************************************************************
		// Match format and create results
		// **********************************************************************
		if(preg_match_all((string)$searchFormat, $searchText, $matches, PREG_OFFSET_CAPTURE)) {
			foreach($matches[0] as $match) {
				$matchValue = $match[0];
				$matchOffset = $match[1];
				$matchEnd = $matchOffset + strlen($matchValue);
				$wordList = array();
				//echo "<pre>Match $matchValue at offset $matchOffset => $matchEnd </pre>";		
				switch($format->getAttribute("fullWord")) {
				case 'false':
					// TODO : Generate results with glyphs instead of words
                    foreach($wordIndex as $wordRef) {
                        if( ($wordRef['offset'] <= $matchOffset && $wordRef['end'] > $matchOffset)
                            || ($wordRef['offset'] > $matchOffset && $wordRef['end'] < $matchEnd)
                            || ($wordRef['offset'] < $matchEnd && $wordRef['end'] >= $matchEnd)
                        ) {
                            $wordList[] = $this->xpath('.//Word[@id="'.$wordRef['id'].'"]', $queryZone)->item(0);
                        }
                    }
                    /*$resultsList = $this->xPath(".//".$Item."["
						."(@".$startOffsetAttribute." <= ".$matchOffset." and @".$endOffsetAttribute." > ".$matchOffset.") "
						."or (@".$startOffsetAttribute." > ".$matchOffset." and @".$endOffsetAttribute." < ".$matchEnd.") "
						."or (@".$startOffsetAttribute." < ".$matchEnd." and @".$endOffsetAttribute." >= ".$matchEnd.") "
						."]", $queryZone);*/
					break;
					
				case 'true':
				default:
					//echo "<pre>FullWord activated, query for boundaries = .//Word[@".$startOffsetAttribute." = ".$matchOffset."] and .//Word[@".$endOffsetAttribute." = ".$matchEnd."]</pre>";	
					$offsetFound = $endFound = false;
                    foreach($wordIndex as $wordRef) {
                        if($wordRef['offset'] < $matchOffset) {
                            //echo "<br/>" . $wordRef['offset'] . "<" . $matchOffset . " => continue";
                            continue;
                        }
                        if($wordRef['offset'] == $matchOffset) {
                            //echo "<br/>" . $wordRef['offset'] . "==" . $matchOffset . " => offsetFound";
                            $offsetFound = true;
                        }
                        if(!$offsetFound) {
                            continue;
                        }
                        if($wordRef['offset'] >= $matchOffset && $wordRef['end'] <= $matchEnd) {
                            //echo "<br/>" . $wordRef['offset'] . ">=" . $matchOffset . " && " . $wordRef['end'] ."<=". $matchEnd."=> add word $wordId";
                            $wordList[] = $this->xpath('.//Word[@id="'.$wordRef['id'].'"]', $queryZone)->item(0);
                            if($wordRef['end'] == $matchEnd) {
                                //echo "<br/>" . $wordRef['end'] . "==" . $matchEnd . " => endFound";
                                $endFound = true;
                                break;
                            }
                        }
                    }
                    if(!$offsetFound || !$endFound) {
                        //echo "<br/>offset of end not found == clear words list";
                        $wordList = array();
                    }
                    /* $startBoundary = $this->queryFirstItem(".//".$Item."[@".$startOffsetAttribute." = ".$matchOffset."]", $queryZone);
					$endBoundary = $this->queryFirstItem(".//".$Item."[@".$endOffsetAttribute." = ".$matchEnd."]", $queryZone);
					if($startBoundary && $endBoundary) {
						//echo "<pre>FullWord activated, Query = .//".$Item."[@".$startOffsetAttribute." >= ".$matchOffset." and @".$endOffsetAttribute." <= ".$matchEnd."]</pre>";	
						$resultsList = $this->xPath(".//".$Item."[@".$startOffsetAttribute." >= ".$matchOffset." and @".$endOffsetAttribute." <= ".$matchEnd."]", $queryZone);
					}*/
				}
				$wordListCount = count($wordList);
                //echo "<br/>Found $wordListCount words on result";
				if($wordListCount > 0) {
                    // Do not merge entire word, as the value is set by PREG match
                    $result = $this->createResult($wordList[0], $resultTag);
                    $this->setResultText($result, $matchValue);
                    for($i=1; $i<$wordListCount;$i++) {
                        $this->mergeId($wordList[$i], $result);
                        $this->mergeBox($wordList[$i], $result);
                    }
                    $resultsNode->appendChild($result);
                }
				
			}
		}
		
	}
	
	function getFormatString(
        $formatString
    ) {
		if(preg_match_all("#\[!\XPATH\[.*\]\]#", $formatString, $xPATH_Matches, PREG_OFFSET_CAPTURE)) {
			foreach($xPATH_Matches[0] as $xPATH_Match) {
				$xPATH = mb_substr($xPATH_Match[0], 8, mb_strlen($xPATH_Match[0]) - 10);
				$entries = $this->xPath($xPATH);
				$value = '';
				$valueArray = array();
				for($dei=0; $dei<$entries->length; $dei++) {
					$entry = $entries->item($dei);
					$valueArray[] = (string)$entry->nodeValue;	
				}
				$value = implode('|', $valueArray);
				$formatString = str_replace($xPATH_Match[0], $value, $formatString);
			}
		}	
		return $formatString . 'u';
	}
	
    // KEYWORD locator
	// **********************************************************************
	function locateKeyword(
        $keyword, 
        $queryZones, 
        $Item='Word', 
        $resultTag='Result'
    ) {
		// **********************************************************************
		// Search keyword as format, append to keyword node
		// **********************************************************************
		$keyword->setAttribute("fullWord", 'false');
        $this->locateFormat($keyword, $queryZones, "Word", "KeywordResult");

		// **********************************************************************
		// Foreach keyword found, make search fields, append to keyword node
		// **********************************************************************
		$this->makeSearchFields($keyword);

		// **********************************************************************
		// Locate words
		// **********************************************************************
		$fields = $this->xPath(".//Fields/Field", $keyword);
		for($sf=0; $sf<$fields->length; $sf++) {
			$field = $fields->item($sf);
			$this->locateField($field, $queryZones, $Item);
		}

	}
	
    private function getKeywordQuery(
        $Word
    ) {
        $wordBox = $Word->getElementsByTagName('Box')->item(0);
        
    
    
    }
    
	function makeSearchFields($keyword) {
		$keywordResults = $this->xPath("./KeywordResults/KeywordResult", $keyword);
		for($kw=0; $kw<$keywordResults->length; $kw++) {
			$keywordResult = $keywordResults->item($kw);
			$fields = $this->createElement("Fields");
			$keywordResult->appendChild($fields);
            
			$refBox = $keywordResult->getBox();
			$ref_llx = $refBox->getAttribute("llx");
			$ref_lly = $refBox->getAttribute("lly");
			$ref_urx = $refBox->getAttribute("urx");
			$ref_ury = $refBox->getAttribute("ury");
			
			$direction = $keyword->getAttribute("direction");
			$dis_l = $this->convertUOM($keyword->getAttribute("left"));
			$dis_r = $this->convertUOM($keyword->getAttribute("right"));
			$dis_u = $this->convertUOM($keyword->getAttribute("up"));
			$dis_d = $this->convertUOM($keyword->getAttribute("down"));

			//echo "<pre>Making search box from llx=$ref_l, lly=$ref_d, urx=$ref_r, ury=$ref_u with modifiers left=$dis_l down=$dis_d right=$dis_r up=$dis_u</pre>";		
			switch($direction) {
			case 'down':
				$llx = $ref_llx + $dis_l; 
				$lly = $ref_lly + $dis_d; #
				$urx = $ref_urx + $dis_r;
				$ury = $ref_lly + $dis_u; #
				break;
			case 'right':
				$llx = $ref_urx + $dis_l; #
				$lly = $ref_lly + $dis_d;
				$urx = $ref_urx + $dis_r; #
				$ury = $ref_ury + $dis_u;
				break;
			case 'up':
				$llx = $ref_llx + $dis_l;
				$lly = $ref_ury + $dis_d; #
				$urx = $ref_urx + $dis_r; 
				$ury = $ref_ury + $dis_u; #
				break;
			case 'left':
				$llx = $ref_llx + $dis_l; #
				$lly = $ref_lly + $dis_d;
				$urx = $ref_llx + $dis_r; # 
				$ury = $ref_ury + $dis_u;
				break;
			}
			
			$field = $this->createElement("Field");
			$fields->appendChild($field);
            
			$field->setAttribute("x", $llx);
			$field->setAttribute("height", ($lly - $ury));
			$field->setAttribute("width", $urx - $llx);
			$field->setAttribute("y", $ury);
			$field->setAttribute("page", $refBox->getAttribute("page"));
			$field->setAttribute("granularity", $keyword->getAttribute("granularity"));
			$field->setAttribute("direction", $direction);
		}
	}
	
	function locateFormatting($formatting, $queryZones, $Item='Word', $resultTag='Result') {
		$resultsNode = $this->createElement("Results");
		$formatting->appendChuld($resultsNode);
		
        $query = $this->getFormattingQuery($formatting, $Item);
        
		for($lz=0; $lz<$queryZones->length; $lz++) {
			$queryZone = $queryZones->item($lz);
			$resultsList = $this->xPath($query, $queryZone);
            $this->createResults(
                $resultsList, 
                $resultsNode, 
                $formatting->getAttribute("granularity"), 
                $resultTag
            );
		}
		
	}
    
    function getFormattingQuery($formatting, $Item='Word') {
        // **********************************************************************
		// Use font style, family and size to get predicats
		// **********************************************************************
		$glyphPredicats = array();
     
        $fontResourcePredicats = array();
        $fontPredicats = array();
        
        $fontstyle = $formatting->getAttribute("fontstyle");
        $fontfamily = $formatting->getAttribute("fontfamily");
        if($fontstyle) $fontResourcePredicats[] = "contains(translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), '".strtolower($fontstyle)."')";
        if($fontfamily) $fontResourcePredicats[] = "contains(translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), '".strtolower($fontfamily)."')";
        if(count($fontResourcePredicats) > 0) {
            $fontIdsQuery = "//Zone[@name='document']/Pages/Resources/Fonts/Font[".implode(' and ', $fontResourcePredicats)."]/@id";
            $fontIds = $this->xPath($fontIdsQuery);
            //echo "<pre>Font id query $fontIdsQuery returned ".$fontIds->length."</pre>";
            for($fi=0; $fi<$fontIds->length; $fi++ ){
                $fontPredicats[] = "@font='".$fontIds->item($fi)->nodeValue."'";
            }
            $glyphPredicats[] = "(" . implode(' or ', $fontPredicats) . ")";
        }
        
        $fontsize = $formatting->getAttribute("fontsize");
        if($fontsize) $glyphPredicats[] = "(@size>".$fontsize.")";
        
        // **********************************************************************
		// Use predicats to get Query
		// **********************************************************************
		$query = './/'.$Item.'[./Box/Glyph[' . implode(' and ', $glyphPredicats) . ']]';
        //echo "<pre>Final formatting query = $query</pre>";
        return $query;
    }
	
	// **********************************************************************
	// EVALUATION functions
	// **********************************************************************
	function evaluate(
        $Evaluator,
        $Element
    ) {
    
    
    }
    
    function evaluateResults($evaluationMethods, $locatingResults, $locatingZones) {
        // **********************************************************************
		// Evaluate Formats
		// **********************************************************************
		$formats = $this->xPath("./Formats/Format", $evaluationMethods);
		for($fi=0;$fi<$formats->length;$fi++) {
			$this->evaluateFormat($formats->item($fi), $locatingResults);
		}
        
        // **********************************************************************
		// Evaluate KeyWords
		// **********************************************************************
		$keywords = $this->xPath("./Keywords/Keyword", $evaluationMethods);
		for($fi=0;$fi<$keywords->length;$fi++) {
			$this->evaluateKeyword($keywords->item($fi), $locatingZones, $locatingResults);
		}
    }
    
    function evaluateFormat($format, $locatingResults) {			
        // **********************************************************************
        // Generate Format Expression including dynamic values
        // **********************************************************************
        $searchFormat = $this->getFormatString((string)$format->nodeValue);
    
		for($lr=0; $lr<$locatingResults->length; $lr++) {
            $query = ".//Result[php:function('preg_match', '".$searchFormat."', string(./Text)) > 0]";
            $resultsList = $this->xPath($query, $locatingResults->item($lr));
            //echo "<pre>Format evaluator Query $query found ".(int)$resultsList->length." results</pre>";
            // Locator Results found with keyword 
            for($ri=0; $ri<$resultsList->length; $ri++) {
				$result = $resultsList->item($ri);
                $formatResultRef = $format->cloneNode(true);
                $result->appendChild($formatResultRef);
			}
        }
	}
    
    function evaluateKeyword($keyword, $queryZones, $locatingResults) {
		// **********************************************************************
		// Search keyword as format, append to keyword node
		// **********************************************************************
		$keyword->setAttribute("fullWord", 'false');
		$this->locateFormat($keyword, $queryZones, "Word", "KeywordResult");
				
		// **********************************************************************
		// Foreach keyword found, make search fields, append to keyword node
		// **********************************************************************
		$this->makeSearchFields($keyword);
		
		// **********************************************************************
		// Search in locator results
		// **********************************************************************
        $fields = $this->xPath(".//Fields/Field", $keyword);
		for($sf=0; $sf<$fields->length; $sf++) {
			$field = $fields->item($sf);
			$query = $this->getFieldQuery($field, $Item='Result');
			for($li=0; $li<$locatingResults->length; $li++) {
				$resultsList = $this->xPath($query, $locatingResults->item($li));
				// Locator Results found with keyword 
                for($ri=0; $ri<$resultsList->length; $ri++) {
					$result = $resultsList->item($ri);
                    $keywordResultRef = $field->parentNode->parentNode->cloneNode();
                    $confidence = (integer)$keyword->getAttribute("confidence");
                    if($confidence == 0) $confidence = 100;
                    $keywordResultRef->setAttribute("confidence", $confidence);
                    $result->appendChild($keywordResultRef);
					/*$oldConfidence = $result->getAttribute("confidence");
					$newConfidence = $oldConfidence + $keyword->getAttribute("confidence");
					$result->setAttribute("confidence", "kw");*/
				}
			}
		}
	}
	
    // **********************************************************************
	// BOX functions
	// **********************************************************************
	private function inflate(
        $Box,
        $value,
        $direction = false
    ) {       
        if(!$direction || $direction == 'left') 
            $Box->setAttribute(
                'llx',
                $Box->getAttribute('llx') - $value
            );
            
        if(!$direction || $direction == 'down')
            $Box->setAttribute(
                'lly',
                $Box->getAttribute('lly') + $value
            );
            
        if(!$direction || $direction == 'right')
            $Box->setAttribute(
                'urx',
                $Box->getAttribute('urx') + $value
            );
            
        if(!$direction || $direction == 'top')
            $Box->setAttribute(
                'ury',
                $Box->getAttribute('ury') - $value
            );
    }
    
    private function deflate(
        $Box,
        $value,
        $direction = false
    ) {       
        if(!$direction || $direction == 'left') 
            $Box->setAttribute(
                'llx',
                $Box->getAttribute('llx') + $value
            ); 
            
        if(!$direction || $direction == 'down')
            $Box->setAttribute(
                'lly',
                $Box->getAttribute('lly') - $value
            );
            
        if(!$direction || $direction == 'right')
            $Box->setAttribute(
                'urx',
                $Box->getAttribute('urx') - $value
            );
            
        if(!$direction || $direction == 'top')
            $Box->setAttribute(
                'ury',
                $Box->getAttribute('ury') + $value
            );
    }
    
	// **********************************************************************
	// LOCATOR RESULTS functions
	// **********************************************************************
	function createResults(
        $WordList, 
        $Caller
    ) {		
        $granularity = $Caller->getAttribute('granularity');
        echo PHP_EOL . "Granularity from " . $Caller->nodeName . "->" . $granularity . PHP_EOL;
        $l = $WordList->length;
        $Result = false;
        for($i=0;$i<$l;$i++) {
			$Word = $WordList->item($i);
            $previousWord = $WordList->item($i-1);
            $merge = false;
            if($granularity && $Result) {
                switch ($granularity) {
                case 'Word':
                    $merge = false;
                    break;
                    
                case 'All':
                    $merge = true;
                    break;
                    
                default:
                    $Ancestor = $Word->ownerDocument->query('./ancestor::'.$granularity, $Word)->item(0);
                    $previousAncestor = $Word->ownerDocument->query('./ancestor::'.$granularity, $previousWord)->item(0);
                    if($Ancestor->getNodePath() == $previousAncestor->getNodePath())
                        $merge = true;
                }	
            }
            
            if($merge)
                $this->mergeResult(
                    $Word, 
                    $Result
                );
            else
                $Result = 
                    $this->createResult(
                        $Caller,
                        $Word
                    );            
		}
	
	}
	
	function createResult(
        $Caller,
        $Word = false
    ) { 
        $Result = $this->Batch->createElement('Result');
        $Text = $this->Batch->createElement("Text");
        $Result->appendChild($Text);
		$Box = $this->Batch->createElement("Box");
        $Result->appendChild($Box);
        
        if($Word) {
            $Result->setAttribute('id', $Word->getAttribute('id'));
            $Text->nodeValue = $Word->getText();
            $WordBox = $Word->getBox();
            $Box->llx = $WordBox->llx;
            $Box->lly = $WordBox->lly;
            $Box->urx = $WordBox->urx;
            $Box->ury = $WordBox->ury;
            $Box->page = $WordBox->page;
		} 
        $Caller->appendChild($Result);
		//$Result->setAttribute("confidence", 100);
		return $Result;
	}
		
	private function mergeResult(
        $Word, 
        $Result 
    ) {
		$this->mergeText($Word, $Result);
        $this->mergeId($Word, $Result);
		$this->mergeBox($Word, $Result);
	}
	
	private function mergeText(
        $Word, 
        $Result
    ) {
        $Text = $Result->getElementsByTagName("Text")->item(0);
		if($Text->nodeValue) {
			$Text->nodeValue .= ' ';
		}
        $newText = $Word->getElementsByTagName("Text")->item(0)->nodeValue;
		$Text->nodeValue .= htmlentities($newText);
    }
    
    function mergeId(
        $Word, 
        $Result
    ) {
		if($Result->hasAttribute('id'))
            $Result->setAttribute(
                "id", 
                $Result->getAttribute("id") . ' '
            );
        
        $Result->setAttribute(
            "id", 
            $Result->getAttribute("id") . $Word->getAttribute("id")
        );
	}
	
	function mergeBox(
        $Word, 
        $Result
    ) {
		$WordBox = $Word->getElementsByTagName("Box")->item(0);
		$ResultBox = $Result->getElementsByTagName("Box")->item(0);
	
		if(!$ResultBox->getAttribute("llx") || $WordBox->getAttribute("llx") < $ResultBox->getAttribute("llx")) 
            $ResultBox->setAttribute("llx", $WordBox->getAttribute("llx"));
		if(!$ResultBox->getAttribute("lly") || $WordBox->getAttribute("lly") < $ResultBox->getAttribute("lly")) 
            $ResultBox->setAttribute("lly", $WordBox->getAttribute("lly"));
		if(!$ResultBox->getAttribute("urx") || $WordBox->getAttribute("urx") > $ResultBox->getAttribute("urx")) 
            $ResultBox->setAttribute("urx", $WordBox->getAttribute("urx"));
		if(!$ResultBox->getAttribute("ury") || $WordBox->getAttribute("ury") > $ResultBox->getAttribute("ury")) 
            $ResultBox->setAttribute("ury", $WordBox->getAttribute("ury"));
		if(!$ResultBox->getAttribute("page")) $ResultBox->setAttribute("page", $WordBox->getAttribute("page"));
		
		/*$Glyphs = $WordBox->getElementsByTagName('Glyph');	
        $l = $Glyphs->length;
		for($i=0; $i<$l; $i++) {
            $ResultGlyph = $Glyphs->item($i)->cloneNode(true);
			$ResultBox->appendChild($ResultGlyph);
		}*/
		
	}
	
	// **********************************************************************
	// Metadata functions
	// **********************************************************************
	function export(
        $Export,
        $Element
    ) {
        $ExportName = $Export->getAttribute('name');
        //$_SESSION['capture']->logEvent("Exporting " . $ExportName . "...");
        
        // Create Index results 
        $ExportResult = false;     
        if($LocatorName = $Export->getAttribute('locator')) {
            $Result = 
                $this->Batch->query(
                    './Reader/Template/Locators/Locator[@name="'.$LocatorName.'"]//Result',
                    $Element
                )->item(0);
            if($Result)
                $ExportResult = $Result->cloneNode(true);
        }
        else if($EvaluatorName = $Export->getAttribute('evaluator')) {
            $Result = 
                $this->Batch->query(
                    './Reader/Template/Evaluators/Evaluator[@name="'.$EvaluatorName.'"]//Result',
                    $Element
                )->item(0);
            if($Result)
                $ExportResult = $Result->cloneNode(true);
        }
        
        if(!$ExportResult) {
            $ExportResult = $this->createResult($Export);
            if($Export->hasAttribute('default'))
                $ExportResult->setText(
                    $Export->getAttribute('default')
                );
            if($Export->nodeValue)
                $ExportResult->setText(
                    $Export->nodeValue
                );
        }
    
        $Export->appendChild($ExportResult);
 
        // Format value if requested
        if($formatterName = (string)$Export->getAttribute("formatter")) {
            $Formatter = 
                $this->query(
                    "/Reader/Formatters/Formatter[@name='".$formatterName."']"
                )->item(0);
            if(!$Formatter) {
                $_SESSION['capture']->logEvent(
                    "Formatter '$formatterName' is not defined", 2
                );
                trigger_error(
                    "Formatter '$formatterName' is not defined",
                    E_USER_ERROR
                );
            } else {
                $this->formatResult(
                    $Formatter, 
                    $ExportResult
                ); 
            }
        }
        
        // Export value to document
        $Metadata = 
            $Element->setMetadata(
                $ExportName,
                $ExportResult->getText()
            );
	}
	
	function formatResult(
        $Formatter, 
        $Result
    ) {
		$resultText = $Result->getElementsByTagName('Text')->item(0);
		$formattedText = 
            call_user_func(
                $Formatter->getAttribute("func"),
                (string)$resultText->nodeValue, 
                $this
            );
		$resultText->nodeValue = $formattedText;
		if($formattedText == '' || !$formattedText) {
			$Result->setAttribute("confidence", "0");
		}
	}
    
    function translateWithDict(
        $inputValue, $dictionary
    ) {
        $outputNode = $this->query("//Dictionary[@name='".$dictionary."']/Entry[./Input='".$inputValue."']/Output")->item(0);
        if($outputNode) return $outputNode->nodeValue;
        else return false;        
    }
    
}


?>