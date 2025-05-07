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

namespace MaarchRM\Business\Events;

/**
 * EventFormat controller
 *
 * @author ...
 */
class EventImpl implements EventInterface
{

    /**
     * Constructor
     */

    public function __construct()
    {
        $this->dataAccess = new \MaarchRM\DataAccess\Events();
    }

    public function create($event)
    {
        $this->dataAccess->insert($event);
    }

    public function delete($eventId)
    {
        $this->dataAccess->delete($eventId);
    }

    public function read($eventId) : \MaarchRM\Business\Events\Entities\Event
    {
        return $this->dataAccess->read($eventId);
    }

    public function update($event)
    {
        $this->dataAccess->update($event);
    }
}
