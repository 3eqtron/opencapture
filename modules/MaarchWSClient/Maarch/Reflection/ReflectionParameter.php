<?php
/*
 * Copyright (C) 2017 Maarch
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Reflection;
/**
 * The definition of a function parameter
 * 
 * @package Maarch
 */
class ReflectionParameter
    extends Reflection
{
    /* Methods */
    /**
     * Constructor
     * @param ReflectionParameter $reflection
     * @param string              $docComment
     */
    public function __construct(\ReflectionParameter $reflection, $docComment)
    {
        $this->reflection = $reflection;

        $this->docComment = $docComment;
    }

    /**
     * Get the name of the component
     * @return string
     */
    public function getName() : string
    { 
        return $this->reflection->name;
    }

    /**
     * Get the style of parameter
     * @return string
     */
    public function getStyle() : string
    {
        if (!empty($this->docComment)) {
            $type = strtok($this->docComment, ' ');
            $name = trim(strtok(' '));
            $description = trim(strtok(''));

            if (!empty($description) && $description[0] == '@') {
                return substr(strtok($description, ' '), 1);
            }
        }
    }

    /**
     * Check if parameter type hint is a built-in type
     * @return boolean
     */
    public function isBuiltIn() : bool
    { 
        $type = $this->reflection->getType();
        
        return (!$type || $type->isBuiltIn());
    }

    /**
     * Check if parameter type hint is a class name
     * @return boolean
     */
    public function isClass() : bool
    { 
        return class_exists((string) $this->reflection->getType());
    }
}
