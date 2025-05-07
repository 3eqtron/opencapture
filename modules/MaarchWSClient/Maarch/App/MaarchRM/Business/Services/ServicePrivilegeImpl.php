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

namespace MaarchRM\Business\Services;

/**
 * Serviceprivilege controller
 *
 * @author ...
 */
class ServicePrivilegeImpl implements ServicePrivilegeInterface
{

    /**
     * Constructor
     */

    public function __construct()
    {
        $this->dataAccess = new \MaarchRM\DataAccess\ServicesPrivilege();
    }

    public function create($service)
    {
        $this->dataAccess->insert($service);
    }

    public function delete($accountId)
    {
        $this->dataAccess->delete($accountId);
    }

    public function read($accountId) : \MaarchRM\Business\Services\Entities\ServicePrivilege
    {
        return $this->dataAccess->read($accountId);
    }

    public function update($service)
    {
        $this->dataAccess->update($service);
    }
}
