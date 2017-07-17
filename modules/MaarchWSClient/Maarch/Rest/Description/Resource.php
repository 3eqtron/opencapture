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
 * Description of a service resource
 * 
 * @package Maarch
 */
class Resource
    extends Reflection\ReflectionClass
    implements \JsonSerializable
{
    /**
     * Get the resources
     * @return Maarch\Rest\Description\ResourceLink[]
     */
    public function getResourceLinks() : array
    {
        $resourceLinks = [];
        
        foreach ($this->reflection->getProperties() as $reflectionProperty) {
            if (preg_match('#\* *@path#', $reflectionProperty->getDocComment())) {
                $resourceLinks[] = new ResourceLink($reflectionProperty);
            }
        }

        return $resourceLinks;
    }

    /**
     * Get methods
     * @param int $filter
     * 
     * @return Maarch\Rest\Description\Method[]
     */
    public function getMethods($filter = 0) : array
    {
        $methods = [];
        foreach ($this->reflection->getMethods() as $reflectionMethod) {
            if (!$reflectionMethod->isUserDefined()) {
                continue;
            }
            if ($reflectionMethod->name[0] == '_') {
                continue;
            }
            $methods[] = new Method($reflectionMethod);
        }

        return $methods;
    }

    /**
     * Get the resource attributes
     * @return Maarch\Rest\Description\ResourceAttribute[]
     */
    public function getAttributes() : array
    {
        $attributes = [];
        
        if ($reflectionConstructor = $this->reflection->getConstructor()) {
            $docComment = new \Maarch\Reflection\ReflectionDocComment($reflectionConstructor->getDocComment());
            $paramTags = $docComment->getTags('param');
            // Resource or resource type params are constructor method params with a style
            foreach ($reflectionConstructor->getParameters() as $pos => $reflectionParameter) {
                if (isset($paramTags[$pos]) 
                    && (strpos($paramTags[$pos], '@template') || strpos($paramTags[$pos], '@matrix'))
                ) {
                    $attributes[] =  new ResourceAttribute($reflectionParameter, $paramTags[$pos]);
                }
            }
        }

        return $attributes;
    }

    /**
     * Get an instance
     * @param array $args
     * 
     * @return object
     */
    public function newInstance(array $args=[])
    {
        if ($this->reflection->hasMethod('__construct')) {
            return $this->reflection->newInstanceArgs($args);
        } else {
            return $this->reflection->newInstanceWithoutConstructor();
        }
    }

    /**
     * Serialize to json
     * @return array
     */
    public function jsonSerialize()
    {
        $return = [];
        $return['name'] = str_replace('\\', '.', $this->getName());
        $return['path'] = $this->path;
        
        foreach ($this->getProperties() as $property) {
            $return['properties'][$property->getName()] = $property;
        }

        foreach ($this->getAttributes() as $attribute) {
            $return['attributes'][$attribute->getName()] = $attribute;
        }

        $return['methods'] = [];
        foreach ($this->getMethods() as $method) {
            $return['methods'][$method->getName()] = $method;
        }

        return $return;
    }
}