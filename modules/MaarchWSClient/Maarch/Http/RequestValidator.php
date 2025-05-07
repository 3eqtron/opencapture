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
 * The request validator
 */
class RequestValidator
{
    /* Constants */

    /* Properties */
    /**
     * The validators
     * @var array
     */
    protected static $validators = [];

    /**
     * @var array The errors
     */
    protected static $errors = [];

    /* Methods */
    /**
     * Validate the request against the request definition
     * @param Psr\Http\Message\ServerRequestInterface  $httpRequest
     * @param Maarch\Http\Description\RequestInterface $requestDescription
     * 
     * @return void
     */
    public static function validateRequest(\Psr\Http\Message\ServerRequestInterface $httpRequest, \Maarch\Http\Description\RequestInterface $requestDescription)
    {
        static::validateRequestHeaders($httpRequest, $requestDescription);
    
        static::validateQueryParams($httpRequest, $requestDescription);

        // Parse request entity body into resource
        switch ($httpRequest->getMethod()) {
            case 'GET':
            case 'HEAD':
            case 'OPTIONS':
                break;

            default:
                static::validateRequestEntity($httpRequest, $requestDescription);
        }

        if (count(static::$errors)) {
            throw new \Maarch\Http\Errors\BadRequest(implode(' ', static::$errors));
        }
    }

    protected static function validateRequestHeaders($httpRequest, $requestDescription)
    {
        foreach ($requestDescription->getHeaders() as $headerRefDescription) {
            if (!$headerRefDescription->isRequired()) {
                continue;
            }

            if ($headerRefDescription->isDefaultValueAvailable()) {
                continue;
            }

            if ($httpRequest->hasHeader($headerRefDescription->getName())) {
                continue;
            }

            static::$errors[] = 'Missing header '.$headerRefDescription->getName();
        }
    }

    protected static function validateQueryParams($httpRequest, $requestDescription)
    {
        $queryParams = $httpRequest->getQueryParams();

        foreach ($requestDescription->getQueryParams() as $queryParamRefDescription) {
            if (!$queryParamRefDescription->isRequired()) {
                continue;
            }

            if ($queryParamRefDescription->isDefaultValueAvailable()) {
                continue;
            }

            if (isset($queryParams[$queryParamRefDescription->getName()])) {
                continue;
            }

            static::$errors[] = 'Missing query parameter '.$queryParamRefDescription->getName();

            // Validate type
        }
    }

    protected static function validateRequestEntity($httpRequest, $requestDescription)
    {
        $contentType = strtok($httpRequest->getHeaderLine('Content-Type'), ';');

        /*if ($requestRepresentationDescription->hasElement()) {
            $element = Parser::$requestRepresentationDescription->getTag('element');

            if (class_exists($element)) {
                $type = Reflection::getClass($element);
            } else {
                $type = Reflection::getType($element);
            }

            static::validate($httpRequest->getParsedBody(), $type);
        }

        $uploadedFiles = $httpRequest->getUploadedFiles();
        if (count($uploadedFiles)) {
            foreach ($uploadedFiles as $uploadedFile) {
                static::validateUploadedFile($uploadedFile);
            }
        }
        */
        
    }

    /**
     * Check the uploaded file
     * @param Psr\Http\Message\UploadedFileInterface $uploadedFile
     * 
     * @return string
     */
    protected static function validateUploadedFile(\Psr\Http\Message\UploadedFileInterface $uploadedFile)
    {
        switch ($uploadedFile->getError()) {
            // 0
            case UPLOAD_ERR_OK:
                return;
            // 1
            case UPLOAD_ERR_INI_SIZE:
                throw new \Maarch\Http\Errors\InternalServerError('The uploaded file exceeds the upload_max_filesize directive in php.ini.');
            // 2
            case UPLOAD_ERR_FORM_SIZE:
                throw new \Maarch\Http\Errors\InternalServerError('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
            // 3
            case UPLOAD_ERR_PARTIAL:
                throw new \Maarch\Http\Errors\InternalServerError('The uploaded file was only partially uploaded.');
            // 4
            case UPLOAD_ERR_NO_FILE:
                throw new \Maarch\Http\Errors\InternalServerError('No file was uploaded.');
            // 5 ???
            // 6
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new \Maarch\Http\Errors\InternalServerError('Missing a temporary folder.');
            // 7
            case UPLOAD_ERR_CANT_WRITE:
                throw new \Maarch\Http\Errors\InternalServerError('Failed to write file to disk.');
            // 8
            case UPLOAD_ERR_EXTENSION:
                throw new \Maarch\Http\Errors\InternalServerError('A PHP extension stopped the file upload.');
        }
    }

}    