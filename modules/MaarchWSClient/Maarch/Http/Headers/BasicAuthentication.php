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
 * along with Maarch. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Http\Headers;
/**
 * The value for basic authentication credential
 */
class BasicAuthentication
{
    /* Constants */

    /* Properties */
    /**
     * @var string The username
     */
    public $username;

    /**
     * @var string The password
     */
    public $password;

    /* Methods */
    /**
     * Constructor
     * @param string $value
     */
    public function __construct($value=false)
    {
        if ($value) {
            $value = base64_decode($value);

            $this->username = strtok($value, ':');
            $this->password = trim(strtok(''));
        }
    }

    /**
     * Get the string representation
     * @return string
     */
    public function __toString()
    {
        return base64_encode($this->username.':'.$this->password);
    }
}