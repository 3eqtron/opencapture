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
 * The Http Header interface
 * 
 * @package Maarch
 */
interface HeaderInterface
    extends ComponentInterface
{
    /**
     * Return a new instance of the parsed header
     * @param string $headerline The original request header line
     * 
     * @return object
     */
    public function parse(string $headerline);

    /**
     * Returns the type of header : request, response, general
     * @return string
     */
    public function getType() : string;
}
