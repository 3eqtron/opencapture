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
 * Contact interface
 *
 * @author ...
 */
interface ContactInterface
{
    /**
     * Create a role member
     * @param MaarchRM\Business\Contacts\Entities\Contact $contact The contact
     */
     
    public function create($contact);

    /**
     * Read a contact ID
     * @param string $contactId The contact ID
     *
     * @return MaarchRM\Business\Contacts\Entities\Contact
     */
    public function read($contactId);

    /**
     * Update a contact
     * @param MaarchRM\Business\Contacts\Entities\Contact $contact The user role
     */
    public function update($contact);

    /**
     * Delete a contact
     * @param string $roleId The contact ID
     */
    public function delete($contactId);
}
