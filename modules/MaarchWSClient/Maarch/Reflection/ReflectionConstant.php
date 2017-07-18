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
namespace Maarch\Reflection;
/**
 * Class that defines a constant
 * 
 */
class ReflectionConstant
{
    /* Properties */
    /**
     * @var string The const name
     */
    protected $name;

    /**
     * @var mixed The value
     */
    protected $value;

    /**
     * @var string The doc comment
     */
    protected $docComment;

    /* Methods */
    /**
     * Constructor of the constant
     * @param string $name       The name of the constant
     * @param mixed  $value      The value
     * @param string $docComment The docComment
     */
    public function __construct($name, $value, $docComment=null)
    {
        $this->name = $name;
        
        $this->value = $value;

        $this->docComment = new ReflectionDocComment($docComment);
    }

    /**
     * Constructor
     * @return ReflectionDocComment the DocComment object
     */
    public function getDocComment() : \Maarch\Reflection\ReflectionDocComment
    {
        return $this->docComment;
    }

    /**
     * Get string version
     * @return mixed
     */
    public function __toString() : string
    {
        return (string) $this->value;
    }

    /**
     * Get the name
     * 
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Get the value
     * 
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

}
