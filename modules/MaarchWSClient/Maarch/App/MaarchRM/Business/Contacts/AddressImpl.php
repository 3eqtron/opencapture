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

namespace MaarchRM\Business\Contacts;

/**
 *
 * Address controller
 *
 * @author ...
 */
class AddressImpl implements AddressInterface
{

    /**
     * Constructor
     */

    public function __construct()
    {
        $this->dataAccess = new \MaarchRM\DataAccess\Addresses();
    }

    public function create($address)
    {
        $this->dataAccess->insert($address);
    }

    public function delete($contactId)
    {
        $this->dataAccess->delete($contactId);
    }

    public function read($contactId) : \MaarchRM\Business\Contacts\Entities\Address
    {
        return $this->dataAccess->read($contactId);
    }

    public function update($address)
    {
        $this->dataAccess->update($address);
    }
}
