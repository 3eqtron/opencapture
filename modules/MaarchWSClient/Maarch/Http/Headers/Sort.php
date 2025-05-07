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
 * The header RFC 2616 Range
 * 
 * @type request
 * @name Sort
 */
class Sort
    extends \ArrayObject
{
    /* Constants */

    /* Properties */
    /**
     * Constructor
     * @param string $value
     */
    public function __construct($value=false)
    {
        foreach (array_map('trim', explode(',', $value)) as $crit) {
            $name = strtok($crit, ';');
            switch(strtok('')) {
                case 'desc':
                    $order = 3;
                    break;
                    
                case 'asc':
                default:
                    $order = 4;
            }
            $sort[$name] = $order;
        }

        parent::__construct($sort);
    }
    
    /* Methods */
    /**
     * Get the string representation
     * @return string
     */
    public function __toString()
    {
        $items = [];
        foreach ($this->getArrayCopy() as $arg => $order) {
            switch ($order) {
                case SORT_DESC:
                    $order = 'desc';
                    break;

                case SORT_ASC:
                default:
                    $order = 'asc';
            }
            
            $items[] = $arg . ';' . $order;
        }

        return implode(', ', $items);
    }
}