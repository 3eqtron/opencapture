<?php
/*
 * Copyright (C) 2015 Maarch
 *
 * This file is part of Maarch.
 *
 * Maarch is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Maarch is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Maarch. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Http\Errors;
/**
 * A generic error message, given when an unexpected condition was encountered and no more specific message is suitable.
 * 
 * @code 500
 */
class InternalServerError
    extends ServerErrorAbstract
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