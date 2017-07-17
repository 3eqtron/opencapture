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
 * The header for Authorization
 * 
 * @type request
 * @name Authorization
 */
class Authorization
{
    /* Constants */

    /* Properties */
    /**
     * @var string The authentication method (Basic | Digest)
     */
    public $method;

    /**
     * @var object The credential
     */
    public $credentials;


    /* Methods */
    /**
     * Constructor
     * @param string $value
     */
    public function __construct($value=false)
    {
        $this->method = strtok($value, ' ');
        $credentials = strtok('');

        $methodClass = __NAMESPACE__.'\\'.ucfirst($this->method).'Authentication';
        if (class_exists($methodClass)) {
            $this->credentials = new $methodClass($credentials);
        } else {
            $this->credentials = $credentials;
        }        
    }
    
    /**
     * Get the string representation
     * @return string
     */
    public function __toString()
    {
        return $this->method.' '. (string) $this->credentials;
    }
}
