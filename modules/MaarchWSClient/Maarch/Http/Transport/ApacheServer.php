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
 * Http wrapper for incoming http requests on Apache server
 *
 */
class ApacheServer
    implements \Maarch\Http\Transport\ServerInterface
{
    /**
     * Get the server parameters
     * @return array
     */
    public function getParams()
    {
        return $_SERVER;
    }

    /**
     * Receives the http request from server software
     * @param null $request
     * 
     * @return Psr\Http\Message\ServerRequestInterface
     */
    public function receiveRequest($request=null)
    {
        $request = new HttpMessage\ServerRequest();

        // Get the protocol version from apache server
        $protocol = strtok($_SERVER['SERVER_PROTOCOL'], '/');
        $request->withProtocolVersion(strtok(''));

        // Get the http method from php SERVER superglobal
        $request->withMethod($_SERVER['REQUEST_METHOD']);

        // Get the request target
        $uri = new HttpMessage\Uri();
        $request->withUri($uri);

        // Scheme
        $uri->withScheme($_SERVER['REQUEST_SCHEME']);

        // User info
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $password = null;
            if (!empty($_SERVER['PHP_AUTH_PW'])) {
                $password = $_SERVER['PHP_AUTH_PW'];
            }

            $uri->withUserInfo($_SERVER['PHP_AUTH_USER'], $password);
        }

        // Host
        $uri->withHost($_SERVER['HTTP_HOST']);

        // Port
        if (isset($_SERVER['HTTP_PORT'])) {
            $uri->withPort($_SERVER['HTTP_PORT']);
        }

        // Path 
        $uri->withPath($_SERVER['SCRIPT_URL']);

        // Query
        $uri->withQuery($_SERVER['QUERY_STRING']);

        // Get the headers
        foreach (getallheaders() as $name => $value) {
            $request->withHeader($name, array_map('trim', (array) explode(',', $value)));
        }

        // Get the body in read/write to allow rewind
        $input = fopen('php://input', 'r');
        //$temp = fopen('php://temp', 'r+');
        //stream_copy_to_stream($input, $temp);
        //rewind($temp);
        $stream = new HttpMessage\Stream($input);
        $request->withBody($stream);

        // Get the uploaded files
        $uploadedFiles = [];
        foreach ($_FILES as $key => $filearr) {
            $uploadedFiles[$key] = new HttpMessage\UploadedFile($filearr['name'], $filearr['type'], $filearr['size'], $filearr['tmp_name'], $filearr['error']);
        }
        $request->withUploadedFiles($uploadedFiles);

        return $request;
    }

    /**
     * Send the http response with server software
     * @param Psr\Http\Message\ResponseInterface $response
     */
    public function sendResponse(\Psr\Http\Message\ResponseInterface $response)
    {
        if (headers_sent()) {
            foreach (headers_list() as $header) {
                header_remove(strtok($header, ':'));
            }

        }
        //if (!headers_sent()) {
            http_response_code($response->getStatusCode());

            foreach ($response->getHeaders() as $name => $value) {
                if (is_array($value)) {
                    $value = implode(', ', str_replace("\n", "", $value));
                }
                header($name.': '.$value);
            }
        //}

        if ($bodyStream = $response->getBody()) {
            $bodyStream->rewind();

            echo $bodyStream;
        }
    }
}