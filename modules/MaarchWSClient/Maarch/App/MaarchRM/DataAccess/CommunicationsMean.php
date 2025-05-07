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
class CommunicationsMean
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
     * Create a contact communicationMean
     * @param object $communication
     *
     * @return bool
     */
     
    public function insert($communication)
    {
        if (!isset($this->statements['insert'])) {
            $this->statements['insert'] = $this->pdo->prepare('INSERT INTO "CommunicationsMean" 
            ("code","name","enabled") VALUES (:code,:name,:enabled)');
        
            $this->statements['insert']->bindValue(':code', $communication->code);
            $this->statements['insert']->bindValue(':name', $communication->name);
            $this->statements['insert']->bindValue(':enabled', $communication->enabled, \PDO::PARAM_BOOL);
        }
        $code = $communication->code;

        try {
            $this->statements['insert']->execute();
        } catch (\PDOException $PDOException) {
            list($code, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);
            throw new $class($code);
        }
    }

     /**
     * Read a contact communicationMean
     * @param string $code The Code
     *
     * @return MaarchRM\Business\Contacts\CommunicationMean
     */
    public function read($code)
    {
        if (!isset($this->statements['read'])) {
            $this->statements['read'] = $this->pdo->prepare('SELECT * FROM "CommunicationsMean" WHERE "code"=?');
        }

        if ($this->statements['read']->execute([$code])) {
            $data = $this->statements['read']->fetchObject();

            if (!$data) {
                throw new \MaarchRM\Errors\EntityNotFound('CommunicationMean', $code);
            }

            $communication = new \MaarchRM\Business\Contacts\Entities\CommunicationMean($data->code);

            $communication->name = $data->name;
            $communication->enabled = $data->enabled;
        
            return $communication;
        }
    }

     /**
     * Data access method to update a contact communicationMean
     *
     * @param MaarchRM\Business\Contacts\CommunicationMean $communication The communicationMean object
     * @throws \PDOException
     */

    public function update($communication)
    {
        if (!isset($this->statements['update'])) {
            $this->statements['update'] = $this->pdo->prepare('UPDATE "CommunicationsMean" SET "name"=?,
             "enabled" =? WHERE "code"=?');
        }
 
        $this->statements['update']->bindValue(1, $communication->name);
        $this->statements['update']->bindValue(2, $communication->enabled, \PDO::PARAM_BOOL);
        $this->statements['update']->bindValue(3, $communication->code);
            
        $code = $communication->code;

        try {
            $this->statements['update']->execute();
        } catch (\PDOException $PDOException) {
            list($code, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($code);
        }
    }

    /**
     * Data access method to delete a communicationMean
     *
     * @param string $code The code
     * @throws \PDOException
     */

    public function delete($code)
    {
        if (!isset($this->statements['delete'])) {
            $this->statements['delete'] = $this->pdo->prepare('DELETE FROM "CommunicationsMean" WHERE "code" = ?');
        }

        try {
            $this->statements['delete']->execute([$code]);
        } catch (\PDOException $PDOException) {
            list($code, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($code);
        }
    }
}
