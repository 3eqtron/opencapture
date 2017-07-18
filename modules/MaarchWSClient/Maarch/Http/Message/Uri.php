<?php
/*
 * Copyright (C) 2015 Maarch
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Http\Message;
/**
 * Class for incoming http server requests URIs
 *
 */
class Uri
    implements \Psr\Http\Message\UriInterface
{
    /* Properties */
    /**
     * @var string The scheme
     */
    protected $scheme;

    /**
     * @var string the username
     */
    protected $user;

    /**
     * @var string the password
     */
    protected $password;

    /**
     * @var string The host
     */
    protected $host;

    /**
     * @var integer The port
     */
    protected $port = 80;

    /**
     * @var string The path
     */
    protected $path;

    /**
     * @var string The query
     */
    protected $query;

    /**
     * @var string The fragment
     */
    protected $fragment;

    /* Methods */
    /**
     * Constructor
     * @param string $target An optional Url for the request
     */
    public function __construct($target=null)
    {
        if ($target) {
            $parser = parse_url($target);

            if (isset($parser['scheme'])) {
                $this->withScheme($parser['scheme']);
            }
            if (isset($parser['user'])) {
                if (isset($parser['pass'])) {
                    $this->withUserInfo($parser['user'], $parser['pass']);
                } else {
                    $this->withUserInfo($parser['user']);
                }
            }
            if (isset($parser['host'])) {
                $this->withHost($parser['host']);
            }
            if (isset($parser['port'])) {
                $this->withPort($parser['port']);
            }
            if (isset($parser['path'])) {
                $this->withPath($parser['path']);
            }
            if (isset($parser['query'])) {
                $this->withQuery($parser['query']);
            }
            if (isset($parser['fragment'])) {
                $this->withFragment($parser['fragment']);
            }
        }
    }

    /**
     * Retrieve the scheme name
     * 
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Retrieve the authority component of the URI
     * 
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
        $authority = null;

        if ($this->user) {
            $authority .= $this->user;
            if (isset($this->password)) {
                $authority .= ':'.$this->password;
            }
            $authority .= '@';
        }

        $authority .= $this->getHost();

        if ($port = $this->getPort()) {
            $authority .= ':'.$port;
        }

        return $authority;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        $userinfo = null;

        if ($this->user) {
            $userinfo .= $this->user;
            if (isset($this->password)) {
                $userinfo .= ':'.$this->password;
            }
        }

        return $userinfo;
    }

    /**
     * Retrieve the host
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Retrieve the port
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Retrieve the path
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Retrieve the query string
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Retrieve the fragment after #
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * 
     * @return static A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid schemes.
     * @throws \InvalidArgumentException for unsupported schemes.
     */
    public function withScheme($scheme)
    {
        $this->scheme = $scheme;

        $this;
    }

    /**
     * Return an instance with the specified user information.
     *
     * @param string      $user     The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * 
     * @return static A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null)
    {
        $this->user = $user;

        if ($password) {
            $this->password = $password;
        }
    }

    /**
     * Return an instance with the specified host.
     *
     * @param string $host The hostname to use with the new instance.
     * 
     * @return static A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Return an instance with the specified port.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * 
     * @return static A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Return an instance with the specified path.
     *
     * @param string $path The path to use with the new instance.
     * 
     * @return static A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Return an instance with the specified query string.
     *
     * @param string $query The query string to use with the new instance.
     * 
     * @return static A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * 
     * @return static A new instance with the specified fragment.
     */
    public function withFragment($fragment)
    {
        $this->fragment = $fragment;

        return $this;
    }

    /**
     * Return the string representation as a URI reference.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString()
    {
        $str = $this->scheme.'://';

        $str .= $this->getAuthority();

        if (isset($this->path)) {
            $str .= $this->path;
        }
        
        if (!empty($this->query)) {
            $str .= "?" . $this->query;
        }

        return $str;
    }
}