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
 * The Http error response composer
 */
class ErrorComposer
    extends ResponseComposer
{
    /**
     * Compose the error response
     * @param \Throwable $error
     * 
     * @return Psr\Http\Message\ResponseInterface
     */
    public static function composeError(\Throwable $error)
    {
        $errorDescription = new Description\Error(new \ReflectionClass($error));

        $httpResponse = new Message\Response();
        
        if ($errorDescription) {
            static::setErrorStatus($httpResponse, $error, $errorDescription);

            static::setErrorHeaders($httpResponse, $error, $errorDescription);
        }

        static::setResponseBody($httpResponse, $error, $errorDescription);
       
        Server::setResponse($httpResponse);
    }

    protected static function setErrorStatus($httpResponse, $error, $errorDescription=null)
    {
        $httpResponse->withStatus($error->getCode(), $error->getMessage());
    }

    protected static function setErrorHeaders($httpResponse, $error, $errorDescription=null)
    {
        if ($errorDescription) {
            parent::setResponseHeaders($httpResponse, $error, $errorDescription);
        }

        $httpResponse->withHeader('X-Error', get_class($error).'['.$error->getCode().']');
    }


}