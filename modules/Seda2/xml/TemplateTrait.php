<?php
/*
 * Copyright (C) 2015 Maarch
 *
 * This file is part of dependency xml.
 *
 * Dependency xml is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Dependency xml is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with dependency xml.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace xml;

trait TemplateTrait
{
    use TemplateDataTrait,
        TemplateParserTrait;

    /* Properties */
    protected $parsedPis = array();

    protected $parsedTexts = array();

    protected $mergedNodes;

    protected $mergedForms;

    /* Methods */
    /**
     * Template trait
     *
     */
    public function templateTrait()
    {
        $this->mergedNodes = new \SplObjectStorage();

        $this->mergedForms = new \SplObjectStorage();

        $this->bindVariable("_SESSION", $_SESSION);
    }


    /* -------------------------------------------------------------------------
    - MERGE processing instructions
    ------------------------------------------------------------------------- */
    /**
     * merge
     * @param string $node
     * @param string $source
     *
     */
    public function merge($node = null, $source = null)
    {
        // Avoid garbage nodes merge
        if (!isset($this->mergedNodes)) {
            $this->mergedNodes = new \SplObjectStorage();
        }

        if (!isset($this->mergedForms)) {
            $this->mergedForms = new \SplObjectStorage();
        }
        
        if ($node && $this->mergedNodes->contains($node)) {
            return;
        }

        $mergeNodes = $this->XPath->query("descendant-or-self::processing-instruction('merge') | descendant-or-self::text()[contains(., '[?merge')] | descendant-or-self::*/@*[contains(., '[?merge')]", $node);

        $this->mergedObjects = array();

        foreach ($mergeNodes as $i => $mergeNode) {
            switch ($mergeNode->nodeType) {
                case XML_PI_NODE :
                    if (!isset($this->parsedPis[$mergeNode->data])) {
                        $this->parsedPis[$mergeNode->data] = $this->parse($mergeNode->data);
                    }
                    $instr = $this->parsedPis[$mergeNode->data];

                    if ($merged = $this->mergePi($mergeNode, $instr, $source)) {
                        if ($mergeNode->parentNode) {
                            $mergeNode->parentNode->removeChild($mergeNode);
                        }
                    }
                    break;

                case XML_TEXT_NODE:
                case XML_ELEMENT_NODE:
                case XML_ATTRIBUTE_NODE:
                default:
                    $this->mergeTextNode($mergeNode, $source);
            }
        }

        /*$this->mergePis($node, $source);

        $this->mergeTextNodes($node, $source);*/

        $this->mergeForms();

        if ($node) {
            $this->mergedNodes->attach($node);
        }
    }

    /*protected function mergePis($node=null, $source=null)
    {
        $pis = $this->XPath->query("descendant-or-self::processing-instruction('merge')", $node);

        $this->mergedObjects = array();

        foreach ($pis as $i => $pi) {
            if (!isset($this->parsedPis[$pi->data])) {
                $this->parsedPis[$pi->data] = $this->parse($pi->data);
            }

            if ($merged = $this->mergePi($pi, $this->parsedPis[$pi->data], $source)) {
                $pi->parentNode->removeChild($pi);
            }
        }
    }*/

    protected function mergePi($pi, $instr, $source=null)
    {
        // Get value by reference
        $value = &$this->getData($instr, $source);

        // Use value with selected target
        if (isset($instr->params['var'])) {
            $this->addVar($instr->params['var'], $value);
            
            $pi->parentNode->removeChild($pi);

            return false;
        }

        // Get type of value
        $type = gettype($value);
        //var_dump($type);
        //if (isset($instr->params['source']))
        //    var_dump($instr->params['source']);

        switch(true) {
            // If value is scalar, merge text before Pi
            case $type == 'string':
            case $type == 'integer':
            case $type == 'double':
                return $this->mergeText($pi, $instr, $value);

            // Value is bool, remove target sibling if false
            case $type == 'boolean':
                return $this->mergeBool($pi, $instr, $value);

            // Value is null, no action
            case $type == 'NULL':
                return true;

            // Value is array, merge target by iterating over array
            case $type == 'array':
                return $this->mergeArray($pi, $instr, $value);

            case $type == 'object' :
                switch (true) {
                    // Object merged with a form
                    case ($targetForm = $this->XPath->query("following-sibling::form", $pi)->item(0)) :
                        $this->mergedForms->attach($targetForm, array($pi, $instr, $value));
                        break;

                    // ArrayObject -> merge array
                    case ($value instanceof \ArrayAccess && $value instanceof \Iterator) :
                        return $this->mergeArray($pi, $instr, $value);

                    // DOMNode -> merge as xml
                    case $value instanceof \DOMNode :
                        return $this->mergeNode($pi, $instr, $value);

                    case $value instanceof \core\Type\Date:
                        return $this->mergeText($pi, $instr, $this->dateTimeFormatter->formatDate($value));

                    case $value instanceof \core\Type\Timestamp:
                        return $this->mergeText($pi, $instr, $this->dateTimeFormatter->formatTimestamp($value));

                    case $value instanceof \core\Type\DateTime:
                        return $this->mergeText($pi, $instr, $this->dateTimeFormatter->formatDateTime($value));

                    // If value is an object but no form : merge string version if possible
                    case method_exists($value, '__toString'):
                        return $this->mergeText($pi, $instr, (string) $value);
                }
        }
    }

    protected function mergeForms()
    {
        $this->mergedForms->rewind();
        while ($this->mergedForms->valid()) {
            $index  = $this->mergedForms->key();
            $targetForm = $this->mergedForms->current();
            list($pi, $instr, $object) = $this->mergedForms->getInfo();

            $params = $instr->params;

            if (isset($params['source'])) {
                $this->setSource($params['source'], $object);
            }

            $this->mergeObjectProperties($targetForm, $object, $params, $oname = false);

            //$pi->parentNode->removeChild($pi);

            $this->mergedForms->next();
        }
    }

    protected function mergeTextNodes($node=null, $source=null)
    {
        $textNodes = $this->XPath->query("descendant-or-self::text()[contains(., '[?merge')] | descendant-or-self::*/@*[contains(., '[?merge')]", $node);

        for ($i=0, $l=$textNodes->length; $i<$l; $i++) {
            $textNode = $textNodes->item($i);
            $this->mergeTextNode($textNode, $source);
        }
    }

    protected function mergeTextNode($textNode, $source=null)
    {
        //$nodeXml = $this->saveXml($textNode);
        $nodeValue = $textNode->nodeValue;
        if (isset($this->parsedTexts[$nodeValue])) {
            $instructions = $this->parsedTexts[$nodeValue];
        } else {
            preg_match_all("#(?<pi>\[\?merge (?<instr>(?:(?!\?\]).)*)\?\])#", $nodeValue, $pis, PREG_SET_ORDER);
            $instructions = array();
            foreach ($pis as $i => $pi) {
                $instructions[$pi['pi']] = $this->parse($pi['instr']);
            }
            $this->parsedTexts[$nodeValue] = $instructions;
        }

        $values = [];
        foreach ($instructions as $pi => $instr) {
            $value = $this->getData($instr, $source);
            if (is_scalar($value) || is_null($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $tmpTextNode = $this->createTextNode($value);
                $mergedValue = (string) $tmpTextNode->wholeText;

                if (empty($this->xmlVersion)) {
                    $mergedValue = htmlentities($mergedValue);
                }
                
                $values[] = $mergedValue;
            }
        }

        $textNode->nodeValue = str_replace(array_keys($instructions), $values, $textNode->nodeValue);

        // If text is in an attribute that is empty, remove attribute
        if ($textNode->nodeType == XML_ATTRIBUTE_NODE && empty($textNode->value)) {
            $textNode->parentNode->removeAttribute($textNode->name);
        }
    }

    protected function mergeText($pi, $instr, $value)
    {
        $params = $instr->params;
        switch(true) {
        case isset($params['attr']):
            if (!$targetNode = $this->XPath->query("following-sibling::*", $pi)->item(0)) {
                return true;
            }
            $targetNode->setAttribute($params['attr'], $value);
            break;

        case isset($params['render']):
            if (!$params['render']) {
                $fragment = $value;
            } else { 
                $fragment = $params['render'];
            }
            if (!isset($this->fragments[$fragment])) {
                return true;
            }
            $targetNode = $this->fragments[$fragment]->cloneNode(true);
            $this->merge($targetNode);
            $pi->parentNode->insertBefore($targetNode, $pi);
            break;

        default:
            $targetNode = $this->createTextNode($value);
            $pi->parentNode->insertBefore($targetNode, $pi);
        }

        return true;
    }

    protected function mergeArray($pi, $instr, &$array)
    {
        $params = $instr->params;

        if (isset($params['include'])) {
            $res = $params['include'];
            $targetNode = $this->createDocumentFragment();

            if (pathinfo($res, PATHINFO_EXTENSION) == 'xml') {
                $targetNode->appendFile($res);
            } else {
                $targetNode->appendHtmlFile($res);
            }

        } elseif (!$targetNode = $this->XPath->query("following-sibling::*", $pi)->item(0)) {
            return true;
        }

        reset($array);
        if ($count = count($array)) {
            $i=0;
            while ($i<$count) {
            //do {
                $itemNode = $targetNode->cloneNode(true);
                $itemData = current($array);
                if (isset($params['source'])) {
                    $this->setSource($params['source'], $itemData);
                }

                $this->merge($itemNode, $itemData);
                $pi->parentNode->insertBefore($itemNode, $pi);

                @next($array);
                $i++;
            /*} while (
                @next($array) !== false
            );*/
            }
        }
        // Remove targetNode (row template)
        if ($targetNode->parentNode) {
            $targetNode = $targetNode->parentNode->removeChild($targetNode);
        }

        // Add to mergedNodes to prevent other calls to merge
        $this->mergedNodes->attach($targetNode);

        return true;
    }

    protected function mergeObject($pi, $instr, $object)
    {
        $params = $instr->params;
        if (!$targetNode = $this->XPath->query("following-sibling::*", $pi)->item(0)) {
            return true;
        }

        if (isset($params['source'])) {
            $this->setSource($params['source'], $object);
        }

        $this->mergeObjectProperties($targetNode, $object, $params, $oname = false);

        return true;
    }

    protected function mergeObjectProperties($targetNode, $object, $params, $oname=false)
    {
        foreach ($object as $pname => $pvalue) {
            if ($oname) {
                $pname = $oname . "." . $pname;
            }
            //var_dump("merge $pname");
            if (\laabs::isScalar($pvalue)) {
                $this->mergeObjectProperty($targetNode, $pvalue, $params, $pname);
            }
            /*elseif (is_object($pvalue))
                $this->mergeObjectProperties($targetNode, $pvalue, $params, $pname);
            elseif (is_array($pvalue))
                foreach ($pvalue as $key => $item)
                    $this->mergeObjectProperties($targetNode, $pvalue, $params, $pname . "[$key]");*/
        }
    }

    protected function mergeObjectProperty($targetNode, $value, $params, $name)
    {
        $elements = $this->XPath->query("descendant-or-self::*[@name='$name']", $targetNode);
        for ($i=0, $l=$elements->length; $i<$l; $i++) {
            $element = $elements->item($i);
            switch (strtolower($element->nodeName)) {
                // Form Input
                case 'input':
                    switch($element->getAttribute('type')) {
                        case 'checkbox':
                            if (is_bool($value)) {
                                if ($value) {
                                    $element->setAttribute('checked', 'true');
                                } else {
                                    $element->removeAttribute('checked');
                                }
                            } else {
                                if ($element->getAttribute('value') == $value) {
                                    $element->setAttribute('checked', 'true');
                                } else {
                                    $element->removeAttribute('checked');
                                }
                            }
                            
                            break;

                        case 'radio':
                            if ($element->getAttribute('value') == $value) {
                                $element->setAttribute('checked', 'true');
                            } else {
                                $element->removeAttribute('checked');
                            }
                            break;

                        default:
                            $element->setAttribute('value', $value);
                    }
                    break;

                // Select
                case 'select':
                    $value = $this->XPath->quote($value);
                    if ($option = $this->XPath->query(".//option[@value=".$value."]", $element)->item(0)) {
                        $option->setAttribute('selected', 'true');
                        if ($optGroup = $this->XPath->query("parent::optgroup", $option)->item(0)) {
                            $optGroup->removeAttribute('disabled');
                        }
                    }
                    break;

                // Textareas
                case 'textarea':
                    $element->nodeValue = $value;
                    break;
            }
        }
    }

    /**
     * Merge a boolean
     * @param DOMNode $pi
     * @param string  $instr
     * @param boolean $bool
     *
     */
    public function mergeBool($pi, $instr, $bool)
    { 
        $params = $instr->params;
        if (isset($params['include'])) {
            $res = $params['include'];
            $targetNode = $this->createDocumentFragment();
        } elseif (!$targetNode = $this->XPath->query("following-sibling::*", $pi)->item(0)) {
            return true;
        }

        if (isset($params['attr'])) {
            if ($bool == false) {
                $targetNode->removeAttribute($params['attr']);
            } else {
                $targetNode->setAttribute($params['attr'], $params['attr']);
            }
        } else {
            if (isset($params['source'])) {
                $this->setSource($params['source'], $bool);
            }
            if ($bool == false) {
                if ($targetNode->parentNode) {
                    $parentNode = $targetNode->parentNode;
                    $targetNode = $parentNode->removeChild($targetNode);
                }

                // Add to mergedNodes to prevent other calls to merge
                $this->mergedNodes->attach($targetNode);
            }
        }

        return true;
    }

    /**
     * Merge a node
     * @param DOMNode $pi
     * @param string  $instr
     * @param DOMNode $DOMNode
     *
     */
    public function mergeNode($pi, $instr, $DOMNode)
    {
        if ($DOMNode->nodeType == XML_DOCUMENT_FRAG_NODE && $DOMNode->childNodes->length == 0) {
            return true;
        }

        if ($pi->ownerDocument != $DOMNode->ownerDocument) {
            $DOMNode = $pi->ownerDocument->importNode($DOMNode, true);
        }

        $pi->parentNode->replaceChild($DOMNode, $pi);
        
        return true;
    }

}