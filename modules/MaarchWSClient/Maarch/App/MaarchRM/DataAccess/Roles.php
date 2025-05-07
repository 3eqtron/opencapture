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
class Roles
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
     * Create a role
     * @param object $role
     *
     * @return bool
     */
     
    public function insert($role)
    {
        if (!isset($this->statements['insert'])) {
            $this->statements['insert'] = $this->pdo->prepare('INSERT INTO "Roles" ("roleId", "roleName" ,"description", "enabled") VALUES (:roleId,:roleName,:description,:enabled)');
        
            $this->statements['insert']->bindValue(':roleId', $role->roleId);
            $this->statements['insert']->bindValue(':roleName', $role->roleName);
            $this->statements['insert']->bindValue(':description', $role->description);
            $this->statements['insert']->bindValue(':enabled', $role->enabled, \PDO::PARAM_BOOL);
        }
        $roleId = $role->roleId;

        try {
            $this->statements['insert']->execute();
        } catch (\PDOException $PDOException) {
            list($roleId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($roleId);
        }
    }

     /**
     * Read a role
     * @param string $roleId The role ID
     *
     * @return MaarchRM\Business\Roles\Role
     */
    public function read($roleId)
    {
        if (!isset($this->statements['read'])) {
            $this->statements['read'] = $this->pdo->prepare('SELECT * FROM "Roles" WHERE "roleId"=?');
        }

        if ($this->statements['read']->execute([$roleId])) {
            $data = $this->statements['read']->fetchObject();

            if (!$data) {
                throw new \MaarchRM\Errors\EntityNotFound('Role', $roleId);
            }

            $role = new \MaarchRM\Business\Users\Entities\Role($data->roleId);

            $role->roleName = $data->roleName;
            $role->description = $data->description;
            $role->enabled = $data->enabled;

            return $role;
        }
    }

     /**
     * Data access method to update a role
     *
     * @param MaarchRM\Business\Roles\Role $role The role object
     * @throws \PDOException
     */

    public function update($role)
    {
        if (!isset($this->statements['update'])) {
            $this->statements['update'] = $this->pdo->prepare('UPDATE "Roles" SET "roleName"=?, "description"=?, 
            "enabled"=? WHERE "roleId"=?');
        }
        $this->statements['update']->bindValue(1, $role->roleName);
        $this->statements['update']->bindValue(2, $role->description);
        $this->statements['update']->bindValue(3, $role->enabled, \PDO::PARAM_BOOL);
        $this->statements['update']->bindValue(4, $role->roleId);

        $roleId = $role->roleId;

        try {
            $this->statements['update']->execute();
        } catch (\PDOException $PDOException) {
            list($roleId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($roleId);
        }
    }

    /**
     * Data access method to delete a role
     *
     * @param string $roleName The role name
     * @throws \PDOException
     */

    public function delete($roleId)
    {
        if (!isset($this->statements['delete'])) {
            $this->statements['delete'] = $this->pdo->prepare('DELETE FROM "Roles" WHERE "roleId"=?');
        }

        try {
            $this->statements['delete']->execute([$roleId]);
        } catch (\PDOException $PDOException) {
            list($roleId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($roleId);
        }
    }
}
