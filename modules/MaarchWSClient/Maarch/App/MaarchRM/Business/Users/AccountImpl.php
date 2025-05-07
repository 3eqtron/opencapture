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
 * Account controller
 *
 * @author Alexis Ragot <alexis.ragot@maarch.org>
 */
class AccountImpl implements AccountInterface
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dataAccess = new \MaarchRM\DataAccess\Accounts();
    }

    public function create($account)
    {
        $this->dataAccess->insert($account);
    }

    public function delete($accountName)
    {
        $this->dataAccess->delete($accountName);
    }

    public function read($accountName) : \MaarchRM\Business\Users\Entities\Account
    {
        return $this->dataAccess->read($accountName);
    }

    public function update($account)
    {
        $this->dataAccess->update($account);
    }
}
