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
namespace Maarch\Http\Description;
use \Maarch\Reflection;
/**
 * Class that defines the response status
 * 
 * @package Maarch
 */
class ResponseStatus
    extends Reflection\ReflectionProperty
    implements \JsonSerializable, RefInterface
{
    /**
     * Returns the value of the header for the header reference
     * @param object $entity
     * 
     * @return int
     */
    public function getCode($entity)
    {
        return $this->reflection->getValue($entity);
    }

    /**
     * Serialize to json
     * @return array
     */
    public function jsonSerialize()
    {
        return 200;
    }
}