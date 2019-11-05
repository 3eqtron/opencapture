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
 * XPath
 */
class XPath
    extends \DOMXPath
{
    /* Properties */
    protected $functions;

    protected $namespaces;

    /* Methods */
    /**
     * Constructor
     * @param \DOMDocument $document  The document
     * @param array        $functions An associative array of function names and php callable
     */  
    public function __construct($document, array $functions=null) 
    {
        parent::__construct($document);

        $this->registerNamespace("php", "http://php.net/xpath");
        $this->registerPhpFunctions();

        $this->registerFunction('lower-case', '\xml\XPath::lower_case');
        $this->registerFunction('upper-case', '\xml\XPath::upper_case');
        $this->registerFunction('ends-with', '\xml\XPath::ends_with');
        $this->registerFunction('matches', '\xml\XPath::matches');

        if ($functions) {
            foreach ($functions as $name => $function) {
                $this->registerFunction($name, $function);
            }
        }
    }

    /**
     * Register a namespace with a prefix
     * @param string $prefix
     * @param string $xmlns
     */
    public function registerNamespace($prefix, $xmlns)
    {
        if (!in_array($xmlns, (array) $this->namespaces)) {
            $this->namespaces[$prefix] = $xmlns;
            parent::registerNamespace($prefix, $xmlns);
        }
    }

    /**
     * Execute a XPAth query
     * @param string   $expr           The query
     * @param \DOMNode $context        The context node
     * @param boolean  $registerNodeNS Register the node namespace
     * 
     * @return \DOMNodeList
     */
    public function query($expr, \DOMNode $context=null, $registerNodeNS=true) 
    {
        $hash = md5($expr);
        if (!isset($GLOBALS[$hash])) {
            $GLOBALS[$hash] = $this->emulateFunctions($expr);
        }

        return parent::query($GLOBALS[$hash], $context, $registerNodeNS);
    }

    /**
     * Avaluate a XPAth query
     * @param string   $expr           The query
     * @param \DOMNode $context        The context node
     * @param boolean  $registerNodeNS Register the node namespace
     * 
     * @return \DOMNodeList
     */
    public function evaluate($expr, \DOMNode $context=null, $registerNodeNS=true) 
    {
        $hash = md5($expr);
        if (!isset($GLOBALS[$hash])) {
            $GLOBALS[$hash] = $this->emulateFunctions($expr);
        }
        
        return parent::evaluate($GLOBALS[$hash], $context, $registerNodeNS);
    }

    /**
     * Escape/protect/quote a xpath expression
     * @param string $expr The expression
     * 
     * @return string
     */
    public function quote($expr)
    {
        $parts = explode("'", $expr);
        if (count($parts) > 1) {
            return "concat('" . implode("', \"'\", '", $parts) . "')";
        }

        return "'". $expr . "'";
    }

    /* Apply bindings */
    /**
     * Register a php function
     * @param string $name The name of the function in xpath expression
     * @param string $func The name of the callable php function
     */
    public function registerFunction($name, $func)
    {
        if (!is_callable($func)) {
            throw new Exception("Function $func is not a valid function or callback");
        }
        
        $this->functions[$name] = $func;
    }

    /**
     * Emulate a Xpath function with a php callable
     * @param string $expr The xpath expression
     * 
     * @return string The xpath expression where xpath V2 functions have been replaced by php callables
     */
    protected function emulateFunctions($expr) 
    {
        foreach ($this->functions as $name => $callable) {
            //if (strpos($expr, $name) !== false) {
                if ($callable[0] == LAABS_NS_SEPARATOR) {
                    $expr = str_replace($name . '(', "php:function('".$callable."', ", $expr);
                } else {
                    $expr = str_replace($name . '(', "php:functionString('".$callable."', ", $expr);
                }
            //}
        }

        return $expr;
    }

    /* Static functions for binding */
    /********************************/
    /**
     * Get a node string value
     * @param \DOMNode $node
     * 
     * @return string
     */
    public static function getNodeString($node)
    {
        switch($node->nodeType) {
            case \XML_ELEMENT_NODE:    
                return $node->nodeValue;
            case \XML_ATTRIBUTE_NODE:  
                return $node->value;
            case \XML_TEXT_NODE:       
                return $node->wholeText;
            case \XML_PI_NODE:         
                return $node->data;
        }
    }

    /**
     * xpath::ends_with
     * @param mixed  $input
     * @param string $value  
     * 
     * @return boolean
     */
    public static function ends_with($input, $value)
    {
        if (!is_scalar($input)) {
            if (count($input)) {
                $node = $input[0];
                $string = static::getNodeString($node);
            } else {
                $string = "";
            }
        } else {
            $string = $input;
        }

        return (substr($string, -strlen($value)) == $value);
    }

    /**
     * xpath::matches
     * @param mixed  $input
     * @param string $pattern  
     * 
     * @return boolean
     */
    public static function matches($input, $pattern)
    {
        if (!is_scalar($input)) {
            if (count($input)) {
                $node = $input[0];
                $string = static::getNodeString($node);
            } else {
                $string = "";
            }
        } else {
            $string = $input;
        }

        return preg_match("#$pattern#", $string);
    }

    /**
     * xpath::lower-case
     * @param mixed $input
     * 
     * @return string
     */
    public static function lower_case($input)
    {
        if (!is_scalar($input)) {
            if (count($input)) {
                $node = $input[0];
                $string = static::getNodeString($node);
            } else {
                $string = "";
            }
        } else {
            $string = $input;
        }
        
        return strtolower($string);
    }

    /**
     * xpath::upper-case
     * @param \DOMNodeSet $nodeset
     * 
     * @return \DOMNodeSet
     */
    public static function upper_case($nodeset)
    {
        if (!is_scalar($input)) {
            if (count($input)) {
                $node = $input[0];
                $string = static::getNodeString($node);
            } else {
                $string = "";
            }
        } else {
            $string = $input;
        }

        return strtoupper($string);
    }
}