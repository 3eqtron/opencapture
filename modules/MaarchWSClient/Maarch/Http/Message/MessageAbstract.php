<?php
/*
 * Copyright (C) 2017 Maarch
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
 * Http wrapper for incoming http requests
 * 
 * @package Http
 * @author  Cyril Vazquez <cyril.vazquez@maarch.org>
 */
abstract class MessageAbstract
    implements \Psr\Http\Message\MessageInterface
{
    /**
     * @var string The protocol version
     */
    protected $protocolVersion = '1.1';

    /**
     * @var array The headers
     */
    protected $headers = [];

    /**
     * @var resource The body
     */
    protected $body;

    /**
     * Returns the protocol version
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * Sets the specified protocol version
     * @param string $version The version of the protocol
     * 
     * @return static
     */
    public function withProtocolVersion($version)
    {
        $this->protocolVersion = $version;

        return $this;
    }

    /**
     * Returns the message headers
     * 
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Check the header by name
     * @param string $name
     * 
     * @return bool
     */
    public function hasHeader($name)
    {
        $name = \Maarch\Http\Description\Header::phpToHttpName($name);

        return array_key_exists($name, $this->headers);
    }

    /**
     * Returns a header by name
     * 
     * @param string $name
     * 
     * @return string[]
     */
    public function getHeader($name)
    {
        $name = \Maarch\Http\Description\Header::phpToHttpName($name);

        return $this->headers[$name];
    }

    /**
     * Return the single header line
     * 
     * @param string $name Case-insensitive header field name.
     * 
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name)
    {
        $name = \Maarch\Http\Description\Header::phpToHttpName($name);

        return implode(',', $this->headers[$name]);
    }

    /**
     * Sets the provided value replacing the specified header.
     * 
     * @param string $name  The name of the header to set
     * @param string $value Header value(s).
     * 
     * @return static
     */
    public function withHeader($name, $value)
    {
        $name = \Maarch\Http\Description\Header::phpToHttpName($name);
        
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * @param string          $name  Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * 
     * @return static
     * 
     * @throws \InvalidArgumentException for invalid header names.
     * @throws \InvalidArgumentException for invalid header values.
     */
    public function withAddedHeader($name, $value)
    {
        $name = \Maarch\Http\Description\Header::phpToHttpName($name);

        $this->headers[$name][] = $value;

        return $this;
    }

    /**
     * Return an instance without the specified header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * 
     * @return static
     */
    public function withoutHeader($name)
    {
        $name = \Maarch\Http\Description\Header::phpToHttpName($name);

        if (isset($this->headers[$name])) {
            unset($this->headers[$name]);
        }

        return $this;
    }

    /**
     * Returns the body stream
     * 
     * @return Psr\Http\Message\StreamInterface
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Sets the body
     * @param Psr\Http\Message\StreamInterface $body
     * 
     * @return static
     */
    public function withBody(\Psr\Http\Message\StreamInterface $body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Sets the encoded body of a message with a string contents
     * @param string $contents The serialized body contents
     * 
     * @return static
     */
    public function withSerializedBody(string $contents)
    {
        $fp = fopen("php://temp", 'r+');
        fputs($fp, $contents);
        rewind($fp);

        return $this->withBody(new \Maarch\Http\Message\Stream($fp));
    }

    /**
     * Export the message to string
     * @return string
     */
    public function __toString()
    {
        $lines = [];

        switch (true) {
            case $this instanceof \Psr\Http\Message\RequestInterface :
                $lines[] = $this->getMethod().' '.str_replace('http://'.$this->getUri()->getAuthority(), '', (string) $this->getUri()).' HTTP/'.$this->getProtocolVersion();
                break;

            case $this instanceof \Psr\Http\Message\ResponseInterface :
                $lines[] = 'HTTP/'.$this->getProtocolVersion().' '.$this->getStatusCode().' '.$this->getReasonPhrase();
                break;
        }

        // Headers
        foreach ($this->getHeaders() as $name => $value) {
            if (is_array($value)) {
                $value = implode(',', str_replace("\n", "", $value));
            }
            $lines[] = $name.': '.$value;
        }
        $lines[] = '';

        // Body
        $bodyStream = $this->getBody();
        $lines[] = $bodyStream->getContents();
        $bodyStream->rewind();

        return implode("\n", $lines);
    }
}