<?php
/*
 * Copyright (C) 2015 Maarch
 *
 * This file is part of Maarch.
 *
 * Bundle recordsManagement is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Bundle recordsManagement is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Maarch.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch;
/**
 * The base exception
 *
 * @package Maarch
 * @author  Cyril VAZQUEZ (Maarch) <cyril.vazquez@maarch.org>
 * 
 */
class Exception
    extends \Exception
{
    /**
     * The message format
     * 
     * @var string
     */
    protected $format;

    /**
     * The context data as an associative array of key=>value pairs
     * 
     * @var array
     */
    protected $context;

    /**
     * Constructor
     * @param mixed  $message  The exception message as a string 
     *                         or an associative array with the message format and an array of context
     * @param int    $code     The exception code
     * @param object $previous The previous exception
     */
    public function __construct($message, int $code=null, \Throwable $previous=null)
    {
        if (is_array($message)) {
            $this->format = $message[0];
            $this->context = $message[1];

            $message = vsprintf($message[0], $message[1]);
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the format
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Returns the contexte
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets the message
     * @param mixed $message The exception message as a string 
     *                       or an associative array with the message format and an array of context
     * 
     * @return void
     */
    public function setMessage($message)
    {
        if (is_array($this->context)) {
            $this->format = $message;

            $message = vsprintf($message, $this->context);
        }
        
        $this->message = $message;
    }
}