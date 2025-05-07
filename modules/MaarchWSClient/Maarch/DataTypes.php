<?php
/*
 * Copyright (C) 2017 Maarch
 *
 * This file is part of Maarch.
 *
 * Maarch is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Maarch is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Maarch. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch;

/**
 * The derived data types
 * 
 * Maarch provides the definition for these derived types
 * using the syntax.
 * 
 * @author Cyril Vazquez (Maarch) <cyril.vazquez@maarch.org>
 */
abstract class DataTypes
{
    /* ************************************************************************
     * 
     *      String
     *
     * ***********************************************************************/
    /**
     * @var string
     * @pattern ^[A-z0-9\+\/\=]*$
     */
    public $base64;

    /**
     * @var string
     * @pattern ^[A-z0-9_\-\=]*$
     */
    public $base64url;

    /**
     * @var string
     * @pattern ^[A-z0-9_\-\.]*$
     */
    public $normalizedName;

    /* ************************************************************************
     * 
     *      Date and time
     *
     * ***********************************************************************/
    /**
     * @var string
     * @pattern ^\d{4}$
     */
    public $year;

    /**
     * @var string
     * @pattern ^(0[1-9]|1[012])$
     */
    public $month;

    /**
     * @var string
     * @pattern ^(0[1-9]|[12][0-9]|3[01])$
     */
    public $day;

    /**
     * @var string
     * @pattern ^\d{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])$
     */
    public $date;

    /**
     * @var string
     * @pattern ^([01]\d|2[0-3])\:[0-5]\d\:[0-5]\d(.\d{1,6})?$
     */
    public $time;

    /**
     * @var string
     * @pattern ^(Z|(\+|\-)([01]\d|2[0-3])\:[0-5]\d)$
     */
    public $timezone;

    /**
     * @var string
     * @pattern ^\d{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])T([01]\d|2[0-3])\:[0-5]\d\:[0-5]\d(.\d{1,6})?((Z|(\+|\-)([01]\d|2[0-3])\:[0-5]\d))?$
     */
    public $datetime;

    /**
     * @var string
     * @pattern ^(\-)?P(\d+Y)?(\d+M)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$
     */
    public $duration;

    /* ************************************************************************
     * 
     *      Numbers
     *
     * ***********************************************************************/
    /**
     * @var int
     * @maxValue 0
     */
    public $nonPositiveInteger;

    /**
     * @var int
     * @maxValue -1
     */
    public $negativeInteger;

    /**
     * @var int
     * @minValue 0
     */
    public $nonNegativeInteger;

    /**
     * @var int
     * @minValue 1
     */
    public $positiveInteger;
}