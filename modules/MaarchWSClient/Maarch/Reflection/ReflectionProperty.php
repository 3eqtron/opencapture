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
 * Reflection properties
 * 
 * @package Maarch
 */
class ReflectionProperty
    extends Reflection
{
    use ReflectionMemberTrait;

    /**
     * Constructor
     * @param mixed  $class
     * @param string $name
     */
    public function __construct($class, $name=false) 
    {
        if ($class instanceof \Reflector) {
            parent::__construct($class);

            $this->implementation = $class->name;
        } else {
            if (is_object($class)) {
                $class = get_class($class);
            }
            parent::__construct(new \ReflectionProperty($class, $name));

            $this->implementation = $class;
        }
    }

    /**
     * Get the identifier of the component
     * @return string
     */
    public function getId() : string
    { 
        return $this->reflection->class.'::$'.$this->reflection->name;
    }

    /**
     * Check if the property has a type tag
     * @return bool
     */
    public function hasType() : bool
    {
        return $this->docComment->hasTag('var');
    }

    /**
     * Get the type
     * @return ReflectionDataType
     */
    public function getType() : \Maarch\Reflection\ReflectionDataType
    {
        $type = $this->docComment->getTag('var');

        return new ReflectionDataType($this->docComment);
    }

    /**
     * Check if property is required
     * @return bool
     */
    public function isRequired() : bool
    { 
        return $this->docComment->hasTag('required');
    }

    /**
     * Check if property is read-only, meaning non public but prevent magic setter __set
     * @return bool
     */
    public function isReadonly() : bool
    { 
        return !$this->isPublic() && $this->docComment->hasTag('readonly');
    }

    /**
     * Checks if a default value is available
     * @return boolean
     */
    public function isDefaultValueAvailable() : bool
    {
        $defaults = get_class_vars($this->reflection->class);

        return isset($defaults[$this->reflection->name]);
    }

    /**
     * Returns the default value of the property
     * @return mixed
     */
    public function getDefaultValue()
    {
        $defaults = get_class_vars($this->reflection->class);

        if (isset($defaults[$this->reflection->name])) {
            return $defaults[$this->reflection->name];
        }
    }

    /**
     * Validate data against schema
     * @param mixed $value The new value
     * 
     * @return void
     * @throws ReflectionException
     */
    public function validate($value)
    {
        if (is_null($value)) {
            if ($this->isRequired()) {
                throw new ReflectionException(['Null value not allowed for property %1$s::%2$s', [$this->implementation, $this->getName()]]);
            }

            return;
        }

        if ($this->hasType()) {
            $reflectionDataType = $this->getType();
            try {
                $reflectionDataType->validate($value);
            } catch (\Maarch\Reflection\ReflectionException $reflectionException) {
                throw new ReflectionException(['Invalid value for property %1$s::%2$s', [$this->implementation, $this->getName()]], 0, $reflectionException);
            }
            
        }
    }
}