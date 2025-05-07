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
 * Http wrapper for http stream clients
 *
 */
interface ClientInterface
{
    /**
     * Sends the request to a server and receives the server response through the protocol using the software.
     * 
     * @param Psr\Http\Message\RequestInterface $request
     * 
     * @return void
     */
    public function sendRequest(\Psr\Http\Message\RequestInterface $request);

    /**
     * Receives the response as an Http response
     * 
     * @return Psr\Http\Message\ResponseInterface
     */
    public function receiveResponse() : \Psr\Http\Message\ResponseInterface;
}