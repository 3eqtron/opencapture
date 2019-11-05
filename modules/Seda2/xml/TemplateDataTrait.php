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

trait TemplateDataTrait
{

    protected $sources = array();

    protected $variables = array();

    protected $fragments = array();

    protected $functions = array();

    /* ------------------------------------------------------------------------
        Data sources management
    ------------------------------------------------------------------------ */
    
    /**
     * Bind variable
     * @param string $name
     * @param string $variable
     *
     */
    public function bindVariable($name, &$variable)
    {
        $this->variables[$name] = &$variable;
    }

    /**
     * Set source
     * @param string $name
     * @param string $value
     *
     */
    public function setSource($name, $value)
    {
        $this->sources[$name] = $value;
    }

    /**
     * Set function
     * @param string $name
     * @param string $callable
     *
     */
    public function setFunction($name, $callable)
    {
        $this->functions[$name] = $callable;
    }

    /**
     * Set html fragment
     * @param string $name
     * @param string $fragment
     *
     */
    public function setHtmlFragment($name, $fragment)
    {
        $fragment = $this->createDocumentFragment();
        $fragment->appendHtml($fragment);
        $this->fragments[$name] = $fragment;
    }

    /**
     * Set xml fragment
     * @param string $name
     * @param string $fragment
     *
     */
    public function setXmlFragment($name, $fragment)
    {
        $fragment = $this->createDocumentFragment();
        $fragment->appendXml($fragment);
        $this->fragments[$name] = $fragment;
    }

    protected function &getData($instr, $source=null)
    {
        //var_dump("getData");
        //var_dump($instr);
        //var_dump($source);

        $value = null;

        $steps = $instr->source;

        // First step defines source
        $type = $steps[0][0];
        switch($type) {
            case 'arg':
                $value = &$source;
                break;
            case 'source':
                $name = $steps[0][1];
                if (isset($this->sources[$name])) {
                    $value = &$this->sources[$name];
                } elseif (is_scalar($name)) {
                    if ($name[0] == '"' || $name[0] == "'") {
                        $value = substr($name, 1, -1);
                    } elseif (is_numeric($name)) {
                        $value = $name;
                    }
                }
                break;
            case 'var':
                $name = $steps[0][1];
                if (isset($this->variables[$name])) {
                    $value = &$this->variables[$name];
                }
                break;
            case 'method':
                $route = $steps[0][1];
                $methodRouter = new \core\Route\MethodRouter($route);
                $serviceObject = $methodRouter->service->newInstance();
                break;
        }

        for ($i=1, $l=count($steps); $i<$l; $i++) {
            $value = &$this->stepData($steps[$i], $value);
        }

        return $value;
    }

    protected function &stepData($step, $source)
    {
        //var_dump("stepData");
        //var_dump($step);
        //var_dump("from " . gettype($source));
        $value = null;
        switch($step[0]) {
        case 'func':
            $value = &$this->stepFunc($step[1], $step[2], $source);
            break;

        case 'offset':
            $key = &$this->getParamValue($step[1], $source);
            if (is_array($source) && isset($source[$key])) {
                $value = &$source[$key];
            }
            break;

        case 'prop':
            if (isset($source->{$step[1]})) {
                $value = &$source->{$step[1]};
            }
            break;
        }
        
        return $value;
    }

    protected function &stepFunc($name, $params=array(), $source)
    {
        $value = null;
        foreach ($params as $i => $param) {
            $params[$i] = &$this->getParamValue($param, $source);
        }

        if (is_object($source) && method_exists($source, $name)) {
            $value = call_user_func_array(array($source, $name), $params);

            return $value;
        }
        //var_dump($params);
        switch($name) {
            // Callback functions
            case 'func':
                $func = $params[0];
                if (!isset($this->functions[$func])) {
                    break;
                }
                $callback = $this->functions[$func];
                array_shift($params);
                array_unshift($params, $source);
                $value = @call_user_func_array($callback, $params);
                break;

            // Array functions
            case 'length':
            case 'count':
                $value = @count($source);
                break;
            case 'key':
                $value = @key($source);
                break;
            case 'current':
                $value = @current($source);
                break;
            case 'first':
                $value = @reset($source);
                break;
            case 'next':
                $value = @next($source);
                break;
            case 'prev':
                $value = @prev($source);
                break;
            case 'end':
                $value = @end($source);
                break;
            case 'pos':
                $pos = null;
                foreach ((array) $source as $key => $value) {
                    $pos++;
                    if ($key == @key($source)) {
                        break;
                    }
                }
                if (!is_null($pos)) {
                    $value = $pos;
                }
                break;
            case 'islast':
                $value = ((@key($source)+1) == @count($source));
                break;
            case 'slice':
                $value = @array_slice($source, $params[0], $params[1]);
                break;
            case 'arraykeyexists':
                $value = @array_key_exists($params[0], $source);
                break;
            case 'inarray':
                $value = @in_array($params[0], $source);
                break;

            // Variable functions
            case 'type':
                $value = @gettype($source);
                break;
            case 'not':
                $value = @!$source;
                break;
            case 'empty':
                $value = @empty($source);
                break;
            case 'isset':
                $value = @isset($source);
                break;
            case 'isarray':
                $value = @is_array($source);
                break;
            case 'isbool':
                $value = @is_bool($source);
                break;
            case 'isfloat':
            case 'isdouble':
            case 'isreal':
                $value = @is_float($source);
                break;
            case 'isint':
            case 'isinteger':
            case 'islong':
                $value = @is_int($source);
                break;
            case 'isnull':
                $value = @is_null($source);
                break;
            case 'isnotnull':
                $value = @!is_null($source);
                break;
            case 'isnumeric':
                $value = @is_numeric($source);
                break;
            case 'isobject':
                $value = @is_object($source);
                break;
            case 'isscalar':
                $value = @is_scalar($source);
                break;
            case 'isstring':
                $value = @is_string($source);
                break;
            case 'int':
                $value = @intval($source);
                break;
            case 'float':
                $value = @floatval($source);
                break;
            case 'string':
                $value = @strval($source);
                break;
            case 'bool':
                $value = @(bool) $source;
                break;
            case 'urlencode':
                $value = @urlencode($source);
                break;
            case 'array':
                if (!is_array($source)) {
                    if (is_null($source)) {
                        $value = [];
                    } else {
                        $value = [$source];
                    }
                } else {
                    $value = $source;
                }
                break;
            case 'attr':
                if (isset($source->{$params[0]})) {
                    $value = @$source->{$params[0]};
                }
                break;
            case 'in':
                $value = false;
                $i = 0;
                while (isset($params[$i])) {
                    $value = $value || $source == $params[$i];
                    $i++;
                }
                break;
            case 'between':
                if (isset($params[2]) && $params[2]) {
                    $value = ($source > $params[0] && $source < $params[1]);
                } else {
                    $value = ($source >= $params[0] && $source <= $params[1]);
                }
                break;
            case 'ifeq':
                $value = ($source == $params[0]);
                break;
            case 'ifne':
                $value = ($source != $params[0]);
                break;
            case 'ifgt':
                $value = ($source > $params[0]);
                break;
            case 'ifgte':
                $value = ($source >= $params[0]);
                break;
            case 'iflt':
                $value = ($source < $params[0]);
                break;
            case 'iflte':
                $value = ($source <= $params[0]);
                break;
            case 'contains':
                $value = (strpos($source, $params[0]) !== false);
                break;
            case 'starts-with':
                $value = (strpos($source, $params[0]) === 0);
                break;
            case 'ends-with':
                $value = (strrpos($source, $params[0]) === (strlen($source) - strlen($params[0]) + 1));
                break;
            case 'bit':
                $value = ($source & (int) $params[0]) > 0;
                break;

            case 'then':
                if ($source) {
                    $value = $params[0];
                } elseif (isset($params[1])) {
                    $value = $params[1];
                }
                break;

            case 'coalesce':
                if (is_null($source)) {
                    $value = $params[0];
                } else {
                    $value = $source;
                }
                break;

            // String functions
            case "newId":
                $value = @\laabs::newId();
                break;
            case 'format':
            case 'fmt':
                if (!empty($source)) {
                    $value = @sprintf($params[0], $source);
                }
                break;
            case 'match':
                $value = (bool) @preg_match($params[0], $source);
                break;
            case 'upper':
                $value = @strtoupper($source);
                break;
            case 'lower':
                $value = @strtolower($source);
                break;
            case 'ucfirst':
                $value = @ucfirst($source);
                break;
            case 'lcfirst':
                $value = @lcfirst($source);
                break;
            case 'ucwords':
                $value = @ucwords($source);
                break;
            case 'split':
            case 'explode':
                $value = @explode($params[0], $source);
                break;
            case 'substr':
                if (isset($params[1])) {
                    $value = @substr($source, $params[0], $params[1]);
                } else {
                    $value = @substr($source, $params[0]);
                }
                break;
            case 'join':
            case 'implode':
                $value = @implode($params[0], $source);
                break;
            case 'constant':
                $value = @constant($source);
                if (is_null($value) && $params[0]) {
                    $value = $source;
                }
                break;
            case 'print':
                $value = @print_r($source, true);
                break;
            case 'json':
                if (isset($params[0])) {
                    $options = \JSON_PRETTY_PRINT;
                } else {
                    $options = 0;
                }
                $value = @json_encode($source, $options);
                break;
            case 'parse':
                if (isset($params[0])) {
                    $value = @json_decode($source, $params[0]);
                } else {
                    $value = @json_decode($source);
                }
                break;
            case 'encodehtml':
                $value = @htmlentities($source);
                break;
            case 'decodehtml':
                $value = @html_entity_decode($source);
                break;
            case 'base64':
                $value = @base64_encode($source);
                break;
            case 'cat':
                $value = @implode($params);
                break;
            case 'dump':
                ob_start();
                var_dump($source);
                $value = @ob_get_clean();
                break;
            case 'translate':
                if (is_string($source) && $this->translator) {
                    $catalog = null;
                    if (isset($params[0])) {
                        $catalog = $params[0];
                    }
                    $context = null;
                    if (isset($params[1])) {
                        $context = $params[1];
                    }
                    $value = $this->translator->getText($source, $context, $catalog);
                } else {
                    $value = $source;
                }
                break;

            // Number functions
            case 'add':
                $value = @($source + $params[0]);
                break;
            case 'mul':
                $value = @($source * $params[0]);
                break;
            case 'div':
                $value = @($source / $params[0]);
                break;
            case 'mod':
                $value = @($source % $params[0]);
                break;
        }

        return $value;
    }

    protected function &getParamValue($param, $source=null)
    {
        if ($param[0] == "'" || $param[0] == '"') {
            $value = substr($param, 1, -1);
        } elseif (is_numeric($param)) {
            $value = $param;
        } else {
            $instr = $this->parse($param);
            $value = &$this->getData($instr, $source);
        }
        
        return $value;
    }

    protected function addVar($name, &$var)
    {
        $this->variables[$name] = $var;
    }

}