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
 * The service request or response entity body representation definition
 * 
 * @package Maarch
 */
class Representation
    extends Reflection\ReflectionMethod
    implements \JsonSerializable, RepresentationInterface
{
    /**
     * Check the media type
     * @return boolean
     */
    public function hasMediaType() : bool
    {
        return $this->docComment->hasTag('mediaType');
    }

    /**
     * Get the media type
     * @return string
     */
    public function getMediaType() : string
    {
        return $this->docComment->getTag('mediaType');
    }

    /**
     * Get the root element name
     * @return string
     */
    public function getElement() : string
    {
        return $this->docComment->getTag('element');
    }

    /**
     * Get the data profile
     * @return string
     */
    public function getProfile() : string
    {
        return $this->getTag('profile');
    }

    /**
     * Parse the entity
     * @param string $entity
     * 
     * @return mixed
     */
    public function parse(string $entity)
    {
        $parser = $this->reflection->getDeclaringClass()->newInstanceWithoutConstructor();

        return call_user_func([$parser, $this->reflection->name], $entity);
        
        return $this->reflection->invokeArgs($parser, $entity);
    }

    /**
     * Serialize to json
     * @return array
     */
    public function jsonSerialize()
    {
        $return = [];

        $return['mediaType'] = $this->getMediaType();
        $return['element'] = $this->getElement();

        return $return;
    }

}