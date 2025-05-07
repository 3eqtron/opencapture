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
 * The archive profile
 * 
 * @author Cyril Vazquez <cyril.vazquez@maarch.org>
 */
class ArchivalProfile
    extends \Maarch\BusinessEntityAbstract
{
    /**
     * @var string
     * @format id
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * An associative array of management rules to be applied on archive units
     * @var array
     */
    protected $managementProfile;

    /**
     * The data profile for description
     * 
     * The data profile is an array of description fields
     * Each field can be a simple or complex definition
     * 
     * @var Maarch\Metamodel\Attribute[]
     */
    protected $dataProfile = [];

    /**
     * The description of the contents
     * 
     * The contents are an array of other archive profiles
     * that describe the archive units contained by the currently
     * described archive units.
     * 
     * @var MaarchRM\BusinessEntities\ArchivalProfile[]
     */
    protected $contentsProfile;

    /**
     * The access control list
     * 
     * The access control list is an array of access control entries
     * used to grant access to archive units for organisational units
     * members.
     * 
     * @var array
     */
    protected $accessControlList;
}
