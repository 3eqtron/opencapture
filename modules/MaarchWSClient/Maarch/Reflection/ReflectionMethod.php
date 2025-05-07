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
 * Reflection method
 * 
 * @package Maarch
 */
class ReflectionMethod
    extends ReflectionFunctionAbstract
{
    use ReflectionMemberTrait;

    /**
     * Constructor
     * @param mixed  $class
     * @param string $name
     */
    public function __construct($class, $name=false) 
    {
        if ($class instanceof \ReflectionMethod) {
            parent::__construct($class);

            $this->implementation = $class->class;
        } else {
            if ($name) {
                if (is_object($class)) {
                    $class = get_class($class);
                }
                $this->implementation = $class;
                parent::__construct(new \ReflectionMethod($class, $name));
            } else {
                list($this->implementation, $name) = explode('::', $class);
                parent::__construct(new \ReflectionMethod($this->implementation, $name));
            }   
        }
    }

    /**
     * Returns the method identifier
     * @return string
     */
    public function getId() : string
    {
        return $this->reflection->class.'::'.$this->reflection->name;
    }

    /**
     * Invoke the method
     * @param object $object The instance of object
     * @param mixed  $args   The call args
     * 
     * @return mixed
     */
    /*public function invoke($object, ...$args)
    {
        $args = func_get_args();

        array_shift($args);

        return $this->invokeArgs($object, $args);
    }*/

    /**
     * Invoke the method with an array of arguments
     * @param object $object The instance of object
     * @param mixed  $args   The call args
     * 
     * @return mixed
     */
    /*public function invokeArgs($object, array $args=[])
    {

        return parent::invokeArgs($object, $args);
    }*/
}