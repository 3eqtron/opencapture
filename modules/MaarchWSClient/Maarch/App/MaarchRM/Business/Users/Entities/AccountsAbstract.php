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
namespace MaarchRM\Business\Users\Entities;

/**
 * The archive Users
 *
 * @author .....
 */
class AccountsAbstract extends \Maarch\BusinessComponents\BusinessEntityAbstract implements \JsonSerializable
{
    /**
     * The user ID
     *
     * @var string
     * @pattern ^[A-Za-z0-9_\-]+$
     * @-readonly
     */
    protected $accountId;

    /**
     * The user name or label
     *
     * @var string
     * @pattern ^[A-Za-z0-9_\-]+$
     * @maxLength 128
     */
    protected $accountName;

     /**
     * The user display name
     *
     * @var string
     * @maxLength 128
     */
    protected $displayName;

    /**
     * The user type
     *
     * @var string
     */
    protected $accountType;

    /**
     * The user email
     *
     * @var string
     */
    protected $emailAddress;

    /**
     * The user email
     *
     * @var boolean
     */
    protected $enabled;

    
    /**
     * The user password
     *
     * @var string
     */
    protected $password;

    /**
     * The user password
     *
     * @var boolean
     */
    protected $passwordChangeRequired;

    /**
     * The user password last change
     *
     * @var boolean
     */
    protected $passwordLastChange;

    /**
     * locked
     *
     * @var boolean
     */
    protected $locked;

    /**
     * lockDate
     *
     * @var boolean
     */
    protected $lockDate;

    /**
     * Password Failed
     *
     * @var integer
     */
    protected $badPasswordCount;

    /**
     * Last Login
     *
     * @var date
     */
    protected $lastLogin;

    /**
     * Last IP
     *
     * @var integer
     */
    protected $lastIp;

    /**
     * Replace User Account ID
     *
     * @var string
     */
    protected $replacingUserAccountId;

    /**
     * First Name
     *
     * @var string
     */
    protected $firstName;

    /**
     * Last Name
     *
     * @var string
     */
    protected $lastName;

     /**
     * Titre
     *
     * @var string
     */
    protected $title;

     /**
     * salt
     * @var string
     */
    protected $salt;

     /**
     * tokenDate
     * @var string
     */
    protected $tokenDate;

    /**
     * Constructor
     * @param string $code The management rule code
     */
    public function __construct(string $accountName)
    {
        $this->__set('accountName', $accountName);
    }

    /**
     * Serialize to Json
     * @return array
     */
    public function jsonSerialize()
    {
        $return = get_object_vars($this);

        unset($return['accountName']);

        return $return;
    }
}
