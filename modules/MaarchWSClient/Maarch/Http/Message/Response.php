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
 * along with Maarch. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Http\Message;
/**
 * Http wrapper for server outgoing http responses
 *
 */
class Response
    extends MessageAbstract
    implements \Psr\Http\Message\ResponseInterface
{
    /**
     * @var integer
     */
    public $statusCode;

    /**
     * @var string
     */
    protected $reasonPhrase;

    /**
     * Constructor
     * @param int    $statusCode   The http response status code
     * @param string $reasonPhrase The http response status reason phrase
     */
    public function __construct(int $statusCode=null, string $reasonPhrase=null)
    {
        if ($statusCode) {
            $this->withStatus($statusCode, $reasonPhrase);
        }

        // Get the protocol version from apache server
        $protocol = strtok($_SERVER['SERVER_PROTOCOL'], '/');
        $this->protocolVersion = strtok('');
    }

    /**
     * Get the http code
     * @return integer The response code
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     * @param int    $code         The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * 
     * @return static
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $this->statusCode = $code;

        $this->reasonPhrase = $reasonPhrase;

        return $this;
    }

    /**
     * Get the http status reason
     * @return string The reason
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }
}