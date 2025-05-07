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
 * ServicePrivilege interface
 *
 * @author ...
 */
interface ServicePrivilegeInterface
{
    /**
     * Create a servicePrivilege
     * @param MaarchRM\Business\RolesMembers\Entities\ServicePrivilege $service The servicePrivilege
     */
     
    public function create($service);

    /**
     * Read an account ID
     * @param string $roleId The account ID
     *
     * @return MaarchRM\Business\Services\Entities\ServicePrivilege
     */
    public function read($accountId);

    /**
     * Update an account ID
     * @param MaarchRM\Business\Services\Entities\ServicePrivilege $service The user servicePrivilege
     */
    public function update($service);

    /**
     * Delete a servicePrivilege
     * @param string $accountId The account ID
     */
    public function delete($accountId);
}
