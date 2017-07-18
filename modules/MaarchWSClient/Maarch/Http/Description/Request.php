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
 * Definition of the service resource method request
 * 
 * @package Maarch
 */
class Request
    extends MessageAbstract
    implements RequestInterface
{
    /**
     * Get the query type
     * @return string
     */
    public function getQueryType() : string
    {
        return $this->docComment->getTag('queryType');
    }

    /**
     * Get the request parameters
     * @return Maarch\Http\Description\QueryParamRef[]
     */
    public function getQueryParams() : array
    {
        $queryParams = [];

        foreach ($this->reflection->getProperties() as $reflectionProperty) {
            if (preg_match('#\* *@query#', $reflectionProperty->getDocComment())) {
                $queryParams[] = new QueryParamRef($reflectionProperty);
            }
        }

        return $queryParams;
    }

    /**
     * Serialize to json
     * @return array
     */
    public function jsonSerialize()
    {
        $return = [];
        if ($queryType = $this->getQueryType()) {
            $return['queryType'] = $queryType;
        }
        if (!empty($headerParams = $this->getHeaders())) {
            $return['headers'] = $headerParams;
        }
        if (!empty($queryParams = $this->getQueryParams())) {
            $return['query'] = $queryParams;
        }
        if ($representations = $this->getRepresentations()) {
            $return['representations'] = $representations;
        }
        
        return $return;
    }

}