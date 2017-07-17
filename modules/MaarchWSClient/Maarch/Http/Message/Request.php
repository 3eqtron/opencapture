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
 * Http wrapper for incoming http requests
 *
 */
class Request
    extends MessageAbstract
    implements \Psr\Http\Message\RequestInterface
{
    /**
     * @var string The http method
     */
    protected $method;

    /**
     * @var Uri The http uri
     */
    protected $uri;

    /**
     * Constructor
     * @param string $target An optional Url for the request
     */
    public function __construct($target=null)
    {
        $this->uri = new Uri($target);
    }

    /**
     * returns the request target 
     * 
     * @return string
     */
    public function getRequestTarget()
    {
        return (string) $this->uri;
    }

    /**
     * Sets the requets target
     * 
     * @param mixed $target
     * 
     * @return static
     */
    public function withRequestTarget($target)
    {
        $this->uri = new Uri($target);

        return $this;
    }

    /**
     * Get the method name
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * @param string $method Case-sensitive method.
     *
     * @return static
     *
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get the uri object
     * @return RequestUri
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * @param UriInterface $uri          New request URI to use.
     * @param bool         $preserveHost Preserve the original state of the Host header.
     * 
     * @return static
     */
    public function withUri(\Psr\Http\Message\UriInterface $uri, $preserveHost = false)
    {
        if ($preserveHost) {
            $uri->withHost($this->uri->getHost());
        }

        $this->uri = $uri;

        return $this;
    }
    
}