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
namespace Maarch\Reflection;
/**
 * Class for component comments
 * 
 * @package Maarch
 * @author  Cyril Vazquez Maarch <cyril.vazquez@maarch.org>
 */
class ReflectionDocComment
{
    /**
     * @var string 
     */
    protected $value;

    /**
     * Constructor
     * @param string $docComment
     */
    public function __construct($docComment)
    {
        $this->value = implode("\n", preg_split('# *\n\s*\*(\/| *)?#m', substr($docComment, 3)));
    }

    /**
     * Magic method to get the value
     * @param string $name
     * 
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
    }

    /**
     * Get the summary
     * @return string
     */
    public function getSummary() : string
    {
        if (preg_match('#^\s*[^@\n]+#m', $this->value, $match)) {
            return trim($match[0]);
        }
    }

    /**
     * Get the description
     * @return string
     */
    public function getDescription() : string
    {
        if (preg_match('#\s*[^@]+#m', $this->value, $match)) {
            return trim(substr($match[0], strpos($match[0], "\n\n")));
        }
    }

    /**
     * Check the tag by name
     * @param string $name
     * 
     * @return bool
     */
    public function hasTag($name) : bool
    {
        return preg_match('#^@'.$name.'#m', $this->value);
    }

    /**
     * Get the tag by name
     * @param string $name
     * 
     * @return string The value
     */
    public function getTag($name) : string
    {
        if (preg_match('#^@'.$name.'#m', $this->value, $match, PREG_OFFSET_CAPTURE)) {
            // Offset of the possible value : offset of tag + length of tag + 1 space
            $o = $match[0][1]+strlen('@'.$name)+1;
            // End offset if the next tag OR end of the doc comment
            $e = \strpos($this->value, "\n@", $o);
            if ($e === false) {
                return trim(substr($this->value, $o));
            } else {
                return trim(substr($this->value, $o, $e-$o));
            }
        }

        return '';
    }

    /**
     * Get the tag array by name
     * @param string $name
     * 
     * @return array The values
     */
    public function getTags($name) : array
    {
        $tags = [];

        if (preg_match_all('#^@'.$name.'#m', $this->value, $matches, PREG_OFFSET_CAPTURE)) {

            foreach ($matches[0] as $match) {
                $o = $match[1]+strlen('@'.$name)+1;
                $e = \strpos($this->value, "\n@", $o);
                if ($e === false) {
                    $tags[] = trim(substr($this->value, $o));
                } else {
                    $tags[] = trim(substr($this->value, $o, $e-$o));
                }
            }
        }

        return $tags;
    }
}
