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
 * Similar to 403 Forbidden, but specifically for use when authentication is required and has failed or has not yet been provided. 
 * The response must include a WWW-Authenticate header field containing a challenge applicable to the requested resource. 
 * See Basic access authentication and Digest access authentication.
 * 401 semantically means "unauthenticated", i.e. the user does not have the necessary credentials.
 * 
 * @code 401
 */
class Unauthorized
    extends ClientErrorAbstract
{
    /**
     * @var The authentication information
     * @header WWW-Authenticate
     */
    public $WWWAuthenticate;

    /**
     * Constructor
     * @param string    $message
     * @param int       $code
     * @param Throwable $previous
     */
    public function __construct(string $message, int $code=401, \Throwable $previous=null)
    {
        parent::__construct($message, $code, $previous);
    }
}