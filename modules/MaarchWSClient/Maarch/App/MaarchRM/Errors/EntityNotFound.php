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
namespace MaarchRM\Errors;
/**
 * Error not found
 * 
 * @author Cyril Vazquez <cyril.vazquez@maarch.org>
 * @status 404
 */
class EntityNotFound
    extends \Maarch\Http\Errors\NotFound
    implements \JsonSerializable
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $id;

    /**
     * Constructor
     * @param string $name
     * @param string $id
     */
    public function __construct($name, $id)
    {
        $this->name = $name;

        $this->id = $id;

        parent::__construct("Entity $name $id not found", 404);
    }

    /**
     * Serialize to json
     * @return array
     */
    public function jsonSerialize()
    {
        return 'Error: '.$this->getMessage();
    }
    
}
