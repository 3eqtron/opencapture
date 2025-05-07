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
 * Role
 *
 * @author ...
 */
class ServicesPrivilege
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
     * Create a service privilege
     * @param object $service
     *
     * @return bool
     */
     
    public function insert($service)
    {
        if (!isset($this->statements['insert'])) {
            $this->statements['insert'] = $this->pdo->prepare('INSERT INTO "ServicesPrivilege" ("accountId", "serviceURI") VALUES (:accountId,:serviceURI)');
        
            $this->statements['insert']->bindValue(':accountId', $service->accountId);
            $this->statements['insert']->bindValue(':serviceURI', $service->serviceURI);
        }
        $accountId = $service->accountId;

        try {
            $this->statements['insert']->execute();
        } catch (\PDOException $PDOException) {
            list($accountId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);
            throw new $class($accountId);
        }
    }

     /**
     * Read a service Privilege
     * @param string $accountId The Id account
     *
     * @return MaarchRM\Business\Services\ServicePrivilege
     */
    public function read($accountId)
    {
        if (!isset($this->statements['read'])) {
            $this->statements['read'] = $this->pdo->prepare('SELECT * FROM "ServicesPrivilege" WHERE "accountId"=?');
        }

        if ($this->statements['read']->execute([$accountId])) {
            $data = $this->statements['read']->fetchObject();

            if (!$data) {
                throw new \MaarchRM\Errors\EntityNotFound('Service Privilege', $accountId);
            }

            $service = new \MaarchRM\Business\Services\Entities\ServicePrivilege($data->accountId);

            $service->serviceURI = $data->serviceURI;
        

            return $service;
        }
    }

     /**
     * Data access method to update a service Privilege
     *
     * @param MaarchRM\Business\Services\ServicePrivilege $service The servicePrivilege object
     * @throws \PDOException
     */

    public function update($service)
    {
        if (!isset($this->statements['update'])) {
            $this->statements['update'] = $this->pdo->prepare('UPDATE "ServicesPrivilege" SET "serviceURI"=? WHERE "accountId"=?');
        }
 
        $this->statements['update']->bindValue(1, $service->accountId);
        $this->statements['update']->bindValue(2, $service->accountId);
     
        $accountId = $service->accountId;

        try {
            $this->statements['update']->execute();
        } catch (\PDOException $PDOException) {
            var_dump($PDOException);
            list($accountId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($accountId);
        }
    }

    /**
     * Data access method to delete a service Privilege
     *
     * @param string $accountId The account ID
     * @throws \PDOException
     */

    public function delete($accountId)
    {
        if (!isset($this->statements['delete'])) {
            $this->statements['delete'] = $this->pdo->prepare('DELETE FROM "ServicesPrivilege" WHERE "accountId" = ?');
        }

        try {
            $this->statements['delete']->execute([$accountId]);
        } catch (\PDOException $PDOException) {
            list($accountId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($accountId);
        }
    }
}
