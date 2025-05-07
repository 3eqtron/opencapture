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
namespace Maarch\Rest;
use \Maarch\Http;
/**
 * The REST server
 */
class ActionExecutor
{
    /**
     * Execute the action
     * 
     * @return mixed
     */
    public static function executeAction()
    {
        // Execute the action and get the data
        // Catch business exceptions
        $resourceDescription = Router::getTargetResource();
        $methodDescription = Router::getMethod();

        $resource = static::getController($resourceDescription);
        $actionArgs = static::getActionArgs($methodDescription);

        try {
            // Call user func instead of reflection to allow use of magic method __call
            return call_user_func_array([$resource, $methodDescription->getName()], $actionArgs);
            
            return $methodDescription->invokeArgs($resource, $actionArgs);

        } catch (\Throwable $businessException) {
            if (Router::routeError($businessException)) {
                
                return $businessException;

            } else {
                throw $businessException;
            }
        }        
    }

    protected static function getController($resourceDescription)
    {
        $resourceArgs = [];
        foreach ($resourceDescription->getAttributes() as $resourceAttributeDescription) {
            $resourceArgs[] = static::getRequestAttribute($resourceAttributeDescription);
        }
        // Backward remove null values from array
        do {
            $resourceArg = end($resourceArgs);
            if ($resourceArg === null) {
                array_pop($resourceArgs);
            }
        } while ($resourceArg === null && count($resourceArgs));

        return $resourceDescription->newInstance($resourceArgs);
    }

    protected static function getActionArgs($methodDescription)
    {
        $actionArgs = [];
        foreach ($methodDescription->getParameters() as $parameterDescription) {
            $actionArgs[] = static::getActionArg($parameterDescription);
        }
        // Backward remove null values from array
        do {
            $actionArg = end($actionArgs);
            if ($actionArg === null) {
                array_pop($actionArgs);
            }
        } while ($actionArg === null && count($actionArgs));

        return $actionArgs;
    }

    protected static function getActionArg($parameterDescription)
    {
        switch ($parameterDescription->getStyle()) {
            // Full request object
            case 'request':
                return Server::getRequest();

            // New response object
            case 'response':
                return new \Maarch\Http\Message\Response();

            // Header param (parsed)
            case 'header':
                return static::getHeaderParam($parameterDescription);

            // Query param
            case 'query':
                return static::getQueryParam($parameterDescription);

            // Full query string
            case 'queryString':
                return Server::getRequest()->getUri()->getQuery();

            // Full entity body
            case 'entity':
                return Server::getRequest()->getParsedBody();

            // Entity property
            case 'property':
                return static::getProperty($parameterDescription);

            // Path attribute
            case 'attribute':
                return static::getRequestAttribute($parameterDescription);

            // All path attributes
            case 'attributes':
                return Server::getRequest()->getAttributes();

            // Cookie param
            case 'cookie':
                return static::getCookieParam($parameterDescription);

            // All Cookies
            case 'cookies':
                return Server::getRequest()->getCookieParams();

            // Server param
            case 'server':
                return static::getServerParam($parameterDescription);

            // File
            case 'file':
                return static::getFile($parameterDescription);

            // All files
            case 'files':
                return Server::getRequest()->getUploadedFiles();


            default:
                if ($parameterDescription->isDefaultValueAvailable()) {
                    return $parameterDescription->getDefaultValue();
                }
        }
    }

    protected static function getRequestAttribute($resourceAttributeDescription)
    {
        $default = null;
        if ($resourceAttributeDescription->isDefaultValueAvailable()) {
            $default = $resourceAttributeDescription->getDefaultValue();
        }

        return Server::getRequest()->getAttribute($resourceAttributeDescription->getName(), $default);
    }

    protected static function getHeaderParam($headerParamDescription)
    {
        $name = $headerParamDescription->getName();

        switch (true) {
            case $headerParamDescription->isClass():
            case $headerParamDescription->isArray(): 
                if (Server::getRequest()->hasHeader($name)) {
                    return Server::getRequest()->getHeader($name);
                }
                break;
        
            default:
                if (Server::getRequest()->hasHeader($name)) {
                    return Server::getRequest()->getHeaderLine($name);
                }
        }
        
        if ($headerParamDescription->isDefaultValueAvailable()) {
            return $headerParamDescription->getDefaultValue();
        }
    }

    protected static function getQueryParam($queryParamDescription)
    {
        $queryParams = Server::getRequest()->getQueryParams();
        
        $name = $queryParamDescription->getName();

        if (isset($queryParams[$name])) {
            return $queryParams[$name];
        }

        if ($queryParamDescription->isDefaultValueAvailable()) {
            return $queryParamDescription->getDefaultValue();
        }
    }

    protected static function getCookieParam($cookieParamDescription)
    {
        $cookieParams = Server::getRequest()->getCookieParams();
        $name = $cookieParamDescription->getName();

        if (isset($cookieParams[$name])) {
            return $cookieParams[$name];
        }

        if ($cookieParamDescription->isDefaultValueAvailable()) {
            return $cookieParamDescription->getDefaultValue();
        }
    }

    protected static function getServerParam($serverParamDescription)
    {
        $httpServerParams = Server::getRequest()->getServerParams();
        $name = $serverParamDescription->getName();

        if (isset($httpServerParams[$name])) {
            return $httpServerParams[$name];
        }

        if ($serverParamDescription->isDefaultValueAvailable()) {
            return $serverParamDescription->getDefaultValue();
        }
    }

    protected static function getProperty($resourcePropertyDescription)
    {
        $parsedBody = Server::getRequest()->getParsedBody();
        $name = $resourcePropertyDescription->getName();

        switch (true) {
            case is_array($parsedBody) && isset($parsedBody[$name]):
            case is_object($parsedBody) && $parsedBody instanceof \ArrayAccess && isset($parsedBody[$name]):
                return $parsedBody[$name];

            case is_object($parsedBody) && isset($parsedBody->{$name}):
                return $parsedBody->{$name};
        }

        if ($resourcePropertyDescription->isDefaultValueAvailable()) {
            return $resourcePropertyDescription->getDefaultValue();
        }
    }

    protected static function getFile($uploadedFileParamDescription)
    {
        $uploadedFiles = Server::getRequest()->getUploadedFiles();
        
        $name = $uploadedFileParamDescription->getName();

        if (isset($uploadedFiles[$name])) {
            return $uploadedFiles[$name];
        }

        if ($uploadedFileParamDescription->isDefaultValueAvailable()) {
            return $uploadedFileParamDescription->getDefaultValue();
        }
    }
}