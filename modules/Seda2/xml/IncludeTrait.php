<?php
/*
 * Copyright (C) 2015 Maarch
 *
 * This file is part of dependency xml.
 *
 * Dependency xml is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Dependency xml is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with dependency xml.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace xml;

trait IncludeTrait
{

    /*************************************************************************/
    /* INCLUDE processing instructions                                       */
    /*************************************************************************/
    /**
     * Include an XML
     * @param DOMNode $node
     *
     */
    public function xinclude($node=null)
    {
        if (!$this->XPath) {
            $this->XPath = new XPath($this);
        }
        if ($pis = $this->XPath->query("descendant-or-self::processing-instruction('xinclude')", $node)) {
            foreach ($pis as $pi) {
                $this->includeXml($pi);
            }
        }
    }

    /**
     * Append an XML string
     * @param DOMNode $pi
     *
     * @return DOMNode
     */
    public function includeXml($pi)
    {
        $includeFragment = $this->createDocumentFragment();
        $includeFragment->appendFile(trim($pi->data));
        if (!$includeFragment) {
            throw new \Exception("Error including Xml fragment: fragment '$pi->data' could not be parsed");
        }

        return $pi->parentNode->replaceChild($includeFragment, $pi);
    }

}