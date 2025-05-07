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
namespace Maarch\Http\Transport;
/**
 * Http wrapper for incoming http requests on Apache server
 *
 */
interface ServerInterface
{
    /**
     * Returns the server parameters
     * 
     * @return array
     */
    public function getParams();

    /**
     * Receives the http request from server software
     * @param string $request The received request identifier
     * 
     * @return Psr\Http\Message\ServerRequestInterface
     */
    public function receiveRequest($request=null);

    /**
     * Send the http response with server software
     * 
     * @param Psr\Http\Message\ResponseInterface $response
     */
    public function sendResponse(\Psr\Http\Message\ResponseInterface $response);
}