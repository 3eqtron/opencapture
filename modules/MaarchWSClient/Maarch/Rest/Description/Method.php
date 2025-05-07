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
namespace Maarch\Rest\Description;
use \Maarch\Reflection;
/**
 * Description of the service resource method
 * 
 * @package Maarch
 */
class Method
    extends Reflection\ReflectionMethod
    implements \JsonSerializable
{
    /**
     * Get the name of the method
     * @return string
     */
    public function getName() : string
    { 
        if ($name = strtok($this->docComment->getTag('method'), ' ')) {
            return $name;
        }

        return strtoupper($this->reflection->name);
    }

    /**
     * Get the Resource
     * 
     * @return Maarch\Rest\Description\Resource The resource definition
     */
    public function getResource() : \Maarch\Rest\Description\Resource
    {
        return new Resource($this->reflection->getDeclaringClass());
    }

    /**
     * Get the request
     * 
     * @return Maarch\Http\Description\Request The request definition
     */
    public function getRequest()
    {
        if ($this->docComment->hasTag('request')) {
            $requestId = strtok($this->docComment->getTag('request'), ' ');

            return \Maarch\Rest\App::getRequest($requestId);
        }       
    }

    /**
     * Get the response
     * 
     * @return Maarch\Http\Description\ResponseInterface
     */
    public function getResponse()
    {
        if ($this->docComment->hasTag('response')) {
            $responseId = strtok($this->docComment->getTag('response'), ' ');

            return \Maarch\Rest\App::getResponse($responseId);
        }
    }

    /**
     * Get the errors
     * 
     * @return Maarch\Http\Description\Error
     */
    public function getErrors() : array
    {
        $errors = [];
        
        foreach ($this->docComment->getTags('error') as $errorTag) {
            $errorId = strtok($errorTag, ' ');

            $errors[] = \Maarch\Rest\App::getError($errorId);
        }

        return $errors;
    }

    /**
     * Serialize to json
     * @return array
     */
    public function jsonSerialize()
    {
        $return = [];
        
        if ($request = $this->getRequest()) {
            $return['request'] = $request;
        }

        if ($response = $this->getResponse()) {
            $return['response'] = $response;
        }

        if ($errors = $this->getErrors()) {
            $return['errors'] = $errors;
        }

        return $return;
    }

}