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
 * Contact
 *
 * @author ...
 */
class Contacts
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
     * Create a contact
     * @param object $contact
     *
     * @return bool
     */
     
    public function insert($contact)
    {
        if (!isset($this->statements['insert'])) {
            $this->statements['insert'] = $this->pdo->prepare('INSERT INTO "Contacts" ("contactId","contactType","orgName",
            "firstName","lastName","title","function","service","displayName") VALUES (:contactId,:contactType,:orgName,
            :firstName,:lastName,:title,:function,:service,:displayName)');
        
            $this->statements['insert']->bindValue(':contactId', $contact->contactId);
            $this->statements['insert']->bindValue(':contactType', $contact->contactType);
            $this->statements['insert']->bindValue(':orgName', $contact->orgName);
            $this->statements['insert']->bindValue(':firstName', $contact->firstName);
            $this->statements['insert']->bindValue(':lastName', $contact->lastName);
            $this->statements['insert']->bindValue(':title', $contact->title);
            $this->statements['insert']->bindValue(':function', $contact->function);
            $this->statements['insert']->bindValue(':service', $contact->service);
            $this->statements['insert']->bindValue(':displayName', $contact->displayName);
        }
        $contactId = $contact->contactId;

        try {
            $this->statements['insert']->execute();
        } catch (\PDOException $PDOException) {
            list($contactId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);
            throw new $class($contactId);
        }
    }

     /**
     * Read a contact
     * @param string $contactID The Contact ID
     *
     * @return MaarchRM\Business\Contacts\Contact
     */
    public function read($contactId)
    {
        if (!isset($this->statements['read'])) {
            $this->statements['read'] = $this->pdo->prepare('SELECT * FROM "Contacts" WHERE "contactId"=?');
        }

        if ($this->statements['read']->execute([$contactId])) {
            $data = $this->statements['read']->fetchObject();

            if (!$data) {
                throw new \MaarchRM\Errors\EntityNotFound('Contact', $contactId);
            }

            $contact = new \MaarchRM\Business\Contacts\Entities\Contact($data->contactId);

            $contact->contactId = $data->contactId;
            $contact->contactType = $data->contactType;
            $contact->orgName = $data->orgName;
            $contact->firstName = $data->firstName;
            $contact->lastName = $data->lastName;
            $contact->title = $data->title;
            $contact->function = $data->function;
            $contact->service = $data->service;
            $contact->displayName = $data->displayName;

            return $contact;
        }
    }

     /**
     * Data access method to update a contact
     *
     * @param MaarchRM\Business\Contacts\Contact $rcontact The contact object
     * @throws \PDOException
     */

    public function update($contact)
    {
        if (!isset($this->statements['update'])) {
            $this->statements['update'] = $this->pdo->prepare('UPDATE "Contacts" SET "contactType"=?, "orgName" =?,
            "firstName"=?, "lastName"=?,"title"=?,"function"=?, "service"=?,"displayName"=?  WHERE "contactId"=?');
        }
 
        $this->statements['update']->bindValue(1, $contact->contactType);
        $this->statements['update']->bindValue(2, $contact->orgName);
        $this->statements['update']->bindValue(3, $contact->firstName);
        $this->statements['update']->bindValue(4, $contact->lastName);
        $this->statements['update']->bindValue(5, $contact->title);
        $this->statements['update']->bindValue(6, $contact->function);
        $this->statements['update']->bindValue(7, $contact->service);
        $this->statements['update']->bindValue(8, $contact->displayName);
        $this->statements['update']->bindValue(9, $contact->contactId);

        $contactId = $contact->contactId;

        try {
            $this->statements['update']->execute();
        } catch (\PDOException $PDOException) {
            list($contactId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($contactId);
        }
    }

    /**
     * Data access method to delete a contact
     *
     * @param string $contactId The contact ID
     * @throws \PDOException
     */

    public function delete($contactId)
    {
        if (!isset($this->statements['delete'])) {
            $this->statements['delete'] = $this->pdo->prepare('DELETE FROM "Contacts" WHERE "contactId" = ?');
        }

        try {
            $this->statements['delete']->execute([$contactId]);
        } catch (\PDOException $PDOException) {
            list($contactId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($contactId);
        }
    }
}
