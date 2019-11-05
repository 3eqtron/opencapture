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

trait TemplateParserTrait
{
    /**
     * Parse
     * @param string $instructionString
     * @param string $sep
     *
     */
    public function parse($instructionString, $sep=" ")
    {
        $args = $this->explode(trim($instructionString), $sep);

        if (!count($args))
            throw new \Exception("Invalid Template instruction : no main argument provided in $instructionString");

        $parser = new \StdClass();
        $parser->path = array_shift($args);
        $parser->source = $this->getSource($parser->path);

        $parser->params = array();

        if (!count($args)) return $parser;
        foreach ($args as $arg) {
            if (preg_match('#^(?<name>\w+)\s*(=(["\'])(?<value>(?:[^\3\\\\]|\\\\.)*)\3)$#', $arg, $pair))
                $parser->params[$pair['name']] = isset($pair['value']) ? $pair['value'] : null;
            elseif ($arg[0]=="@")
                $parser->params["attr"]  = substr($arg, 1);
            elseif ($arg[0]=="$")
                $parser->params["var"]  = substr($arg, 1);
            elseif ($arg[0]=="/")
                $parser->params["include"]  = substr($arg, 1);
            else
                $parser->params["source"]  = $arg;
        }
        
        return $parser;
    }

    /**
     * Get source
     * @param string $data
     *
     */
    public function getSource($data)
    {
        $source = array();
        $steps = $this->tokenize($data);
        for ($i=0, $l=count($steps); $i<$l; $i++) {
            $step = $steps[$i];
            switch(true) {
                case $step == "" :
                case $step == false :
                    if ($i == 0)
                        $source[] = array('arg', '');
                    else
                        unset($step);
                    break;

                //case $step[0] == ".":
                //    $source[] = array('prop', substr($step, 1));
                //    break;

                case preg_match('#^\$(?<name>.*)$#', $step, $var):
                    $source[] = array('var', $var['name']);
                    break;

                case preg_match('#^(?<ext>\w+):(?<name>.*)$#', $step, $ext):
                    $source[] = array($ext['ext'], $ext['name']);
                    break;

                case preg_match('#^(?<name>[^(\/]+)\((?<params>.*)?\)$#', $step, $func) :
                    $params = $this->explode($func['params'], ",");
                    $source[] = array('func', $func['name'], $params);
                    break;

                case preg_match('#^\[(?<name>(?<enc>["\'])?[^\2]*\2?)\]$#', $step, $offset):
                    $source[] = array('offset', $offset['name']);
                    break;

                case preg_match('#^\/(?<name>[^(]+)\((?<params>.*)?\)$#', $step, $method) :
                    $params = $this->explode($method['params'], ",");
                    $source[] = array('method', $method['name'], $params);
                    break;

                default:
                    if ($i==0)
                        $source[] = array('source', $step);
                    else
                        $source[] = array('prop', $step);
            }
        }
        
        return $source;
    }

    protected function explode($str, $sep)
    {
        $l = strlen($str);
        $o = 0;
        $esc = false;
        $sq  = false;
        $dq  = false;
        $br  = 0;
        $sbr = 0;
        $tok = array();

        for ($i=0; $i<$l; $i++) {
            // Add token if separator found out of enclosures and brackets
            if ($str[$i] == $sep && !$dq && !$sq && !$br && !$sbr) {
                $tok[] = trim(substr($str, $o, $i-$o));
                $o = $i+1;
                continue;
            }

            // Ignore character if escaped
            if ($esc) {
                $esc = false;
                continue;
            }

            // Special characters that affect parsing
            switch($str[$i]) {
            case "'":
                if (!$sq) $sq = true;
                else $sq = false;
                break;
            case '"':
                if (!$dq) $dq = true;
                else $dq = false;
                break;
            case '(':
                if (!$sq && !$dq) $br++;
                break;
            case ')':
                if (!$sq && !$dq) $br--;
                break;
            case '[':
                if (!$sq && !$dq) $sbr++;
                break;
            case ']':
                if (!$sq && !$dq) $sbr--;
                break;
            case '\\':
                $esc = true;
                break;
            }
        }
        $tail = trim(substr($str, $o, $i-$o));
        if ($tail !== "") {
            $tok[] = $tail;
        }

        if ($sq || $dq || $br || $sbr || $esc)
            throw new \Exception("Invalid string: unexpected end of string at offset $i");

        return $tok;
    }

    protected function tokenize($str)
    {
        $l = strlen($str);
        $o = 0;
        $esc = false;
        $sq  = false;
        $dq  = false;
        $br  = 0;
        $sbr = false;
        $steps = array();
        $step = false;

        // Function
        for ($i=0; $i<$l; $i++) {
            // Tokenize only of out of enclosures
            if (!$dq && !$sq && !$br) {
                // Add token if dot found
                if ($str[$i] == ".") {
                    $steps[] = trim(substr($str, $o, $i-$o));
                    $o = $i+1;
                    continue;
                }

                // Add token if opening square bracket
                if ($str[$i] == "[") {
                    $steps[] = trim(substr($str, $o, $i-$o));
                    $o = $i+1;
                    $sbr = true;
                    continue;
                }

                // Add token enclosed by square brackets
                if ($str[$i] == "]" && $sbr) {
                    $steps[] = trim(substr($str, $o-1, $i-$o+2));
                    $o = $i+1;
                    $sbr = false;
                    continue;
                }
            }

            // Ignore character if escaped
            if ($esc) {
                $esc = false;
                continue;
            }

            // Special characters that affect parsing
            switch($str[$i]) {
            case "'":
                if (!$sq) $sq = true;
                else $sq = false;
                break;
            case '"':
                if (!$dq) $dq = true;
                else $dq = false;
                break;
            case '(':
                if (!$sq && !$dq) $br++;
                break;
            case ')':
                if (!$sq && !$dq) $br--;
                break;
            case '\\':
                $esc = true;
                break;
            }
        }
        $tail = trim(substr($str, $o, $i-$o));
        if ($tail !== false)
            $steps[] = $tail;

        if ($sq || $dq || $br || $sbr || $esc)
            throw new \Exception("Invalid string: unexpected end of string at offset $i");

        return $steps;
    }

}