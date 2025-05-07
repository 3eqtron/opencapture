<?php
/*
 * Copyright (C) 2015 Maarch
 *
 * This file is part of Maarch.
 *
 * Bundle recordsManagement is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Bundle recordsManagement is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Maarch.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Http\Errors;
/**
 * The base abstract 500 response
 *
 * @package Maarch
 * @author  Cyril VAZQUEZ (Maarch) <cyril.vazquez@maarch.org>
 */
abstract class ServerErrorAbstract
    extends ErrorAbstract
{
    /**
     * Constructor
     * @param string    $message
     * @param int       $code
     * @param Throwable $previous
     */
    public function __construct(string $message, int $code=500, \Throwable $previous=null)
    {
        parent::__construct($message, $code, $previous);
    }
    
}