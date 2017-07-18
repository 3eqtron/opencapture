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
class RolesMembers
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
     * Create a role member
     * @param object $roleMember
     *
     * @return bool
     */
     
    public function insert($roleMember)
    {
        if (!isset($this->statements['insert'])) {
            $this->statements['insert'] = $this->pdo->prepare('INSERT INTO "RolesMembers" ("roleId", "userAccountId") VALUES (:roleId,:userAccountId)');
        
            $this->statements['insert']->bindValue(':roleId', $roleMember->roleId);
            $this->statements['insert']->bindValue(':userAccountId', $roleMember->userAccountId);
        }
        $roleId = $roleMember->roleId;

        try {
            $this->statements['insert']->execute();
        } catch (\PDOException $PDOException) {
            var_dump($PDOException);
            list($roleId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);
            throw new $class($roleId);
        }
    }

     /**
     * Read a role member
     * @param string $roleName The role member Name
     *
     * @return MaarchRM\Business\RolesMembers\RoleMember
     */
    public function read($roleId)
    {
        if (!isset($this->statements['read'])) {
            $this->statements['read'] = $this->pdo->prepare('SELECT * FROM "RolesMembers" WHERE "roleId"=?');
        }

        if ($this->statements['read']->execute([$roleId])) {
            $data = $this->statements['read']->fetchObject();

            if (!$data) {
                throw new \MaarchRM\Errors\EntityNotFound('Role Member', $roleId);
            }

            $roleMember = new \MaarchRM\Business\Users\Entities\RoleMember($data->roleId);

            $roleMember->userAccountId = $data->userAccountId;
        

            return $roleMember;
        }
    }

     /**
     * Data access method to update a role member
     *
     * @param MaarchRM\Business\RolesMembers\RoleMember $roleMember The role member object
     * @throws \PDOException
     */

    public function update($roleMember)
    {
        if (!isset($this->statements['update'])) {
            $this->statements['update'] = $this->pdo->prepare('UPDATE "RolesMembers" SET "userAccountId"=? WHERE "roleId"=?');
        }
 
        $this->statements['update']->bindValue(1, $roleMember->userAccountId);
        $this->statements['update']->bindValue(2, $roleMember->roleId);
     
        $roleId = $roleMember->roleId;

        try {
            $this->statements['update']->execute();
        } catch (\PDOException $PDOException) {
            var_dump($PDOException);
            list($roleId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($roleId);
        }
    }

    /**
     * Data access method to delete a role member
     *
     * @param string $roleId The role member ID
     * @throws \PDOException
     */

    public function delete($roleId)
    {
        if (!isset($this->statements['delete'])) {
            $this->statements['delete'] = $this->pdo->prepare('DELETE FROM "RolesMembers" WHERE "roleId" = ?');
        }

        try {
            $this->statements['delete']->execute([$roleId]);
        } catch (\PDOException $PDOException) {
            list($roleId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($roleId);
        }
    }
}
