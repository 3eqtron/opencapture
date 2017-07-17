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
 * CommunicationMean interface
 *
 * @author ...
 */
interface CommunicationMeanInterface
{
    /**
     * Create a communicationMean
     * @param MaarchRM\Business\Contacts\Entities\CommunicationMean $communication The communicationMean
     */
     
    public function create($communication);

    /**
     * Read a communicationMean
     * @param string $code The conde
     *
     * @return MaarchRM\Business\Contacts\Entities\CommunicationMean
     */
    public function read($code);

    /**
     * Update a communicationMean
     * @param MaarchRM\Business\Contacts\Entities\CommunicationMean $communication The contact communicationMean
     */
    public function update($communication);

    /**
     * Delete a contact
     * @param string $code The code
     */
    public function delete($code);
}
