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
 * The service method response definition
 * 
 * @package Maarch
 */
class Response
    extends MessageAbstract
    implements ResponseInterface
{
    /**
     * Get the status
     * @return ResponseStatus|Status|int
     */
    public function getStatus()
    {
        foreach ($this->reflection->getProperties() as $reflectionProperty) {
            $docComment = new \Maarch\Reflection\ReflectionDocComment($reflectionProperty->getDocComment());

            if ($docComment->hasTag('status')) {
                if ($docComment->hasTag('var')) {
                    $token = strtok($docComment->getTag('var'), ' ');
                    if ($token == 'int' || $token == 'integer') {
                        return new ResponseStatus($reflectionProperty);
                    } else {
                        return new Status($token);
                    }
                }
            }
        }

        if ($this->docComment->hasTag('status')) {
            $status = $this->docComment->getTag('status');
            $token = strtok($status, ' ');
            if (is_numeric($token)) {
                return $token;
            } else {
                return new Status($token);
            }
        }
    }

    /**
     * Serialize to json
     * @return array
     */
    public function jsonSerialize()
    {
        $return = [];
        if ($status = $this->getStatus()) {
            $return['statusCode'] = $status;
        }
        if (!empty($headers = $this->getHeaders())) {
            $return['headers'] = $headers;
        }
        if (!empty($representations = $this->getRepresentations())) {
            $return['representations'] = $representations;
        }

        return $return;
    }
}