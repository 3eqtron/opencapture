<?php
/*
 * Copyright (C) 2015 Maarch
 *
 * This file is part of Maarch.
 *
 * Maarch is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Maarch is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Maarch.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Http\Headers;
/**
 * The header extended value (value with parameters with semi-coma separation)
 */
class ExtendedValue
{
    /* Constants */

    /* Properties */
    /**
     * @var string The value
     */
    public $value;

    /**
     * @var array The params
     */
    public $params = [];

    /* Methods */
    /**
     * Constructor with string value
     * @param string $value
     */
    public function __construct($value=false)
    {
        $this->value = strtok($value, ';');

        while ($param = trim(strtok(';'))) {
            $this->addParam($param);
        }
    }

    protected function addParam($param)
    {
        if (strpos($param, '=') !== false) {
            list($name, $value) = explode('=', $param);
        } else {
            $name = $param;
            $value = true;
        }
        
        if (strpos($name, '-') !== false) {
            $tokens = explode('-', $name);
            $tokens = array_map("strtolower", $tokens);
            $tokens = array_map('ucfirst', $tokens);
            
            $name = lcfirst(implode('', $tokens));
        }

        $this->params[$name] = $value;
    }
    
    /**
     * Get the string representation
     * @return string
     */
    public function __toString()
    {
        $string = $this->value;

        foreach ($this->params as $name => $value) {
            $string .= '; ' . $name;
            if ($value) {
                $string .= '=' . $value;
            }
        }

        return (string) $string;
    }
}