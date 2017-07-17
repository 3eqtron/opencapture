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
namespace Maarch\Rest\Description;
use \Maarch\Reflection;
/**
 * Reference to a resource on a container
 * 
 * @package Maarch
 */
class ResourceLink
    extends Reflection\ReflectionProperty
    implements \JsonSerializable
{
    /**
     * Get the resource
     * 
     * @return Maarch\Rest\Description\Resource
     */
    public function getResource() : \Maarch\Rest\Description\Resource
    {
        if ($this->docComment->hasTag('var')) {
            return new Resource(new \ReflectionClass(strtok($this->docComment->getTag('var'), ' ')));
        }
    }

    /**
     * Get the path
     * @return string
     */
    public function getPath() : string
    {
        if ($this->docComment->hasTag('path')) {
            return $this->docComment->getTag('path');
        }
    }

    /**
     * Get the authentication method if protected
     * @return string
     */
    public function getAuthentication() : string
    {
        if (!$this->reflection->isPublic()) {
            return $this->docComment->getTag('auth');
        }
    }

    /**
     * Serialize to json
     * @return array
     */
    public function jsonSerialize()
    {
        return [];
    }
}