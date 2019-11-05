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

class Parser
{
    /**
     * The reader for Xml parsing.
     * @var XMLReader
     */
    public $reader;

    /**
     * The document object.
     * @var DOMDocument
     */
    protected $document;

    /**
     * The current parsing element.
     * @var DOMElement
     */
    protected $currentElement;


    protected $errors;

    /* Methods */
    /**
     * Parse Xml Data from string.
     *
     * @see \XMLReader::XML()
     *
     * @param string $source String containing the XML to be parsed.
     * @param object $parentElement Parent Element to append parsed XML to
     * @param string $encoding The document encoding or NULL.
     * @param string $options A bitmask of the LIBXML_* constants.
     * @return DOMDocument The created DOM tree.
     */
    public function parseString($source, $parentElement, $encoding = null, $options = 0)
    {
        $this->reader = new \XMLReader();
        $this->reader->XML($source, $encoding, $options);

        $this->currentElement = $parentElement;

        $this->parse();
    }

    /**
     * Parse Xml Data from file to new document.
     *
     * @see \XMLReader::open()
     *
     * @param string $uri URI pointing to the document.
     * @param object $document Empty document to import Xml
     * @param string $encoding The document encoding or NULL.
     * @param string $options A bitmask of the LIBXML_* constants.
     * @return DOMDocument The created DOM tree.
     */
    public function parseFile($uri, $parentElement, $encoding=null, $options = 0)
    {
        $this->reader = new \XMLReader();
        $this->reader->open($uri, $encoding, $options);

        $this->currentElement = $parentElement;

        $this->parse();
    }

    /**
     * The parser.
     *
     * @return DOMDocument The created DOM tree.
     */
    protected function parse()
    {
        libxml_use_internal_errors(true);

        switch($this->currentElement->nodeType) {
            case \XML_DOCUMENT_NODE :
                $this->document = $this->currentElement;
                break;
            case \XML_ELEMENT_NODE:
            case \XML_DOCUMENT_FRAG_NODE:
                $this->document = $this->currentElement->ownerDocument;
                break;
        }

        while ($this->reader->read()) {
            switch ($this->reader->nodeType) {
                case \XMLReader::ELEMENT: // 1
                    if ($this->reader->isEmptyElement) $this->addElement();
                    else $this->currentElement = $this->addElement();
                    break;

                case \XMLReader::ATTRIBUTE: // 2
                    // Parsed with owner element
                    break;

                case \XMLReader::TEXT: // 3
                    $this->currentElement->appendChild(
                        $this->document->createTextNode($this->reader->value)
                    );
                    break;

                case \XMLReader::CDATA: // 4
                    $this->currentElement->appendChild(
                        $this->document->createCDATASection($this->reader->value)
                    );
                    break;

                case \XMLReader::ENTITY_REF: // 5
                    $this->currentElement->appendChild(
                        $this->document->createEntityReference($this->reader->name)
                    );
                    break;

                case \XMLReader::ENTITY: // 6
                    // TO DO ?
                    break;

                case \XMLReader::PI: //7
                    $this->currentElement->appendChild(
                        $this->document->createProcessingInstruction($this->reader->name, $this->reader->value)
                    );
                    break;

                case \XMLReader::COMMENT: //8
                    $this->currentElement->appendChild(
                        $this->document->createComment($this->reader->value)
                    );
                    break;

                case \XMLReader::DOC: //9
                    // N/A
                    break;

                case \XMLReader::DOC_TYPE: //10
                    // TO DO ?
                    break;

                case \XMLReader::DOC_FRAGMENT: //11
                    // TO DO ?
                    break;

                case \XMLReader::NOTATION: //12
                    // TO DO ?
                    break;

                case \XMLReader::WHITESPACE: //13
                    // TO DO ?
                    break;

                case \XMLReader::SIGNIFICANT_WHITESPACE: // 14
                    $this->currentElement->appendChild(
                        $this->document->createTextNode($this->reader->value)
                    );
                    break;

                case \XMLReader::END_ELEMENT: //15
                    $this->currentElement = $this->currentElement->parentNode;
                    break;

                case \XMLReader::END_ENTITY: //16
                    // TO DO ?
                    break;

                case \XMLReader::XML_DECLARATION: //17
                    // TO DO ?
                    break;

            }
        }
        $this->reader->close();

        $this->errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if (!count($this->errors)) return true;

    }

    protected function get_errors() {
        return $this->errors;
    }

    protected function _display_error($error) {
        $return = "<br/>";

        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $error->code: ";
                break;
             case LIBXML_ERR_ERROR:
                $return .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $error->code: ";
                break;
        }

        $return .= trim($error->message);

        if ($error->file)
            $return .= "\n<br/>  File: $error->file";

        $return .=
                   "\n<br/>  Line: $error->line" .
                   "\n<br/>  Column: $error->column";

        if ($this->reader->nodeType)
            $return .= "\n<br/>  Node type: " . $this->reader->nodeType;

        if ($this->reader->name)
            $return .= "\n<br/>  Node name: " . $this->reader->name;

        if ($this->reader->value)
            $return .= "\n<br/>  Node value: ". $this->reader->value;

        echo $return;
    }

    protected function addElement() {
        if ($this->reader->namespaceURI)
            $element = $this->document->createElementNS($this->reader->namespaceURI, $this->reader->name);
        else
            $element = $this->document->createElement($this->reader->name);       /* Have document creating element */
        $this->currentElement->appendChild($element);                              /* Append to current element */
        while ($this->reader->moveToNextAttribute())                               /* Add attributes */
            $this->addAttribute($element);
        return $element;
    }

    protected function addAttribute($element) {
        if ($this->reader->namespaceURI)
            $element->setAttributeNS($this->reader->namespaceURI, $this->reader->name, $this->reader->value);
        else
            $element->setAttribute($this->reader->name, $this->reader->value);
    }

}