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
namespace Maarch\Rest;
/**
 * The Rest application
 */
class App
    extends \Maarch\Http\App
{

    /**
     * Sets the start resource
     * @param string $id
     */
    public static function setStart($id)
    {
        static::$start = static::getResource($id);
    }

    /**
     * Get a resource definition by reference
     * @param string $id
     * 
     * @return Maarch\Rest\Description\Resource
     */
    public static function getResource($id)
    {
        return new Description\Resource(new \ReflectionClass($id));
    }

    /**
     * Serialize to json string
     * @return array
     */
    public function jsonSerialize()
    {
        return [];
    }
}