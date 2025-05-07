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
namespace Maarch\Reflection;
/**
 * Reflection member trait
 * 
 * @package Maarch
 * @author  Cyril Vazquez Maarch <cyril.vazquez@maarch.org>
 */
trait ReflectionMemberTrait
{
    /**
     * @var string The implementing class
     */
    protected $implementation;

    /**
     * Get the name of the component
     * @return string
     */
    public function getName() : string
    { 
        return $this->reflection->name;
    }

    /**
     * Get the implementation name
     * 
     * @return string
     */
    public function getImplementation()  : string
    {
        return $this->implementation;
    }

    /**
     * Get the implementing reflection class
     * @return Maarch\Reflection\ReflectionClass
     */
    public function getImplementingClass() : \Maarch\Reflection\ReflectionClass
    {
        return new ReflectionClass($this->implementation);
    }

    /**
     * Get the name of the declaring class
     * 
     * @return string
     */
    public function getClass() : string
    {
        return $this->reflection->class;
    }

    /**
     * Get the declaring reflection class
     * @return Maarch\Reflection\ReflectionClass
     */
    public function getDeclaringClass() : \Maarch\Reflection\ReflectionClass
    {
        return new ReflectionClass($this->reflection->class);
    }
}
