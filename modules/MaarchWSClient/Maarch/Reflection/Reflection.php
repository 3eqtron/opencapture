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
 * Abstract class for service definition components
 * 
 * @package Maarch
 * @author  Cyril Vazquez Maarch <cyril.vazquez@maarch.org>
 */
class Reflection
{
    /**
     * @var Reflector 
     */
    protected $reflection;

    /**
     * @var DocComment 
     */
    protected $docComment;

    /**
     * Constructor
     * @param Reflector $reflection
     */
    public function __construct(\Reflector $reflection)
    {
        $this->reflection = $reflection;

        $this->docComment = new ReflectionDocComment($reflection->getDocComment());
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
     * Get the doc comment parser
     * @return Maarch\Reflection\ReflectionDocComment
     */
    public function getDocComment() : \Maarch\Reflection\ReflectionDocComment
    {
        return $this->docComment;
    }

    /**
     * Get the reflection
     * @return Reflector
     */
    public function getReflection() : \Reflector
    {
        return $this->reflection;
    }

    /**
     * Call a Reflection method
     * @param string $name
     * @param array  $args
     * 
     * @return mixed
     */
    public function __call($name, array $args=[])
    {
        if (method_exists($this->reflection, $name)) {
            return call_user_func_array([$this->reflection, $name], $args);
        }

        throw new ReflectionException('Call to undefined method '.get_called_class().'::'.$name);
    }
}
