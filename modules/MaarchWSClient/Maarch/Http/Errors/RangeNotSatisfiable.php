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
 * The client has asked for a portion of the file (byte serving), but the server cannot supply that portion. 
 * For example, if the client asked for a part of the file that lies beyond the end of the file.[47] Called "Requested Range Not Satisfiable" previously.
 * 
 * @code 416
 */
class RangeNotSatisfiable
    extends ClientErrorAbstract
{
    /**
     * Constructor
     * @param string    $message
     * @param int       $code
     * @param Throwable $previous
     */
    public function __construct(string $message, int $code=416, \Throwable $previous=null)
    {
        parent::__construct($message, $code, $previous);
    }
}