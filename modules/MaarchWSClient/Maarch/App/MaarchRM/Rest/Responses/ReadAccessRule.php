<?php
/*
 * Copyright (C) 2016 Maarch
 *
 * This file is part of MaarchRM.
 *
 * MaarchRM is free software: you can redistribute it and/or modify
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
namespace MaarchRM\Rest\Responses;
/**
 * The access rule
 *
 * @author Cyril Vazquez <cyril.vazquez@maarch.org>
 * @status 200
 */
class ReadAccessRule
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $duration;

    /**
     * @var string
     */
    public $description;

    /**
     * Constructor
     * @param object $data
     */
    public function __construct($data)
    {
        foreach (get_class_vars(__CLASS__) as $name => $default) {
            $this->$name = $data->{$name};
        }
    }

    /**
     * The json representation
     * 
     * @return string
     * @representation
     * @mediaType application/json
     * @element #/definitions/AccessRule
     */
    public function toJson()
    {
        return json_encode($this);
    }
}
