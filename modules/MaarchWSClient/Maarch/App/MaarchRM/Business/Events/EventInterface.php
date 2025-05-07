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
 * Event interface
 *
 * @author ...
 */
interface EventInterface
{
    /**
     * Create an event
     * @param MaarchRM\Business\Events\Entities\Event $event The event
     */
     
    public function create($event);

    /**
     * Read an event
     * @param string $eventId The event ID
     *
     * @return MaarchRM\Business\Events\Entities\Event
     */
    public function read($eventId);

    /**
     * Update an event
     * @param MaarchRM\Business\Events\Entities\Event $event The event
     */
    public function update($event);

    /**
     * Delete an event
     * @param string $eventId The event ID
     */
    public function delete($eventId);
}
