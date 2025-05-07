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
 * A response for Options
 *
 * @package Maarch
 * @author  Cyril VAZQUEZ (Maarch) <cyril.vazquez@maarch.org>
 * 
 * @code 200
 */
class Options
{
    /* Properties */
    /**
     * @var string The allowed methods
     * @header
     */
    public $allow;

    /**
     * @var string The allowed patch media types
     * @header
     */
    public $acceptPatch;

    /**
     * @var string The allowed range types
     * @header
     */
    public $acceptRange;

    /**
     * Constructor
     * @param array $methods     The available methods
     * @param array $acceptRange The accepted range types (units) 
     * @param array $acceptPatch The media types that can be used to patch resource if available
     */
    public function __construct(array $methods=[], array $acceptRange=null, array $acceptPatch=null)
    {
        $this->allow = implode(', ', $methods);

        if (is_array($acceptRange)) {
            $this->acceptRange = implode(', ', $acceptRange);
        }

        if (is_array($acceptPatch)) {
            $this->acceptPatch = implode(', ', $acceptPatch);
        }
    }
}