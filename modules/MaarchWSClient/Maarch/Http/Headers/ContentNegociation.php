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
 * The header RFC 7231 content negociation list
 */
class ContentNegociation
    extends ExtendedList
{
    /* Constants */

    /* Properties */
    
    /* Methods */
    /**
     * Construct from header string value
     * @param string $liststr
     */
    public function __construct($liststr=false)
    {       
        parent::__construct($liststr);

        $this->uasort([$this, 'cmp']);
    }

    /**
     * Compare the quality factor
     * @param object $a
     * @param object $b
     * 
     * @return int
     */
    public function cmp($a, $b)
    {
        if (!isset($a->params['q'])) {
            $a->params['q'] = '1.0';
        }
        if (!isset($b->params['q'])) {
            $b->params['q'] = '1.0';
        }

        if ($a->params['q'] == $b->params['q']) {
            return 0;
        }

        return ($a->params['q'] < $b->params['q']) ? 1 : -1;
    }
}