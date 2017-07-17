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
/**
 * The Http base ref interface
 * 
 * @package Maarch
 */
interface RefInterface
    extends ComponentInterface
{
    /**
     * Returns the referenced id
     * 
     * @return string
     */
    public function getType() : \Maarch\Reflection\ReflectionDataType;

    /**
     * Checks if the referenced parameter is required
     * 
     * @return bool
     */
    public function isRequired() : bool;

    /**
     * Checks if the referenced parameter has a default value
     * 
     * @return bool
     */
    public function isDefaultValueAvailable() : bool;

    /**
     * Returns the default value
     * 
     * @return mixed
     */
    public function getDefaultValue();
}
