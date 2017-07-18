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
namespace Maarch\Container;
/**
 * Service container
 * 
 * @package Maarch
 * @author  Cyril Vazquez Maarch <cyril.vazquez@maarch.org>
 */
class ReflectionContainer
    implements \Psr\Container\ContainerInterface
{
    /**
     * @var array The service configuration object
     */
    protected $configuration;

    /**
     * @var array The instantiated services
     */
    protected $services = [];

    /**
     * @var ReflectionClass 
     */
    protected $reflectionClass;

    /**
     * @var string 
     */
    protected $docComment;

    /* Methods */
    /**
     * Constructor
     * @param string $container     The container definition class
     * @param array  $configuration The configuration array
     */
    public function __construct(string $container, array $configuration)
    {
        $this->reflectionClass = new \ReflectionClass($container);

        $this->docComment = implode("\n", preg_split('# *\n\s*\*(\/| *)?#m', substr($this->reflectionClass->getDocComment(), 3)));

        $this->configuration = $configuration;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for this identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException(['Service %1$s not found', [$id]]);
        }

        if (!isset($this->services[$id])) {
            $reflectionMethod = $this->reflectionClass->getMethod($id);
            
            $configurationObject = [];
            if (isset($this->configuration[$id])) {
                $configurationObject = $this->configuration[$id];
            }

            $args = [];
            foreach ($reflectionMethod->getParameters() as $pos => $reflectionParameter) {
                $name = $reflectionParameter->getName();
                $value = null;
                if (isset($configurationObject[$name])) {
                    $value = $configurationObject[$name];
                } elseif ($reflectionParameter->isDefaultValueAvailable()) {
                    $value = $reflectionParameter->getDefaultValue();
                }

                $args[$pos] = $value;
            }

            // Backward remove null values from array
            do {
                $arg = end($args);
                if ($arg === null) {
                    array_pop($args);
                }
            } while ($arg === null && count($args));

            if ($reflectionMethod->isStatic()) {
                $instance = null;
            } else {
                $instance = $this;
            }
        
            $this->services[$id] = $reflectionMethod->invokeArgs($instance, $args);
        }

        return $this->services[$id];
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($id)
    {
        return $this->reflectionClass->hasMethod($id);
    }
}
