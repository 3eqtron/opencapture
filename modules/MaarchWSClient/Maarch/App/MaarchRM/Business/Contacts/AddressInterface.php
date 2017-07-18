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
 * Address interface
 *
 * @author ...
 */
interface AddressInterface
{
    /**
     * Create an Address
     * @param MaarchRM\Business\Contacts\Entities\Address $address The Address
     */
     
    public function create($address);

    /**
     * Read an address
     * @param string $contactId The contact ID
     *
     * @return MaarchRM\Business\Contacts\Entities\Address
     */
    public function read($contactId);

    /**
     * Update an address
     * @param MaarchRM\Business\Contacts\Entities\Address $addressn The contact's address
     */
    public function update($address);

    /**
     * Delete a contact
     * @param string $contactId The contact ID
     */
    public function delete($contactId);
}
