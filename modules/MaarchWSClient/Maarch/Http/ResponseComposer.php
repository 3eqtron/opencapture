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
 * The Http response composer
 */
class ResponseComposer
{
    /**
     * Present the response
     * @param mixed                            $actionReturn
     * @param Maarch\Http\Description\Response $responseDescription
     * 
     * @return Psr\Http\Message\ResponseInterface
     */
    public static function composeResponse($actionReturn, \Maarch\Http\Description\Response $responseDescription)
    {
        // Controller return is a valid http response
        if ($actionReturn instanceof \Psr\Http\Message\ResponseInterface) {
            $httpResponse = $actionReturn;

            if (is_null($httpResponse->getBody()) && $httpResponse instanceof \Maarch\Http\Message\ServerResponse) {
                $entity = $actionReturn->getEntity();
            }
        } else {
            $httpResponse = new Message\Response();
            $entity = $actionReturn;

            // Use response definition to set headers and status code
            if ($responseDescription && $actionReturn) {
                static::setResponseStatus($httpResponse, $actionReturn, $responseDescription);

                static::setResponseHeaders($httpResponse, $actionReturn, $responseDescription);
            }
        }

        // Set standard headers
        static::setMaarchHeaders($httpResponse);

        // Serialize response entity into body string
        if (isset($entity)) {
            static::setResponseBody($httpResponse, $entity, $responseDescription);
        }

        return Server::setResponse($httpResponse);
    }

    protected static function setResponseBody($httpResponse, $entity, $responseDescription)
    {
        switch (Server::getRequest()->getMethod()) {
            case 'HEAD':
            case 'OPTIONS':
                break;

            default:
                if ($stringBody = static::serializeEntity($httpResponse, $entity, $responseDescription)) {
                    $httpResponse->withSerializedBody($stringBody);
                }
        }
    }

    protected static function serializeEntity($httpResponse, $responseEntity, $responseDescription=null)
    {
        $httpRequest = Server::getRequest();
        
        if ($httpRequest->hasHeader('Accept')) {
            $acceptHeader = $httpRequest->getHeaderLine('Accept');
        } else {
            $acceptHeader = '*/*';
        }
        $accepts = new Headers\Accept($acceptHeader);

        if ($responseDescription && is_object($responseEntity) && get_class($responseEntity) == $responseDescription->getId()) {
            $responseRepresentationDescription = static::getResponseRepresentation($accepts, $responseDescription);

            if ($responseRepresentationDescription && !$responseRepresentationDescription->isAbstract()) {
                $httpResponse->withHeader('Content-Type', $responseRepresentationDescription->getMediaType());
    
                // When entity has been received in response, use it as instance
                return call_user_func([$responseEntity, $responseRepresentationDescription->getName()]);
                
                return $responseRepresentationDescription->invoke($responseEntity);
            }
        }

        foreach ($accepts as $accept) {
            switch ($accept->value) {
                case 'application/json':
                    $httpResponse->withHeader('Content-Type', 'application/json');

                    return \Maarch\Json::encode($responseEntity);

                case 'text/plain' :
                    $httpResponse->withHeader('Content-Type', 'text/plain');
                    if (is_scalar($responseEntity)
                        || (is_object($responseEntity) && method_exists($responseEntity, '__toString'))) {
                        return (string) $responseEntity;
                    }

                case '*/*':
                default:
                    $httpResponse->withHeader('Content-Type', 'text/html');
                    if (is_scalar($responseEntity)
                        || (is_object($responseEntity) && method_exists($responseEntity, '__toString'))) {
                        return (string) $responseEntity;
                    }
            }
        }
        

        throw new Errors\NotAcceptable("The accepted response entity media type is not supported: " . $acceptLine);
    }

    protected static function getResponseRepresentation($accepts, $responseDescription)
    {
        $maxq = null;
        $acceptedType = null;
        $acceptedRepresentation = null;
        foreach ($responseDescription->getRepresentations() as $responseRepresentationDescription) {
            if ($responseRepresentationDescription->hasMediaType()) {
                $mediaType = $responseRepresentationDescription->getMediaType();
                foreach ($accepts as $accept) {
                    $q = 1;
                    if (isset($accept->params['q'])) {
                        $q = $accept->params['q'];
                    }
                    if (fnmatch($accept->value, $mediaType)) {
                        if ($q == 1) {
                            return $responseRepresentationDescription;
                        } elseif ($q > $maxq) {
                            $maxq = $q;
                            $acceptedType = $mediaType;
                            $acceptedRepresentation = $responseRepresentationDescription;
                        }
                    }
                }
            }
        }

        return $acceptedRepresentation;
    }

    protected static function setResponseStatus($httpResponse, $actionReturn, $responseDescription)
    {
        if (empty($httpResponse->getStatusCode()) && $responseStatusDescription = $responseDescription->getStatus()) {
            switch (true) {
                case ctype_digit($responseStatusDescription):
                    return $httpResponse->withStatus($responseStatusDescription);

                case $responseStatusDescription instanceof Description\Status:
                    return $httpResponse->withStatus($responseStatusDescription->getCode(), $responseStatusDescription->getReasonPhrase());
                
                case $responseStatusDescription instanceof Description\ResponseStatus:
                    $value = $responseStatusDescription->getCode($actionReturn);
                    if (!is_null($value)) {
                        return $httpResponse->withStatus($value);
                    }
            }
        }
    }

    protected static function setMaarchHeaders($httpResponse)
    {
        $httpResponse->withHeader('X-Processed-By', 'Maarch Rest Server')
                     ->withHeader('X-Process-Time', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
                     //->withHeader('X-Memory-Usage', memory_get_peak_usage());
    }

    protected static function setResponseHeaders($httpResponse, $actionReturn, $responseDescription)
    {
        // Get headers from properties and hooks
        foreach ($responseDescription->getHeaders() as $headerRefDescription) {
            $name = $headerRefDescription->getName();
            $value = $headerRefDescription->getValue($actionReturn);
            
            if (!is_null($value)) {
                $httpResponse->withHeader($name, (string) $value);
            }
        }
    }

    /**
     * Get a response header
     * @param string $name
     * @param mixed  $responseEntity
     * 
     * @return mixed
     */
    protected static function getResponseHeader($name, $responseEntity)
    {
        $value = null;

        switch (true) {
            case (property_exists($responseEntity, $name)) :
                $value = $responseEntity->{$name};
                break;

            case (method_exists($responseEntity, $name)) :
                $value = $responseEntity->{$name}();
                break;
        }

        return $value;
    }    
}