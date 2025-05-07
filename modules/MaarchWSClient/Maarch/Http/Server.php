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
namespace Maarch\Http;
/**
 * The Http server
 */
class Server
{
    /**
     * @var Psr\Http\Message\ServerRequestInterface
     */
    protected static $request;

    /**
     * @var Psr\Http\Message\ResponseInterface
     */
    protected static $response;

    /* Methods */
    /**
     * Run the server
     * @param Psr\Http\Message\ServerRequestInterface $request The request reference if needed
     * 
     * @return Psr\Http\Message\ResponseInterface
     */
    public static function process(\Psr\Http\Message\ServerRequestInterface $request) : \Psr\Http\Message\ResponseInterface
    {
        static::$request = $request;

        if (empty(static::$response)) {
            static::$response = new \Maarch\Http\Message\Response();
        }

        $filename = static::$request->getServerParams()['DOCUMENT_ROOT'].$httpRequest->getUri()->getPath();
        if (is_file($filename)) {
            ob_start();
            require_once $filename;
            $output = ob_end_clean();
        } else {
            $output = '';
            static::$response->withStatus(404);
        }

        static::$response->withSerializedBody($output)
                         ->withHeader('X-Processed-By', 'Maarch Http Server')
                         ->withHeader('X-Process-Time', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);

        return static::$response;
    }

    /**
     * Returns the current http request
     * @return Psr\Http\Message\ServerRequestInterface
     */
    public static function getRequest()
    {
        return static::$request;
    }

    /**
     * Sets the current http response
     * @param Psr\Http\Message\ResponseInterface $response
     */
    public static function setResponse(\Psr\Http\Message\ResponseInterface $response)
    {
        static::$response = $response;
    }

    /**
     * Returns the current http response
     * @return Psr\Http\Message\ResponseInterface
     */
    public static function getResponse()
    {
        return static::$response;
    }
}