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
 * Interface for Http message representation description
 * 
 * @package Maarch
 */
interface RepresentationInterface
    extends ComponentInterface
{
    /**
     * Check the media type
     * @return boolean
     */
    public function hasMediaType() : bool;

    /**
     * Get the media type
     * @return string
     */
    public function getMediaType() : string;

    /**
     * Get the root element name
     * @return string
     */
    public function getElement() : string;

    /**
     * Get the data profile
     * @return string
     */
    public function getProfile() : string;

    /**
     * Parse the entity
     * @param string $entity
     * 
     * @return mixed
     */
    public function parse(string $entity);
}