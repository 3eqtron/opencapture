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
 * Address
 *
 * @author ...
 */
class Addresses
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
     * Create an address
     * @param object $address
     *
     * @return bool
     */
     
    public function insert($address)
    {
        if (!isset($this->statements['insert'])) {
            $this->statements['insert'] = $this->pdo->prepare('INSERT INTO "Addresses" 
            ("addressId","contactId","purpose","room","floor","building","number","street","postBox","block",
            "citySubDivision","postCode","city","country") VALUES (:addressId,:contactId,:purpose,:room,:floor,
            :building,:number,:street,:postBox,:block,:citySubDivision,:postCode,:city,:country)');

            $this->statements['insert']->bindValue(':addressId', $address->addressId);
            $this->statements['insert']->bindValue(':contactId', $address->contactId);
            $this->statements['insert']->bindValue(':purpose', $address->purpose);
            $this->statements['insert']->bindValue(':room', $address->room);
            $this->statements['insert']->bindValue(':floor', $address->floor);
            $this->statements['insert']->bindValue(':building', $address->building);
            $this->statements['insert']->bindValue(':number', $address->number);
            $this->statements['insert']->bindValue(':street', $address->street);
            $this->statements['insert']->bindValue(':postBox', $address->postBox);
            $this->statements['insert']->bindValue(':block', $address->block);
            $this->statements['insert']->bindValue(':citySubDivision', $address->citySubDivision);
            $this->statements['insert']->bindValue(':postCode', $address->postCode);
            $this->statements['insert']->bindValue(':city', $address->city);
            $this->statements['insert']->bindValue(':country', $address->country);
        }
        $contactId = $address->contactId;

        try {
            $this->statements['insert']->execute();
        } catch (\PDOException $PDOException) {
            list($contactId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);
            throw new $class($contactId);
        }
    }

     /**
     * Read an address
     * @param string $contactId The Contact ID
     *
     * @return MaarchRM\Business\Contacts\Address
     */

    public function read($contactId)
    {
        if (!isset($this->statements['read'])) {
            $this->statements['read'] = $this->pdo->prepare('SELECT * FROM "Addresses" WHERE "contactId"=?');
        }
        if ($this->statements['read']->execute([$contactId])) {
            $data = $this->statements['read']->fetchObject();

            if (!$data) {
                throw new \MaarchRM\Errors\EntityNotFound('Adresse', $contactId);
            }

            $address = new \MaarchRM\Business\Contacts\Entities\Address($data->contactId);

            $address->addressId = $data->addressId;
            $address->purpose= $data->purpose;
            $address->room = $data->room;
            $address->floor = $data->floor;
            $address->building = $data->building;
            $address->number = $data->number;
            $address->street = $data->street;
            $address->postBox = $data->postBox;
            $address->block = $data->block;
            $address->citySubDivision= $data->citySubDivision;
            $address->postCode = $data->postCode;
            $address->city = $data->city;
            $address->country = $data->country;
        
            return $address;
        }
    }

     /**
     * Data access method to update an address
     *
     * @param MaarchRM\Business\Contacts\Address  $address The address object
     * @throws \PDOException
     */

    public function update($address)
    {
        if (!isset($this->statements['update'])) {
            $this->statements['update'] = $this->pdo->prepare('UPDATE "Addresses" SET "addressId"=?,
              purpose"=?,"room"=? , floor"=? ,"building"=? ,"number"=?
             ,"street"=? ,"postBox"=? ,"block"=? ,"citySubDivision"=? ,"postCode"=?
             ,"city"=? ,"country"=? WHERE "contactId"=?');
        }
        $this->statements['update']->bindValue(1, $address->addressId);
        $this->statements['update']->bindValue(2, $address->purpose);
        $this->statements['update']->bindValue(3, $address->room);
        $this->statements['update']->bindValue(4, $address->floor);
        $this->statements['update']->bindValue(5, $address->building);
        $this->statements['update']->bindValue(6, $address->number);
        $this->statements['update']->bindValue(7, $address->street);
        $this->statements['update']->bindValue(8, $address->postBox);
        $this->statements['update']->bindValue(9, $address->block);
        $this->statements['update']->bindValue(10, $address->citySubDivision);
        $this->statements['update']->bindValue(11, $address->postCode);
        $this->statements['update']->bindValue(12, $address->city);
        $this->statements['update']->bindValue(13, $address->country);
        $this->statements['update']->bindValue(14, $address->contactId);
            
        $contactId = $address->contactId;

        try {
            $this->statements['update']->execute();
        } catch (\PDOException $PDOException) {
            list($contactId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);
            throw new $class($contactId);
        }
    }

    /**
     * Data access method to delete an address
     *
     * @param string $contactId The contact ID
     * @throws \PDOException
     */

    public function delete($contactId)
    {
        if (!isset($this->statements['delete'])) {
            $this->statements['delete'] = $this->pdo->prepare('DELETE FROM "Addresses" WHERE "contactId" = ?');
        }

        try {
            $this->statements['delete']->execute([$contactId]);
        } catch (\PDOException $PDOException) {
            list($contactId, $class) = \MaarchRM\DataAccess\Pdo\ExceptionHandler::getHttpError($PDOException);

            throw new $class($contactId);
        }
    }
}
