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
 * Reflection function
 * 
 * @package Maarch
 */
abstract class ReflectionFunctionAbstract
    extends Reflection
{
    /**
     * Indicates whether the method has parameters or not
     * @return bool
     */
    public function hasParameters()
    {
        if ($this->reflection->getNumberOfParameters() > 0) {
            return true;
        }
    }

    /**
     * Get the parameters of the method description
     * @return array An array of ReflectionParameter objects for the method
     */
    public function getParameters() : array
    {
        $parameters = [];

        $paramTags = $this->docComment->getTags('param');
        foreach ($this->reflection->getParameters() as $pos => $reflectionParameter) {
            $docComment = false;
            if (isset($paramTags[$pos])) {
                $docComment = $paramTags[$pos];
            }

            $parameters[] = new ReflectionParameter($reflectionParameter, $docComment);
        }

        return $parameters;
    }

    /**
     * Check the throwable errors or exceptions of the method
     * 
     * @return bool
     */
    public function hasThrowables() : bool
    {
        return $this->getDocComment()->hasTag('throws');
    }

    /**
     * Get the throwable errors or exceptions of the method from doc comments
     * 
     * @return ReflectionCLass[]
     */
    public function getThrowables() : array
    {
        $throwables = [];

        $docComment = $this->getDocComment();

        if ($docComment->hasTag('throws')) {
            foreach ($docComment->getTags('throws') as $thrown) {
                $throwable = strtok($thrown, ' ');
                if (class_exists($throwable)) {
                    $throwables[] = new ReflectionClass($throwable);
                }
            }
        }

        return $throwables;
    }
}