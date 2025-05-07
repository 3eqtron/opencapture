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
namespace MaarchRM\DataAccess;

/**
 * Event
 *
 * @author ...
 */
class Events
{
    /**
     * @var \PDO The PDO object
     */
    protected $pdo;

    /**
     * @var PDOStatement The pdo select statement
     */
    protected $statements;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $container;

        $this->pdo = $container->get('pdoRm');
    }

    /**
     * Create an event
     * @param object $event
     *
     * @return bool
     */
     
    public function insert($event)
    {
        if (!isset($this->statements['insert'])) {
            $this->statements['insert'] = $this->pdo->prepare('INSERT INTO "Events" ("eventId","eventType","timestamp",
            "instanceName","orgRegNumber","orgUnitRegNumber","accountId","objectClass","objectId","operationResult",
            "description", "eventInfo") VALUES (:eventId,:eventType,:timestamp,:instanceName,:orgRegNumber,
            :orgUnitRegNumber,:accountId,:objectClass,:objectId,:operationResult,:description,:eventInfo)');
        
            $this->statements['insert']->bindValue(':eventId', $event->eventId);
            $this->statements['insert']->bindValue(':eventType', $event->eventType);
            $this->statements['insert']->bindValue(':timestamp', $event->timestamp);
            $this->statements['insert']->bindValue(':instanceName', $event->instanceName);
            $this->statements['insert']->bindValue(':orgRegNumber', $event->orgRegNumber);
            $this->statements['insert']->bindValue(':orgUnitRegNumber', $event->orgUnitRegNumber);
            $this->statements['insert']->bindValue(':accountId', $event->accountId);
            $this->statements['insert']->bindValue(':objectClass', $event->objectClass);
            $this->statements['insert']->bindValue(':objectId', $event->objectId);
            $this->statements['insert']->bindValue(':operationResult', $event->operationResult, \PDO::PARAM_BOOL);
            $this->statements['insert']->bindValue(':description', $event->description);
            $this->statements['insert']->bindValue(':eventInfo', $event->eventInfo);
        }
        $eventId = $event->eventId;

        try {
            $this->statements['insert']->execute();
        } catch (\PDOException $PDOException) {
            var_dump($PDOException);
            list($eventId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);
            throw new $class($eventId);
        }
    }

     /**
     * Read an event
     * @param string $eventId the event ID
     *
     * @return MaarchRM\Business\Events\Events
     */
    public function read($eventId)
    {
        if (!isset($this->statements['read'])) {
            $this->statements['read'] = $this->pdo->prepare('SELECT * FROM "Events" WHERE "eventId"=?');
        }

        if ($this->statements['read']->execute([$eventId])) {
            $data = $this->statements['read']->fetchObject();

            if (!$data) {
                throw new \MaarchRM\Errors\EntityNotFound('Event Format', $eventId);
            }

            $event = new \MaarchRM\Business\Events\Entities\Event($data->eventId);

            $event->eventType = $data->eventType;
            $event->timestamp = $data->timestamp;
            $event->instanceName = $data->instanceName;
            $event->orgRegNumber = $data->orgRegNumber;
            $event->orgUnitRegNumber = $data->orgUnitRegNumber;
            $event->accountId = $data->accountId;
            $event->objectClass = $data->objectClass;
            $event->objectId = $data->objectId;
            $event->operationResult = $data->operationResult;
            $event->description = $data->description;
            $event->eventInfo = $data->eventInfo;

            return $event;
        }
    }

     /**
     * Data access method to update an event
     *
     * @param MaarchRM\Business\Events\Event $event The event object
     * @throws \PDOException
     */

    public function update($event)
    {
        if (!isset($this->statements['update'])) {
            $this->statements['update'] = $this->pdo->prepare('UPDATE "Events" SET "eventType"=?,"timestamp"=?,
            "instanceName"=?,"orgRegNumber"=?,"orgUnitRegNumber"=?,"accountId"=?,"objectClass"=?,
            "objectId"=?,"operationResult"=?,
            "description"=?,"eventInfo"=? WHERE "eventId"=?');
        }
 
        $this->statements['update']->bindValue(1, $event->eventType);
        $this->statements['update']->bindValue(2, $event->timestamp);
        $this->statements['update']->bindValue(3, $event->instanceName);
        $this->statements['update']->bindValue(4, $event->orgRegNumber);
        $this->statements['update']->bindValue(5, $event->orgUnitRegNumber);
        $this->statements['update']->bindValue(6, $event->accountId);
        $this->statements['update']->bindValue(7, $event->objectClass);
        $this->statements['update']->bindValue(8, $event->objectId);
        $this->statements['update']->bindValue(9, $event->operationResult, \PDO::PARAM_BOOL);
        $this->statements['update']->bindValue(10, $event->description);
        $this->statements['update']->bindValue(11, $event->eventInfo);
        $this->statements['update']->bindValue(12, $event->eventId);

        $eventId = $event->eventId;

        try {
            $this->statements['update']->execute();
        } catch (\PDOException $PDOException) {
            list($eventId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($eventId);
        }
    }

    /**
     * Data access method to delete
     *
     * @param string $eventId the event ID
     * @throws \PDOException
     */

    public function delete($eventId)
    {
        if (!isset($this->statements['delete'])) {
            $this->statements['delete'] = $this->pdo->prepare('DELETE FROM "Events" WHERE "eventId" = ?');
        }

        try {
            $this->statements['delete']->execute([$eventId]);
        } catch (\PDOException $PDOException) {
            list($eventId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($eventId);
        }
    }
}
