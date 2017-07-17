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
namespace Maarch;
/**
 * Json utility
 */
class Json
{
    
    /**
     * Decode a json contents into an associative array of parameters
     * @param string $json
     * 
     * @return mixed
     */
    public static function decode($json)
    {
        $data = \json_decode($json);
       
        static::catchError();

        return $data;
    }

    /**
     * Encode data into json string
     * @param mixed $data
     * @param int   $options
     * 
     * @return string
     */
    public static function encode($data, $options = \JSON_PRETTY_PRINT + \JSON_UNESCAPED_SLASHES + \JSON_UNESCAPED_UNICODE)
    {
        $jsonString = \json_encode($data, $options);

        static::catchError();

        return $jsonString;
    }

    protected static function catchError()
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return;

            case JSON_ERROR_DEPTH:
                $message = 'The maximum stack depth has been exceeded.';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $message = 'Invalid or malformed JSON.';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $message = 'Control character error, possibly incorrectly encoded.';
                break;
            case JSON_ERROR_SYNTAX:
                $message = 'Syntax error.';
                break;
            case JSON_ERROR_UTF8:
                $message = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                break;
            case JSON_ERROR_RECURSION:
                $message = 'One or more recursive references detected in the value to be encoded.';
                break;
            case JSON_ERROR_INF_OR_NAN:
                $message = 'One or more NAN or INF values in the value to be encoded.';
                break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                $message = 'A value of a type that cannot be encoded was given.';
                break;
            default:
                $message = 'Unknown error.';
        }

        throw new \Exception("Error in Json data: " . $message);
    }

}