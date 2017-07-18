<?php
/*
 * Copyright (C) 2017 Maarch
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
namespace Maarch\BusinessComponents;
/**
 * A business entity
 * 
 * Business entities store data values and expose them through properties;
 * they contain and manage business data used by an application and provide 
 * stateful programmatic access to the business data and related functionality. 
 * Business entities should also validate the data contained within the entity 
 * and encapsulate business logic to ensure consistency and to implement business 
 * rules and behavior.
 *
 */
abstract class BusinessEntityAbstract
    implements \JsonSerializable
{
    /* Constants */

    /* Properties */

    /* Methods */
    /**
     * Sets the value of a non-public property
     * @param string $name  The name of the property
     * @param mixed  $value The value to set. Defaults null
     * 
     * @return void
     */
    public function __set(string $name, $value=null)
    {
        $reflectionClass = new \Maarch\Reflection\ReflectionClass($this);

        if (!$reflectionClass->hasProperty($name)) {
            if (!$reflectionClass->allowsAdditionalProperties()) {
                throw new \Maarch\Reflection\ReflectionException(['Undefined property %1$s::%2$s', [get_called_class(), $name]]);
            }
        } else {
            $reflectionProperty = $reflectionClass->getProperty($name);
            if ($reflectionProperty->isReadonly()) {
                throw new \Maarch\Reflection\ReflectionException(['Can not modify read only property %1$s::%2$s', [get_called_class(), $name]]);
            }

            $reflectionProperty->validate($value);
        }       

        $this->{$name} = $value;
    }

    /**
     * Returns the value of a non-public property
     * @param string $name The name of the property
     * 
     * @return mixed The value to set. Defaults null
     */
    public function __get(string $name)
    {
        return $this->{$name};
    }

    /**
     * Serialize to Json
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}