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
 * The service message abstract description
 * 
 * @package Maarch
 */
abstract class MessageAbstract
    extends Reflection\ReflectionClass
    implements \JsonSerializable
{
    /**
     * Get the headers
     * @return Maarch\Http\Description\HeaderRef[]
     */
    public function getHeaders() : array
    {
        $headers = [];

        foreach ($this->reflection->getProperties() as $reflectionProperty) {
            if (preg_match('#\* *@header#', $reflectionProperty->getDocComment())) {
                $headers[] = new HeaderRef($reflectionProperty);
            }
        }

        return $headers;
    }

    /**
     * Get representations
     * @return Maarch\Http\Description\Representation[]
     */
    public function getRepresentations() : array
    {
        $representations = [];

        foreach ((array) $this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            if (!$reflectionMethod->isUserDefined()) {
                continue;
            }

            if (preg_match('#\* *@representation#', $reflectionMethod->getDocComment())) {
                $representations[] = new Representation($reflectionMethod);
            }
        }

        return $representations;
    }
}