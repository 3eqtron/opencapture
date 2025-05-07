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
 * EventFormat
 *
 * @author ...
 */
class EventsFormat
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
     * Create an eventFormat
     * @param object $event
     *
     * @return bool
     */
     
    public function insert($event)
    {
        if (!isset($this->statements['insert'])) {
            $this->statements['insert'] = $this->pdo->prepare('INSERT INTO "EventsFormat" ("type","format","message",
            "notification") VALUES (:type,:format,:message,:notification)');
        
            $this->statements['insert']->bindValue(':type', $event->type);
            $this->statements['insert']->bindValue('format', $event->format);
            $this->statements['insert']->bindValue(':message', $event->message);
            $this->statements['insert']->bindValue(':notification', $event->notification, \PDO::PARAM_BOOL);
        }
        $type = $event->type;

        try {
            $this->statements['insert']->execute();
        } catch (\PDOException $PDOException) {
            var_dump($PDOException);
            list($type, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);
            throw new $class($type);
        }
    }

     /**
     * Read an event format
     * @param string $type The type
     *
     * @return MaarchRM\Business\Events\EventsFormat
     */
    public function read($type)
    {
        if (!isset($this->statements['read'])) {
            $this->statements['read'] = $this->pdo->prepare('SELECT * FROM "EventsFormat" WHERE "type"=?');
        }

        if ($this->statements['read']->execute([$type])) {
            $data = $this->statements['read']->fetchObject();

            if (!$data) {
                throw new \MaarchRM\Errors\EntityNotFound('Event Format', $type);
            }

            $event = new \MaarchRM\Business\Events\Entities\EventFormat($data->type);

            $event->format = $data->format;
            $event->message = $data->message;
            $event->notification = $data->notification;

            return $event;
        }
    }

     /**
     * Data access method to update an event Format
     *
     * @param MaarchRM\Business\Events\EventFormat $rcontact The event Format object
     * @throws \PDOException
     */

    public function update($event)
    {
        if (!isset($this->statements['update'])) {
            $this->statements['update'] = $this->pdo->prepare('UPDATE "EventsFormat" SET "format"=?,"message"=?,
            "notification"=? WHERE "type"=?');
        }
 
        $this->statements['update']->bindValue(1, $event->format);
        $this->statements['update']->bindValue(2, $event->message);
        $this->statements['update']->bindValue(3, $event->notification, \PDO::PARAM_BOOL);
        $this->statements['update']->bindValue(4, $event->type);


        $type = $event->type;

        try {
            $this->statements['update']->execute();
        } catch (\PDOException $PDOException) {
            list($type, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($type);
        }
    }

    /**
     * Data access method to delete
     *
     * @param string $type The type
     * @throws \PDOException
     */

    public function delete($type)
    {
        if (!isset($this->statements['delete'])) {
            $this->statements['delete'] = $this->pdo->prepare('DELETE FROM "EventsFormat" WHERE "type" = ?');
        }

        try {
            $this->statements['delete']->execute([$type]);
        } catch (\PDOException $PDOException) {
            list($type, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($type);
        }
    }
}
