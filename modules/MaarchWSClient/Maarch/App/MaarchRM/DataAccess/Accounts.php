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
 * Account
 *
 * @author ...>
 */
class Accounts
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
     * Create an account
     * @param object $account
     *
     * @return bool
     */
     
    public function insert($account)
    {
        if (!isset($this->statements['insert'])) {
            $this->statements['insert'] = $this->pdo->prepare('INSERT INTO "Accounts"(
            "accountId", "accountName", "displayName", "accountType", "emailAddress", 
            "enabled", "password", "passwordChangeRequired", "passwordLastChange", 
            "locked", "lockDate", "badPasswordCount", "lastLogin", "lastIp", 
            "replacingUserAccountId", "firstName", "lastName", "title", "salt", 
            "tokenDate") VALUES (:accountId, :accountName, :displayName, :accountType, :emailAddress,
            :enabled, :password, :passwordChangeRequired,
            :passwordLastChange, :locked, :lockDate, :badPasswordCount, :lastLogin, :lastIp,
            :replacingUserAccountId, :firstName, :lastName, :title, :salt, :tokenDate)');

            $this->statements['insert']->bindValue(':accountId', $account->accountId);
            $this->statements['insert']->bindValue(':accountName', $account->accountName);
            $this->statements['insert']->bindValue(':displayName', $account->displayName);
            $this->statements['insert']->bindValue(':accountType', $account->accountType);
            $this->statements['insert']->bindValue(':emailAddress', $account->emailAddress);
            $this->statements['insert']->bindValue(':enabled', $account->enabled, \PDO::PARAM_BOOL);
            $this->statements['insert']->bindValue(':password', $account->password);
            $this->statements['insert']->bindValue(':passwordChangeRequired', $account->passwordChangeRequired, \PDO::PARAM_BOOL);
            $this->statements['insert']->bindValue(':passwordLastChange', $account->passwordLastChange);
            $this->statements['insert']->bindValue(':locked', $account->locked, \PDO::PARAM_BOOL);
            $this->statements['insert']->bindValue(':lockDate', $account->lockDate);
            $this->statements['insert']->bindValue(':badPasswordCount', $account->badPasswordCount, \PDO::PARAM_INT);
            $this->statements['insert']->bindValue(':lastLogin', $account->lastLogin);
            $this->statements['insert']->bindValue(':lastIp', $account->lastIp);
            $this->statements['insert']->bindValue(':replacingUserAccountId', $account->replacingUserAccountId);
            $this->statements['insert']->bindValue(':firstName', $account->firstName);
            $this->statements['insert']->bindValue(':lastName', $account->lastName);
            $this->statements['insert']->bindValue(':title', $account->title);
            $this->statements['insert']->bindValue(':salt', $account->salt);
            $this->statements['insert']->bindValue(':tokenDate', $account->tokenDate);
        }

        $accountName = $account->accountName;

        try {
            $this->statements['insert']->execute();
        } catch (\PDOException $PDOException) {
            list($accountName, $class) = \Maarch\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($accountName);
        }
    }

    /**
     * Read an account
     * @param string $accountName The account Name
     *
     * @return MaarchRM\Business\Users\Account
     */
    public function read($accountName)
    {
        if (!isset($this->statements['read'])) {
            $this->statements['read'] = $this->pdo->prepare('SELECT * FROM "Accounts" WHERE "accountName"=?');
        }

        if ($this->statements['read']->execute([$accountName])) {
            $data = $this->statements['read']->fetchObject();

            if (!$data) {
                throw new \MaarchRM\Errors\EntityNotFound('Account', $accountName);
            }
            
            $account = new \MaarchRM\Business\Users\Entities\Account($data->accountName);
         
            $account->accountId = $data->accountId;
            $account->displayName = $data->displayName;
            $account->accountType = $data->accountType;
            $account->emailAddress = $data->emailAddress;
            $account->enabled = $data->enabled;
            $account->password = $data->password;
            $account->passwordChangeRequired = $data->passwordChangeRequired;
            $account->passwordLastChange = $data->passwordLastChange;
            $account->locked = $data->locked;
            $account->lockDate = $data->lockDate;
            $account->badPasswordCount = $data->badPasswordCount;
            $account->lastLogin = $data->lastLogin;
            $account->lastIp = $data->lastIp;
            $account->replacingUserAccountId = $data->replacingUserAccountId;
            $account->firstName = $data->firstName;
            $account->lastName = $data->lastName;
            $account->title = $data->title;
            $account->salt = $data->salt;
            $account->tokenDate = $data->tokenDate;

            return $account;
        }
    }

    /**
     * Data access method to update an account
     *
     * @param MaarchRM\Business\Users\Account $account The account object
     * @throws \PDOException
     */
    public function update($account)
    {
        if (!isset($this->statements['update'])) {
            $this->statements['update'] = $this->pdo->prepare('UPDATE "Accounts" SET  "displayName"=?, "accountType"=?, "emailAddress"=?, 
            "enabled"=?, "password"=?, "passwordChangeRequired"=?, "passwordLastChange"=?, 
            "locked"=?, "lockDate"=?, "badPasswordCount"=?, "lastLogin"=?, "lastIp"=?, 
            "replacingUserAccountId"=?, "firstName"=?, "lastName"=?, "title"=?, "salt"=?, 
            "tokenDate"=? WHERE "accountName"=?');
        }

        $this->statements['update']->bindValue(1, $account->displayName);
        $this->statements['update']->bindValue(2, $account->accountType);
        $this->statements['update']->bindValue(3, $account->emailAddress);
        $this->statements['update']->bindValue(4, $account->enabled, \PDO::PARAM_BOOL);
        $this->statements['update']->bindValue(5, $account->password);
        $this->statements['update']->bindValue(6, $account->passwordChangeRequired, \PDO::PARAM_BOOL);
        $this->statements['update']->bindValue(7, $account->passwordLastChange);
        $this->statements['update']->bindValue(8, $account->locked, \PDO::PARAM_BOOL);
        $this->statements['update']->bindValue(9, $account->lockDate);
        $this->statements['update']->bindValue(10, $account->badPasswordCount, \PDO::PARAM_INT);
        $this->statements['update']->bindValue(11, $account->lastLogin);
        $this->statements['update']->bindValue(12, $account->lastIp);
        $this->statements['update']->bindValue(13, $account->replacingUserAccountId);
        $this->statements['update']->bindValue(14, $account->firstName);
        $this->statements['update']->bindValue(15, $account->lastName);
        $this->statements['update']->bindValue(16, $account->title);
        $this->statements['update']->bindValue(17, $account->salt);
        $this->statements['update']->bindValue(18, $account->tokenDate);
        $this->statements['update']->bindValue(19, $account->accountName);

        $accountName = $account->accountName;

        try {
            $this->statements['update']->execute();
        } catch (\PDOException $PDOException) {
            var_dump($PDOException);
            list($accountName, $class) = \Maarch\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($accountName);
        }
    }

    /**
     * Data access method to delete an account
     *
     * @param string $accountName The account name
     * @throws \PDOException
     */
    public function delete($accountName)
    {
        if (!isset($this->statements['delete'])) {
            $this->statements['delete'] = $this->pdo->prepare('DELETE FROM "Accounts" WHERE "accountName" = ?');
        }

        try {
            $this->statements['delete']->execute([$accountName]);
        } catch (\PDOException $PDOException) {
            list($accountName, $class) = \Maarch\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($accountName);
        }
    }
}
