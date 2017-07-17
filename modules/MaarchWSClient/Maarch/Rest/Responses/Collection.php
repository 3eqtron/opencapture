<?php
/*
 * Copyright (C) 2016 Maarch
 *
 * This file is part of Maarch.
 *
 * Maarch is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Bundle documentManagement is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Maarch. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Rest\Responses;
/**
 * The range response
 *
 * @author Cyril Vazquez <cyril.vazquez@maarch.org>
 * @status 200
 */
class Collection
    extends \ArrayObject
    implements \JsonSerializable
{
    /**
     * The status
     * @var int
     * @status
     */
    public $status = 200;

    /**
     * The content count
     * @var string
     * @header
     */
    public $contentCount;

    /**
     * The content range
     * @var string
     * @header
     */
    public $contentRange;

    /**
     * The accepted range
     * @var string
     * @header
     */
    public $acceptRanges = 'items';

    /**
     * Constructor
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);

        $this->contentCount = count($data).' items';  
    }

    /**
     * Sets the returned content range
     * @param int $start
     * @param int $end
     * @param int $total
     */
    public function setContentRange(int $start, int $end, int $total=null)
    {
        $this->contentRange = 'items '.$start.'-'.$end;
        if (!is_null($total)) {
            $this->contentRange .= '/'.$total;
        }

        $this->status = 206;
    }

    /**
     * Json Serializer
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
