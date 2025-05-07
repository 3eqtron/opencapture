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
namespace MaarchRM\Business\ManagementRules\Entities;

/**
 * The archive management rule
 *
 * @author Cyril Vazquez <cyril.vazquez@maarch.org>
 */
class ManagementRuleAbstract extends \Maarch\BusinessComponents\BusinessEntityAbstract implements \JsonSerializable
{
    /**
     * The management rule code
     *
     * @var string
     * @pattern ^[A-Za-z0-9_\-]+$
     * @-readonly
     */
    protected $code;

    /**
     * The management rule name or label
     *
     * @var string
     * @maxLength 128
     */
    protected $name;

    /**
     * The description or motivation of the rule
     *
     * @var string
     */
    protected $description;

    /**
     * Constructor
     * @param string $code The management rule code
     */
    public function __construct(string $code)
    {
        $this->__set('code', $code);
    }

    /**
     * Serialize to Json
     * @return array
     */
    public function jsonSerialize()
    {
        $return = get_object_vars($this);

        unset($return['code']);

        return $return;
    }
}
