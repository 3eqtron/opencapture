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
class Server
    extends \Maarch\Http\Server
{
    /**
     * Processes the request
     * @param Psr\Http\Message\ServerRequestInterface $request
     * 
     * @return Psr\Http\Message\ResponseInterface
     */
    public static function process(\Psr\Http\Message\ServerRequestInterface $request) : \Psr\Http\Message\ResponseInterface
    {
        static::$request = $request;

        if (empty(static::$response)) {
            static::$response = new Http\Message\Response();
        }

        try {
            switch (static::$request->getMethod()) {
                case 'OPTIONS':
                    // Route the request resource only
                    Router::routePath();
                    
                    DiscoveryComposer::composeOptions();
                    break;

                case 'GET':     // Read the resource (id) or a collection/range (no id, header range)
                case 'POST':    // Create a new resource (entity)
                case 'PUT':     // Update a resource (id, entity)
                case 'DELETE':  // Delete a resource (id) or a collection/range (no id, header range)
                case 'HEAD':    // Test the resource (id) or a collection/range (no id, header range)
                //case 'PATCH':   // Partially update a resource (id, entity, header range)             : use PUT + range instead
                //case 'LINK':    // Link a resource to another (header Link)                           : use POST+subresource link instead
                //case 'UNLINK':  // Unlink a resource from another (header Link)                       : use DELETE+subresource link instead
                //case 'PURGE':   // Purge a resource in cache (id) or a collection (no id)             
                //case 'COPY':    // Copy a resource to another (header Destination)                    
                //case 'LOCK':    // Lock a resource or a collection/range (no id, header range)
                //case 'UNLOCK':  // Unlock a resource or a collection/range (no id, header range)
                //case 'PROPFIND':  // See the members of a resource or collection (id, header range)   : use GET+subresource instead
                default:
                    static::processBusinessRequest();
            }

        } catch (Http\Errors\ErrorAbstract $processError) {
            // Compose response for undeclared error but http error 
            Http\ErrorComposer::composeError($processError);
        
        } /*catch (\Throwable $throwable) {
            // Compose response for undeclared error and unexpected throwable 
            $unexpectedError = new \Maarch\Http\Errors\InternalServerError('An unexpected error occured.', 500, $throwable);
            
            Http\ErrorComposer::composeError($unexpectedError);
        }*/

        static::$response->withHeader('X-Process-Time', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);

        return static::$response;
    }

    protected static function processBusinessRequest()
    {
        // Route the request method
        Router::routeRequest();
        $requestDescription = Router::getRequest();

        // Check identification, authentication and authorizations on resource/method
        Auth::checkAuthentication();

        if ($requestDescription) {
            // Parse the query string into server query object
            // Parse the request entity into resource
            Http\RequestParser::parseRequest(static::$request, $requestDescription);

            // Validate query, header and template params VS resource/request definition
            // Validate entity body vs schema
            Http\RequestValidator::validateRequest(static::$request, $requestDescription);
        }

        // Execute the action and get the returned data or http response object
        $actionReturn = ActionExecutor::executeAction();
        
        // Compose response for normal action return or declared error 
        Http\ResponseComposer::composeResponse($actionReturn, Router::getResponse());
    }

    
}