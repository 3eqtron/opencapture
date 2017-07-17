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
 * The Http request parser
 */
class RequestParser
{
    /**
     * Parse the http request
     * @param Psr\Http\Message\ServerRequestInterface  $httpRequest
     * @param Maarch\Http\Description\RequestInterface $requestDescription
     * 
     * @return void
     */
    public static function parseRequest(\Psr\Http\Message\ServerRequestInterface $httpRequest, \Maarch\Http\Description\RequestInterface $requestDescription)
    {
        static::parseQuery($httpRequest, $requestDescription);

        if ($requestDescription) {
            static::parseHeaders($httpRequest, $requestDescription);
        }

        // Parse request entity body into resource
        switch ($httpRequest->getMethod()) {
            case 'GET':
            case 'HEAD':
            case 'OPTIONS':
                break;

            default:
                if ($parsedBody = static::parseEntity($httpRequest, $requestDescription)) {
                    $httpRequest->withParsedBody($parsedBody);
                }
        }
    }

    protected static function parseQuery($httpRequest, $requestDescription=null)
    {
        if (empty($queryString = $httpRequest->getUri()->getQuery())) {
            return;
        }

        if (isset($requestDescription)) {
            $queryType = $requestDescription->getQueryType();
        }
        if (empty($queryType)) {
            $queryType = 'application/x-www-form-urlencoded';
        }

        $queryParser = false;

        if ($queryParser) {
            $queryParams = call_user_func($queryParser, $queryString);
        } else {
            switch ($queryType) {
                case 'application/x-www-form-urlencoded' :
                case 'multipart/form-data' :
                    $queryParams = [];
                    parse_str($queryString, $queryParams);
                    break;

                case 'text/plain' :
                default:
                    return;
            }
        }

        if ($requestDescription && is_array($queryParams)) {
            static::parseQueryParams($requestDescription, $queryParams);
        }

        $httpRequest->withQueryParams($queryParams);
    }

    protected static function parseQueryParams($requestDescription, &$queryParams)
    {
        foreach ($requestDescription->getQueryParams() as $queryParamRefDescription) {
            $name = $queryParamRefDescription->getName();
            $type = $queryParamRefDescription->getType();
            // Query param is described by reference
            if ($type->isClass()) {
                $id = $type->getName();
                $queryParamDescription = App::getQueryParam($id);
                if (isset($queryParams[$name])) {
                    $queryParams[$name] = $queryParamDescription->parse($queryParams[$name]);
                }
            } else {
                if (isset($queryParams[$name])) {
                    switch($type->getName()) {
                        case 'bool':
                            $queryParams[$name] = true;
                            break;

                        case 'int':
                            //$queryParams[$name] = intval($queryParams[$name]);
                            break;

                        case 'float':
                            //$queryParams[$name] = floatval($queryParams[$name]);
                            break;

                        case 'array':
                            //$queryParams[$name] = (array) $queryParams[$name];
                            break;
                    }
                }
            }
        }
    }

    protected static function parseHeaders($httpRequest, $requestDescription)
    {
        foreach ($requestDescription->getHeaders() as $headerRefDescription) {
            $name = $headerRefDescription->getName();
            if ($headerRefDescription->hasType()) {
                $type = $headerRefDescription->getType();
                if ($type->isClass()) {
                    $headerId = $type->getName();
                    $headerDescription = App::getHeader($headerId);
                    if ($httpRequest->hasHeader($name)) {
                        $parsedHeader = $headerDescription->parse($httpRequest->getHeaderLine($name));

                        $httpRequest->withHeader($name, $parsedHeader);
                    }
                }
            }
        }
    }

    protected static function parseEntity($httpRequest, $requestDescription=null)
    {
        $httpRequestBodyStream = $httpRequest->getBody();
        $httpRequestBody = $httpRequestBodyStream->getContents();

        if ($httpRequest->hasHeader('Content-Type')) {
            $contentType = strtok($httpRequest->getHeaderLine('Content-Type'), ';');
        } else {
            $finfo = new \finfo(\FILEINFO_MIME_TYPE);
            $contentType = $finfo->buffer($httpRequestBody);

            $httpRequest->withHeader('Content-Type', $contentType);
        }

        if ($requestDescription && ($representationDescription = static::getRepresentationDescription($contentType, $requestDescription))) {
            return $representationDescription->parse($httpRequestBody);
        } else {
            switch ($contentType) {
                case 'application/x-www-form-urlencoded' :
                case 'multipart/form-data' :
                    return $_POST;
                
                case 'application/json':
                case 'application/javascript':
                case 'text/javascript':
                case 'application/x-javascript':
                case 'text/x-javascript':
                case 'text/x-json':
                    return json_decode($httpRequestBody);

                case 'application/x-empty' :
                case 'text/plain' :
                default:
                    return $httpRequestBody;
            }
        }

        throw new Errors\UnsupportedMediaType("The request entity media type '$contentType' is not supported.");
    }

    protected static function getRepresentationDescription($contentType, $requestDescription) 
    {
        foreach ($requestDescription->getRepresentations() as $representationDescription) {
            if ($mediaType = $representationDescription->getMediaType()) {
                if (fnmatch($contentType, $mediaType)) {
                    return $representationDescription;
                }
            }
        }
    }
}