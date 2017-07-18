<?php
/*
 * Copyright (C) 2015 Maarch
 *
 * This file is part of Maarch.
 *
 * Maarch is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Maarch is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Maarch.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Http\Headers;
/**
 * The header associative array
 */
class Map
    extends \ArrayObject
{
    /* Constants */

    /* Properties */
    
    /* Methods */
    /**
     * Constructor with string value
     * @param mixed $value
     */
    public function __construct($value=false)
    {
        if (!is_array($value)) {
            $arr = [];
            foreach (array_map('trim', explode(',', $value)) as $item) {
                $name = strtok($item, '=');
                $val = strtok('');

                $arr[$name] = $value;
            }
        } else {
            $arr = $value;
        }

        parent::__construct($arr);
    }
    
    /**
     * Get the string representation
     * @return string
     */
    public function __toString()
    {
        $items = [];
        foreach ($this->getArrayCopy() as $name => $value) {
            $items[] = $name . '=' . $value;
        }

        return implode(', ', $items);
    }
}