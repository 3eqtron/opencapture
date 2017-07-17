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
namespace MaarchRM\Business\Users\Entities;

/**
 * The archive Roles
 *
 * @author .....
 */
class RolesAbstract extends \Maarch\BusinessComponents\BusinessEntityAbstract implements \JsonSerializable
{

    /**
     * The role ID
     *
     * @var string
     * @pattern ^[A-Za-z0-9_\-]+$
     * @-readonly
     */
    protected $roleId;

    /**
     * The role name or label
     *
     * @var string
     * @pattern ^[A-Za-z0-9_\-]+$
     * @maxLength 128
     */
    protected $roleName;

     /**
     * The role description
     *
     * @var string
     */
    protected $description;

    /**
     * Enabled
     *
     * @var boolean
     */
    protected $enabled;

    /**
     * Constructor
     * @param string $role The management role code
     */
    public function __construct(string $role)
    {
        $this->__set('roleId', $role);
    }

    /**
     * Serialize to Json
     * @return array
     */
    public function jsonSerialize()
    {
        $return = get_object_vars($this);

        unset($return['roleId']);

        return $return;
    }
}
