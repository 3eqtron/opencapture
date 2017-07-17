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
/**
 * The Rest router
 */
class Router
{
    /**
     * @var Maarch\Rest\Resource
     */
    protected static $startResource;

    /**
     * @var Maarch\Rest\RouteStep[]
     */
    protected static $steps;

    /**
     * @var Maarch\Rest\Resource
     */
    protected static $targetResource;

    /**
     * @var Maarch\Rest\Description\Method
     */
    protected static $method;

    /**
     * @var Maarch\Http\Description\Request
     */
    protected static $request;

    /**
     * @var Maarch\Http\Description\Response
     */
    protected static $response;

    /**
     * Route the request
     * 
     * @return Route
     */
    public static function routeRequest()
    {
        $httpRequest = Server::getRequest();

        static::routePath();

        static::routeMethod();

        $context = [
            'method' => $httpRequest->getMethod(),
            'path' => $httpRequest->getUri()->getPath(),
            'action' => static::$method->getId()
            ];
        //App::getLogger()->info('Maarch\Rest\Router : {method} {path} -> {action}', $context);
    }

    /**
     * Route the request to resource path only
     * 
     * @return Route
     */
    public static function routePath()
    {
        $httpRequest = Server::getRequest();

        if (static::routeResource(App::getStart())) {
            return static::$targetResource;
        }

        $message = "Resource not found at path '".$httpRequest->getUri()->getPath()."'.";
        $throwable = new \Maarch\Http\Errors\NotFound($message);
        $throwable->path = $httpRequest->getUri()->getPath();

        //App::getLogger()->error('Maarch\Rest\Router : '.$message);
        
        throw $throwable;
    }

    /**
     * Returns the steps
     * @return Maarch\Rest\RouteStep[]
     */
    public static function getSteps()
    {
        return static::$steps;
    }

    /**
     * Returns the target resource
     * @return Maarch\Rest\Description\Resource
     */
    public static function getTargetResource()
    {
        return static::$targetResource;
    }

    /**
     * Returns the method
     * @return Maarch\Rest\Description\Method
     */
    public static function getMethod()
    {
        return static::$method;
    }

    /**
     * Returns the request
     * @return Maarch\Http\Description\RequestInterface
     */
    public static function getRequest()
    {
        return static::$request;
    }

    /**
     * Returns the response or error
     * @return Maarch\Http\Description\ResponseInterface
     */
    public static function getResponse()
    {
        return static::$response;
    }

    /**
     * Route the error for the method
     * @param \Throwable $error The error
     * 
     * @return Maarch\Rest\Description\Error The target error
     */
    public static function routeError(\Throwable $error)
    {
        foreach (static::$method->getErrors() as $errorDescription) {
            $errorId = get_class($error);
            if ($errorDescription->getId() == $errorId) {
                $context = ['error' => $errorId];
                //App::getLogger()->info('Maarch\Rest\Router : Business error {error} selected.', $context);

                return static::$response = $errorDescription;
            }
        }
    }

    /**
     * Route the data request and fill parameters
     * @param Maarch\Rest\Description\Resource $resource    The reflection resource
     * @param string                           $contextPath The previous resource path
     * 
     * @return Maarch\Rest\Description\Resource
     */
    protected static function routeResource(Description\Resource $resource, $contextPath=false)
    {
        $httpRequest = Server::getRequest();

        foreach ($resource->getResourceLinks() as $resourceLink) {
            // Make unique indentifier to identify matched parts
            $uid = '__'.rand();
            
            // If relative path, make new pattern.
            // Current path template values are captured by upper call to routeResource
            // New path template values will be captures during this call so named
            $resourcePath = $resourceLink->getPath();
            if ($resourcePath && $resourcePath[0] == '/') {
                $pathPattern = '#^(?<step'.$uid.'>'.preg_replace('#\{(\w+)\}#', '(?<$1>\w+)', $resourcePath).'(?<matrix'.$uid.'>(;[^\/]+)*)?)(?<tail'.$uid.'>\/.+)*$#i';
                $relative = false;
            } elseif ($resourcePath) {
                $pathPattern = '#^'.$contextPath.'/(?<step'.$uid.'>'.preg_replace('#\{(\w+)\}#', '(?<$1>\w+)', $resourcePath).'(?<matrix'.$uid.'>(;[^\/]+)*)?)(?<tail'.$uid.'>\/.+)*$#i';
                $relative = true;
            }

            // Match and capture new resource relative path template values
            if (preg_match($pathPattern, $httpRequest->getUri()->getPath(), $matchedPath)) {
                $linkedResource = $resourceLink->getResource();
                $resourceParams = static::getResourceParams($linkedResource, $matchedPath, $uid);

                if ($relative) {
                    $contextPath = $contextPath.'/'.$matchedPath['step'.$uid];
                    static::$steps[$contextPath] = new RouteStep($resourceLink, $linkedResource, $resourceParams);
                } else {
                    $contextPath = $matchedPath['step'.$uid];
                    static::$steps->exchangeArray([$contextPath => new RouteStep($resourceLink, $linkedResource, $resourceParams)]);
                }               
                
                // No tail : full path match
                // Tail indicates that the route is not fully matched and is recursive
                if (!isset($matchedPath['tail'.$uid])) {
                    static::$targetResource = $linkedResource;

                    return true;
                }
                if (static::routeResource($linkedResource, $contextPath)) {
                    return true;
                }

                // Tail and linked resource did not match, remove current step
                unset(static::$steps[$contextPath]);
            }
        }
    }

    /**
     * Extract the resource parameters
     * @param Maarch\Rest\Description\Resource $resource    The reflection resource
     * @param array                            $matchedPath The matched path
     * @param string                           $uid         The unique identifier for the path step
     * 
     * @return array
     */
    protected static function getResourceParams(Description\Resource $resource, array $matchedPath, string $uid)
    {
        $httpRequest = Server::getRequest();

        $resourceParams = [];

        $matrixValues = [];
        if (!empty($matchedPath['matrix'.$uid])) {
            foreach (explode(';', $matchedPath['matrix'.$uid]) as $matrixParam) {
                $name = strtok($matrixParam, '=');
                $value = strtok('');
                if (empty($value)) {
                    $value = true;
                }

                $matrixValues[$name] = $value;
            }
        }

        foreach ($resource->getAttributes() as $resourceAttribute) {
            $style = $resourceAttribute->getStyle();
            $name = $resourceAttribute->getName();
            switch ($style) {
                case 'template':
                    if (isset($matchedPath[$name])) {
                        $httpRequest->withAttribute($name, $matchedPath[$name]);
                        $resourceParams[$name] = $matchedPath[$name];
                    }
                    break;

                case 'matrix':
                    if (array_key_exists($name, $matrixValues)) {
                        $httpRequest->withAttribute($name, $matrixValues[$name]);
                        $resourceParams[$name] = $matrixValues[$name];
                    }
                    break;
            }
        }

        return $resourceParams;
    }

    /**
     * Route the method for the resource
     * 
     * @return Maarch\Rest\Description\Method The target method
     */
    protected static function routeMethod()
    {
        $httpRequest = Server::getRequest();
        
        foreach (static::$targetResource->getMethods() as $methodDescription) {
            if ($methodDescription->getName() == $httpRequest->getMethod()) {
                static::$request = $methodDescription->getRequest();
                static::$response = $methodDescription->getResponse();

                return static::$method = $methodDescription;
            }
        }

        $message = "Method '".$httpRequest->getMethod()."' not allowed for resource '".$httpRequest->getUri()->getPath()."'.";
        //App::getLogger()->error('Maarch\Rest\Router : '.$message);

        $throwable = new \Maarch\Http\Errors\MethodNotAllowed($message);

        throw $throwable;
    }
}