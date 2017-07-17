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
namespace MaarchRM\Business\Contacts\Entities;

/**
 * The archive Address
 *
 * @author .....
 */
class AddressesAbstract extends \Maarch\BusinessComponents\BusinessEntityAbstract implements \JsonSerializable
{

    /**
     * The code
     *
     * @var string
     * @pattern ^[A-Za-z0-9_\-]+$
     * @-readonly
     */
    protected $addressId;

    /**
     * The contact ID
     *
     * @var string
     * @maxLength 128
     */
    protected $contactId;

     /**
     * purpose
     *
     * @var string
     */
    protected $purpose;

     /**
     * chambre
     *
     * @var string
     */
    protected $room;

     /**
     * étage
     *
     * @var string
     */
    protected $floor;

     /**
     * immeuble
     *
     * @var string
     */
    protected $building;

    /**
     * numéro
     *
     * @var string
     */
    protected $number;

    /**
     * Rue
     *
     * @var string
     */
    protected $street;

    /**
     * PostBox
     *
     * @var string
     */
    protected $postBox;

    /**
     * block
     *
     * @var string
     */
    protected $block;

    /**
     * CitySubDivision
     *
     * @var string
     */
    protected $citySubDivision;

    /**
     * PostCode
     *
     * @var string
     */
    protected $postCode;

    /**
     * city
     *
     * @var string
     */
    protected $city;

    /**
     * country
     *
     * @var string
     */
    protected $country;

    /**
     * Constructor
     * @param string $code The address code
     */
    public function __construct(string $address)
    {
        $this->__set('contactId', $address);
    }

    /**
     * Serialize to Json
     * @return array
     */
    public function jsonSerialize()
    {
        $return = get_object_vars($this);

        unset($return['contactId']);

        return $return;
    }
}
