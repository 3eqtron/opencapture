<?php
/*
 * Copyright (C) 2016 Maarch
 *
 * This file is part of Dice.
 *
 * Dice is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Dice is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Dice.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace MaarchRM\Rest;
/**
 * MaarchRM
 * 
 * @package MaarchRM
 * @author  Cyril VAZQUEZ (Maarch) <cyril.vazquez@maarch.org>
 * 
 */
abstract class Resources
{
    /* Resources */
    /**
     * @var MaarchRM\Rest\Controllers\RetentionRules The retention rules
     * @path retentionRules
     */
    public $retentionRules;

    /**
     * @var MaarchRM\Rest\Controllers\AccessRules The access rules
     * @path accessRules
     */
    public $accessRules;
}