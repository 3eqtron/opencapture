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
 * The Http response status definition interface
 * 
 * @package Maarch
 */
interface StatusInterface
    extends ComponentInterface
{
    /**
     * Returns the numeric status code
     * @param object $entity 
     * 
     * @return integer
     */
    public function getCode($entity=null) : int;

    /**
     * Returns the reason phrase
     * @return string
     */
    public function getReasonPhrase() : string;
    
    /**
     * Returns the http problem details
     * @return mixed
     */
    public function getDetail();
}
