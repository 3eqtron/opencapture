<?php
/*
 * Copyright (C) 2017 Maarch
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Http\Description;
use \Maarch\Reflection;
/**
 * The http service response status definition
 * 
 * @package Maarch
 */
class Status
    extends Reflection\ReflectionProperty
    implements \JsonSerializable, StatusInterface
{
    /**
     * Returns the numeric status code
     * @param object $entity 
     * 
     * @return integer
     */
    public function getValue($entity=null)
    {
        if ($this->docComment->hasTag('code')) {
            return strtok($this->docComment->getTag('code'), ' ');
        }
    }

    /**
     * Returns the reason phrase
     * @return string
     */
    public function getReasonPhrase()
    {
        $this->reflection->getSummary();
    }

    /**
     * Returns the http problem details
     * @return string
     */
    public function getDetail()
    {
        $this->reflection->getDescription();
    }
}
