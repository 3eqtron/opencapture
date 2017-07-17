<?php
/*
 * Copyright (C) 2015 Maarch
 *
 * This file is part of Maarch.
 *
 * Bundle recordsManagement is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Bundle recordsManagement is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Maarch.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Rest\Responses;
/**
 * Standard response for successful HTTP requests. 
 * The actual response will depend on the request method used. 
 * In a GET request, the response will contain an entity corresponding to the requested resource. 
 * In a POST request, the response will contain an entity describing or containing the result of the action.
 *
 * @package Maarch
 * @author  Cyril VAZQUEZ (Maarch) <cyril.vazquez@maarch.org>
 * 
 * @code 200
 */
class OK
    extends AbstractSuccess
{
    /**
     * @var string The acccepted range unit
     * @header
     */
    public $acceptRange;

    /**
     * @var string The acccepted patch
     * @header
     */
    public $acceptPatch;

    /**
     * @var integer The count of items
     * @header
     */
    public $contentCount;

    /**
     * @var array The sort arguments
     * @header
     */
    public $contentSort;

    /**
     * @var string The range of items
     * @header
     */
    public $contentRange;

    /**
     * @var string The hash of object
     * @header
     */
    public $ETag;
}