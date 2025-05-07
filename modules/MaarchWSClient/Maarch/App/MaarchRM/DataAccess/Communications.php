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
class Communications
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
     * Create a communication
     * @param object $communication
     *
     * @return bool
     */
     
    public function insert($communication)
    {
        if (!isset($this->statements['insert'])) {
            $this->statements['insert'] = $this->pdo->prepare('INSERT INTO "Communications" 
            ("communicationId","contactId","purpose","comMeanCode","value","info") 
            VALUES (:communicationId,:contactId,:purpose, :comMeanCode,:value,:info)');

            $this->statements['insert']->bindValue(':communicationId', $communication->communicationId);
            $this->statements['insert']->bindValue(':contactId', $communication->contactId);
            $this->statements['insert']->bindValue(':purpose', $communication->purpose);
            $this->statements['insert']->bindValue(':comMeanCode', $communication->comMeanCode);
            $this->statements['insert']->bindValue(':value', $communication->value);
            $this->statements['insert']->bindValue(':info', $communication->info);
        }
        $contactId = $communication->contactId;

        try {
            $this->statements['insert']->execute();
        } catch (\PDOException $PDOException) {
            var_dump($PDOException);
            list($contactId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);
            throw new $class($contactId);
        }
    }

     /**
     * Read a communication
     * @param string $contactId The Contact ID
     *
     * @return MaarchRM\Business\Contacts\Communication
     */
    public function read($contactId)
    {
        if (!isset($this->statements['read'])) {
            $this->statements['read'] = $this->pdo->prepare('SELECT * FROM "Communications" WHERE "contactId"=?');
        }

        if ($this->statements['read']->execute([$contactId])) {
            $data = $this->statements['read']->fetchObject();

            if (!$data) {
                throw new \MaarchRM\Errors\EntityNotFound('Communication ', $contactId);
            }

            $communication = new \MaarchRM\Business\Contacts\Entities\Communication($data->contactId);

            $communication->communicationId = $data->communicationId;
            $communication->purpose = $data->purpose;
            $communication->comMeanCode= $data->comMeanCode;
            $communication->value = $data->value;
            $communication->info = $data->info;
        
            return $communication;
        }
    }

     /**
     * Data access method to update a communication
     *
     * @param MaarchRM\Business\Contacts\Communication  $communication The communication  object
     * @throws \PDOException
     */

    public function update($communication)
    {
        if (!isset($this->statements['update'])) {
            $this->statements['update'] = $this->pdo->prepare('UPDATE "Communications" SET "communicationId"=?,
             "purpose" =?,"comMeanCode"=? , "value"=? ,"info"=? WHERE "contactId"=?');
        }
        $this->statements['update']->bindValue(1, $communication->communicationId);
        $this->statements['update']->bindValue(2, $communication->purpose);
        $this->statements['update']->bindValue(3, $communication->comMeanCode);
        $this->statements['update']->bindValue(4, $communication->value);
        $this->statements['update']->bindValue(5, $communication->info);
        $this->statements['update']->bindValue(6, $communication->contactId);
            
        $contactId = $communication->contactId;

        try {
            $this->statements['update']->execute();
        } catch (\PDOException $PDOException) {
            list($contactId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($contactId);
        }
    }

    /**
     * Data access method to delete a communication
     *
     * @param string $contactId The contact ID
     * @throws \PDOException
     */

    public function delete($contactId)
    {
        if (!isset($this->statements['delete'])) {
            $this->statements['delete'] = $this->pdo->prepare('DELETE FROM "Communications" WHERE "contactId" = ?');
        }

        try {
            $this->statements['delete']->execute([$contactId]);
        } catch (\PDOException $PDOException) {
            list($contactId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($contactId);
        }
    }
}
