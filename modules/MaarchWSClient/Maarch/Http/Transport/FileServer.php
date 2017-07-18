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
 * Http wrapper for incoming http requests on a file server
 *
 */
class FileServer
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
     * @param string $filename
     * 
     * @return Psr\Http\Message\ServerRequestInterface
     */
    public function receiveRequest($filename=null)
    {
        $handle = fopen($filename, "r");
        if (!$handle) {
            return;            
        }

        $requestLine = trim(fgets($handle));

        $request = new HttpMessage\ServerRequest();

        // Get the http method from php SERVER superglobal
        $request->withMethod(strtok($requestLine, ' '));

        $uriString = strtok(' ');

        // Get the protocol version
        $protocol = strtok('/');
        $request->withProtocolVersion(strtok(''));

        // Get the request target
        $uri = new HttpMessage\Uri();
        $request->withUri($uri);

        // Scheme
        $uri->withScheme('http');

        // User info
        /*if (isset($_SERVER['PHP_AUTH_USER'])) {
            $password = null;
            if (!empty($_SERVER['PHP_AUTH_PW'])) {
                $password = $_SERVER['PHP_AUTH_PW'];
            }

            $uri->withUserInfo($_SERVER['PHP_AUTH_USER'], $password);
        }*/

        // Host
        $uri->withHost('');

        // Path 
        $uri->withPath(strtok($uriString, '?'));

        // Query
        $uri->withQuery(strtok(''));

        // Get the headers
        while ($headerLine = trim(fgets($handle))) {
            $name = strtok($headerLine, ':');
            $value = trim(strtok(''));
            $request->withHeader($name, array_map('trim', (array) explode(',', $value)));
        }

        $body = '';
        while ($bodyLine = trim(fgets($handle))) {
            $body .= $bodyLine;
        }
        if (!empty($body)) {
            // Get the body in read/write to allow rewind
            $temp = fopen('php://temp', 'r+');
            fwrite($temp, $body);
            rewind($temp);
            $stream = new HttpMessage\Stream($temp);
            $request->withBody($stream);
        }

        // Get the uploaded files
        /*$uploadedFiles = [];
        foreach ($_FILES as $key => $filearr) {
            $uploadedFiles[$key] = new HttpMessage\UploadedFile($filearr['name'], $filearr['type'], $filearr['size'], $filearr['tmp_name'], $filearr['error']);
        }
        $request->withUploadedFiles($uploadedFiles);*/

        fclose($handle);

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