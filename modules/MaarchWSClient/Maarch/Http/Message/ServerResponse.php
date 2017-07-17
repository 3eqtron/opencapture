<?php
/*
 * Copyright (C) 2015 Maarch
 *
 * This file is part of Maarch.
 *
 * Maarch is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Maarch is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Maarch. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Http\Message;
/**
 * Http wrapper for server outgoing http responses
 *
 */
class ServerResponse
    extends Response
{
    /**
     * @var mixed
     */
    protected $entity;

    /**
     * Return an instance with the specified entity body source
     * @param mixed $entity The data
     * 
     * @return static
     */
    public function withEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get the entity body
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }
}