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
 * MaarchRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MaarchRM. If not, see <http://www.gnu.org/licenses/>.
 */
namespace MaarchRM\Business\Events\Entities;

/**
 * The archive Events
 *
 * @author .....
 */
class EventsAbstract extends \Maarch\BusinessComponents\BusinessEntityAbstract implements \JsonSerializable
{

    /**
     * The event ID
     *
     * @var string
     * @-readonly
     */
    protected $eventId;

    /**
     * the event type
     *
     * @var string
     */
    protected $eventType;

     /**
     * the timestamp
     * @var string
     */
    protected $timestamp;

    /**
     * the instanceName
     *
     * @var string
     */
    protected $instanceName;

    /**
     * the orgRegNumber
     *
     * @var string
     */
    protected $orgRegNumber;

    /**
     * the orgUnitRegNumber
     *
     * @var string
     */
    protected $orgUnitRegNumber;

    /**
     * the accountId
     *
     * @var string
     */
    protected $accountId;

    /**
     * the objectClass
     *
     * @var string
     */
    protected $objectClass;

    /**
     * the objectId
     *
     * @var string
     */
    protected $objectId;

    /**
     * the operationResult
     *
     * @var boolean
     */
    protected $operationResult;

    /**
     * the description
     *
     * @var string
     */
    protected $description;

    /**
     * the eventInfo
     *
     * @var string
     */
    protected $eventInfo;

    /**
     * Constructor
     * @param string $event The event format
     */
    public function __construct(string $event)
    {
        $this->__set('eventId', $event);
    }

    /**
     * Serialize to Json
     * @return array
     */
    public function jsonSerialize()
    {
        $return = get_object_vars($this);

        unset($return['eventId']);

        return $return;
    }
}
