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
use Maarch\Http\Message as HttpMessage;
/**
 * Http wrapper for http stream clients
 *
 */
class StreamClient
    implements ClientInterface
{
    /**
     * @var resource
     */
    protected $socket;

    /**
     * Sends the request to a server and receives the server response through the protocol using the software.
     * 
     * @param Psr\Http\Message\RequestInterface $request
     */
    public function sendRequest(\Psr\Http\Message\RequestInterface $request)
    {       
        $body = $request->getBody();

        $headers = [];
        foreach ($request->getHeaders() as $name => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $headers[] = $name.': '.$value;
        }

        $opts = array('http' =>
            array(
                'method'  => $request->getMethod(),
                'header'  => $headers,
                'content' => $body ? $request->getBody()->getContents() : null,
                'ignore_errors' => true,
                'timeout' => 30,
            ),
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false
            )
        );

        $context = stream_context_create($opts);

        // Get response
        $this->socket = fopen((string) $request->getUri(), 'r', false, $context);
    }

    /**
     * Receives the response as an Http response
     * 
     * @return Psr\Http\Message\ResponseInterface
     */
    public function receiveResponse() : \Psr\Http\Message\ResponseInterface
    {
        $response = new HttpMessage\Response();

        $socketMetadata = stream_get_meta_data($this->socket);
        $httpMetadata = $socketMetadata['wrapper_data'];

        $protocol = strtok($httpMetadata[0], '/');
        $response->withProtocolVersion(strtok(' '));

        $response->withStatus(strtok(' '), strtok(''));

        while ($header = next($httpMetadata)) {
            $response->withHeader(strtok($header, ':'), trim(strtok('')));
        }
        $responseBodyStream = new HttpMessage\Stream($this->socket);
        $response->withBody($responseBodyStream);

        return $response;
    }
}