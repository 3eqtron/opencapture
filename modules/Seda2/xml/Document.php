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
/**
 * The XML Document service extends DOMDocument to provide additionnal features
 *
 * @package Dependency_Xml
 */
class Document
    extends \DOMDocument
{
    use IncludeTrait,
        TemplateTrait;

    /* Constants */
    /* -------------------------------------------------------------------------
    - Properties
    ------------------------------------------------------------------------- */
    protected $extensions;
    public $XPath;
    /* -------------------------------------------------------------------------
    - Methods
    ------------------------------------------------------------------------- */
    /* -------------------------------------------------------------------------
    - DOMDocument methods overrides
    ------------------------------------------------------------------------- */
    /**
     * Constuctor of document
     * @param string $version
     * @param string $encoding
     * @param string $extensions
     */
    public function __construct($version="1.0", $encoding=null, $extensions=null)
    {
        parent::__construct($version, $encoding);
        $this->registerNodeClass('DOMElement', '\xml\Element');
        $this->registerNodeClass('DOMAttr', '\xml\Attr');
        $this->registerNodeClass('DOMText', '\xml\Text');
        $this->registerNodeClass('DOMComment', '\xml\Comment');
        $this->registerNodeClass('DOMCdataSection', '\xml\CdataSection');
        $this->registerNodeClass('DOMEntityReference', '\xml\EntityReference');
        $this->registerNodeClass('DOMProcessingInstruction', '\xml\ProcessingInstruction');
        $this->registerNodeClass('DOMDocument', '\xml\Document');
        $this->registerNodeClass('DOMDocumentType', '\xml\DocumentType');
        $this->registerNodeClass('DOMDocumentFragment', '\xml\DocumentFragment');
        $this->registerNodeClass('DOMNotation', '\xml\Notation');
        $this->nodeExtensions = $extensions;
        $this->preserveWhiteSpace = false;
        $this->formatOutput = true;

        $this->templateTrait();
        
    }
    
    /* -------------------------------------------------------------------------
    - Xml\Document methods
    ------------------------------------------------------------------------- */
    /**
     * Get the node extensions
     * @param DOMNode $node
     * 
     * @return array
     */
    public function getNodeExtension($node)
    {
        switch($node->nodeType) {
            case \XML_ELEMENT_NODE:
                $type = 'ELEMENT';
                $name = $node->nodeName;
                break;
            case \XML_ATTRIBUTE_NODE:
                $type = 'ATTRIBUTE';
                $name = $node->name;
                break;
            case \XML_PI_NODE:
                $type = 'PI';
                $name = $node->target;
                break;
            default:
                return;
        }

        if (isset($this->nodeExtensions[$type][$name])) {
            return $this->nodeExtensions[$type][$name];
        }
    }

    /**
     * Load a ressource
     * @param string $uri
     */
    public function loadResource($uri)
    {
        $router = new \core\Route\ResourceRouter($uri);
        $path = $router->getResource()->getRealPath();

        $this->load($path);

        $this->XPath = new XPath($this);

        $this->xinclude();
    }

    /**
     * Transform DOMDocument into PHP object
     * @param string $classname
     * 
     * @return object
     */
    public function export($classname)
    {
        return $this->exportElement($this->documentElement, $classname);
    }

    protected function exportElement($element, $class)
    {
        if (is_string($class)) {
            $class = \laabs::getClass($class);
        }

        if (isset($class->tags['xmlns'])) {
            foreach ($class->tags['xmlns'] as $xmlnsDecl) {
                $prefix = strtok($xmlnsDecl, " ");
                $xmlns = trim(strtok(""));
                $this->XPath->registerNamespace($prefix, $xmlns);
            }
        }

        $object = $class->newInstanceWithoutConstructor();

        foreach ($class->getProperties() as $property) {
            $property->setAccessible(true);

            $value = null;
            if (isset($property->tags['xvalue'])) {
                switch ($property->tags['xvalue'][0]) {
                    case 'generate-id':
                        $value = (string) \laabs::newId();
                        break;

                    case 'local-name':
                        $value = $element->localName;
                        break;

                    case 'node-path':
                        $value = $element->getNodePath();
                        break;
                }

            } elseif (isset($property->tags['xpath'])) {
                $nodes = $this->XPath->query($property->tags['xpath'][0], $element);

                if ($nodes->length == 0) {
                    continue;
                }

                $type = $property->getType();
                switch (true) {
                    // Scalar property type
                    case $property->isScalar():
                        $node = $nodes->item(0);
                        switch ($type) {
                            case 'xstring':
                                $value = \laabs::cast($node, $type);
                                break;

                            default:
                                $value = \laabs::cast($node->nodeValue, $type);
                                break;
                        }
                        break;

                    // array or classname[]
                    case $property->isArray():
                        $value = array();
                        switch (true) {
                            case (strpos($type, LAABS_URI_SEPARATOR) !== false):
                                $classname = substr($type, 0, -2);

                                foreach ($nodes as $node) {
                                    if ($node->hasAttribute('xml:id')) {
                                        $value[$node->getAttribute('xml:id')] = $this->exportElement($node, $classname);
                                    } else {
                                        $value[] = $this->exportElement($node, $classname);
                                    }
                                }
                                break;

                            case ($type == 'array'):
                                foreach ($nodes as $node) {
                                    $value[] = $node->nodeValue;
                                } 
                                break;

                            case ($type == 'object'):
                                foreach ($nodes as $node) {
                                    $xmlns = $node->namespaceURI;
                                    if (!$bundle = \laabs::resolveXmlNamespace($xmlns)) {
                                        throw new \Exception('Unknown description namespace '.$xmlns);
                                    }
                                    $class = $bundle . '/' . $node->localName;
                                    $value[] = $this->exportElement($node, $class);
                                }
                                break;

                            default:
                                $typename = substr($type, 0, -2);
                                switch ($typename) {
                                    case 'xstring':
                                        foreach ($nodes as $node) {
                                            $value[] = \laabs::cast($node, $typename);
                                        }
                                        break;

                                    default:
                                        foreach ($nodes as $node) {
                                            $value[] = \laabs::cast($node->nodeValue, $typename);
                                        }
                                        break;
                                }
                        }
                        break;

                    // classname
                    case (strpos($type, LAABS_URI_SEPARATOR) !== false):
                        $value = $this->exportElement($nodes->item(0), $type);
                        break;

                    // undefined class
                    case $type == 'object':
                    default:
                        $node = $nodes->item(0);
                        $xmlns = $node->namespaceURI;
                        if (!$bundle = \laabs::resolveXmlNamespace($xmlns)) {
                            throw new \Exception('Unknown description namespace '.$xmlns);
                        }
                        $class = $bundle . '/' . $node->localName;
                        $value = $this->exportElement($node, $class);
                        break;

                    // Undefined
                    //default:
                }
            }

            $property->setValue($object, $value);
        }

        return $object;
    }

    /**
     * Remove empty elements and attributes
     * @param DOMNode $node
     */
    public function removeEmptyNodes($node=null)
    {       
        if (!$node) {
            $node = $this->documentElement;
        }       
        
        switch($node->nodeType) {
            case \XML_ELEMENT_NODE:
                $childNodeList = $node->childNodes;
                for ($i=$childNodeList->length-1; $i>=0; $i--) {
                    $this->removeEmptyNodes($childNodeList->item($i));
                }

                $childNodeList = $node->childNodes;
                if ($childNodeList->length == 0 && !$node->hasAttributes()) {
                    $node->parentNode->removeChild($node);
                }
                break;

            case \XML_ATTRIBUTE_NODE:
                if (empty($node->value)) {
                    $node->parentNode->removeChild($node);
                }
                break;

            case \XML_TEXT_NODE:
                if (ctype_space($node->nodeValue)) { 
                    //&& $node->previousSibling && $node->previousSibling->nodeType == \XML_TEXT_NODE) {
                    $node->nodeValue = trim($node->nodeValue);
                }
                break;
        }
    }
}