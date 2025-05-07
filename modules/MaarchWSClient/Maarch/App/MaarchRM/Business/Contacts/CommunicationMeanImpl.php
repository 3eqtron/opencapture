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
 * CommunicationMean controller
 *
 * @author ...
 */
class CommunicationMeanImpl implements CommunicationMeanInterface
{

    /**
     * Constructor
     */

    public function __construct()
    {
        $this->dataAccess = new \MaarchRM\DataAccess\CommunicationsMean();
    }

    public function create($communication)
    {
        $this->dataAccess->insert($communication);
    }

    public function delete($code)
    {
        $this->dataAccess->delete($code);
    }

    public function read($code) : \MaarchRM\Business\Contacts\Entities\CommunicationMean
    {
        return $this->dataAccess->read($code);
    }

    public function update($communication)
    {
        $this->dataAccess->update($communication);
    }
}
