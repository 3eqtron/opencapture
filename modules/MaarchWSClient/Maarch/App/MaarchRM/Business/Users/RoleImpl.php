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
 * Bundle MaarchRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MaarchRM. If not, see <http://www.gnu.org/licenses/>.
 */

namespace MaarchRM\Business\Users;

/**
 * Role controller
 *
 * @author ...
 */
class RoleImpl implements RoleInterface
{

    /**
     * Constructor
     */

    public function __construct()
    {
        $this->dataAccess = new \MaarchRM\DataAccess\Roles();
    }

    public function create($role)
    {
        $this->dataAccess->insert($role);
    }

    public function delete($roleId)
    {
        $this->dataAccess->delete($roleId);
    }

    public function read($roleId) : \MaarchRM\Business\Users\Entities\Role
    {
        return $this->dataAccess->read($roleId);
    }

    public function update($role)
    {
        $this->dataAccess->update($role);
    }
}
