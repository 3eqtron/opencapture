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
namespace Maarch\Http;
/**
 * The Http application
 */
class App
{
    /**
     * @var string
     */
    protected static $name;

    /**
     * @var string
     */
    protected static $start;

    /**
     * @var array
     */
    protected static $identityProviders = [];

    /**
     * @var Psr\Log\LoggerInterface
     */
    protected static $logger;

    /**
     * @var Psr\Cache\CacheItemPoolInterface
     */
    protected static $cacheItemPool;

    /**
     * @var string[]
     */
    protected static $headers = [];

    /**
     * @var string[]
     */
    protected static $errors = [];

    /* Methods */
    /**
     * Sets the service name
     * @param string $name
     */
    public static function setName($name)
    {
        static::$name = $name;
    }

    /**
     * Returns the service name
     * @return string
     */
    public static function getName()
    {
        return static::$name;
    }

    /**
     * Sets the root directory name
     * @param string $start
     */
    public static function setStart($start)
    {
        static::$start = $start;
    }

    /**
     * Returns the service name
     * @return string
     */
    public static function getStart()
    {
        return static::$start;
    }

    /**
     * Adds an identityProvider
     * @param string                                $id
     * @param Maarch\Auth\IdentityProviderInterface $identityProvider
     * @param bool                                  $default
     */
    public static function addIdentityProvider(string $id, \Maarch\Auth\IdentityProviderInterface $identityProvider, $default=false)
    {
        if ($default) {
            static::$identityProviders = array_merge([$id => $identityProvider], static::$identityProviders);
        } else {
            static::$identityProviders[$id] = $identityProvider;
        }
    }

    /**
     * Checks the identityProvider
     * @param string $id
     * 
     * @return bool
     */
    public static function hasIdentityProvider(string $id=null)
    {
        if ($id) {
            return isset(static::$identityProviders[$id]);
        }

        return !empty($identityProviders);
    }
        
    /**
     * Returns the identityProvider
     * @param string $id
     * 
     * @return Maarch\Auth\IdentityProviderInterface
     */
    public static function getIdentityProvider(string $id=null)
    {
        if ($id) {
            if (isset(static::$identityProviders[$id])) {
                return static::$identityProviders[$id];
            }
        }

        return reset(static::$identityProviders[$id]);
    }

    /**
     * Get a request definition by reference
     * @param string $id
     * 
     * @return Request
     */
    public static function getRequest($id)
    {
        return new Description\Request(new \ReflectionClass($id));
    }

    /**
     * Get a response definition by reference
     * @param string $id
     * 
     * @return Response
     */
    public static function getResponse($id)
    {
        return new Description\Response(new \ReflectionClass($id));
    }

    /**
     * Get an error definition by reference
     * @param string $id
     * 
     * @return Response
     */
    public static function getError($id)
    {
        return new Description\Error(new \ReflectionClass($id));
    }

    /**
     * Get a header by reference
     * @param string $id
     * 
     * @return Header
     */
    public static function getHeader($id)
    {
        return new Description\Header(new \ReflectionClass($id));
    }

    /**
     * Get a query parameter by reference
     * @param string $id
     * 
     * @return QueryParam
     */
    public static function getQueryParam($id)
    {
        return new Description\QueryParam(new \ReflectionClass($id));
    }

    /**
     * Get a status code by reference
     * @param string $id
     * 
     * @return Status
     */
    public static function getStatus($id)
    {
        return new Description\Status(new \ReflectionClass($id));
    }

    /**
     * Sets the logger
     * @param Psr\Log\LoggerInterface $logger
     */
    public static function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        static::$logger = $logger;
    }

    /**
     * Returns the a logger
     * @return Psr\Log\LoggerInterface
     */
    public static function getLogger()
    {
        return static::$logger;
    }

    /**
     * Sets the cache pool
     * @param Psr\Cache\CacheItemPoolInterface $cacheItemPool
     */
    public static function setCacheItemPool(\Psr\Cache\CacheItemPoolInterface $cacheItemPool)
    {
        static::$cacheItemPool = $cacheItemPool;
    }

    /**
     * Returns the a logger
     * @return Psr\Cache\CacheItemPoolInterface
     */
    public static function getCacheItempool()
    {
        return static::$cacheItemPool;
    }


}