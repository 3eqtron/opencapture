<?php
/*
 * Copyright (C) 2016 Maarch
 *
 * This file is part of MaarchRM.
 *
 * MaarchRM is a free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MaarchRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MaarchRM. If not, see <http://www.gnu.org/licenses/>.
 */
namespace MaarchRM\BusinessEntities;
/**
 * The metamodel description field
 * 
 * @author Cyril Vazquez <cyril.vazquez@maarch.org>
 */
class descriptionField
{
    /**
     * The name of the property
     *
     * @var string
     * @notempty
     */
    public $name;

    /**
    * @var string The label for users
    * @notempty
    */
    public $label;

    /**
    * @var string The type of data : string, integer, float, boolean, object
    * @notempty
    */
    public $type;

    /**
    * @var string The default value
    */
    public $default;

    /**
    * @var integer
    */
    public $minLength;

    /**
    * @var integer
    */
    public $maxLength;

    /**
    * @var float
    */
    public $minValue;

    /**
    * @var float
    */
    public $maxValue;

    /**
    * @var string
    */
    public $enumeration;

    /**
    * @var string
    */
    public $pattern;
}
