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

spl_autoload_register('Maarch\Loader::autoload');

/**
 * Loader for classes and files
 */
class Loader
{
    const NAMESPACE_SEPARATOR = '\\';

    /**
     * Class autoloader
     * Requires the source file of a given class based on its namespace\name
     * @param string $class The full name of the class to load
     *
     * @return bool
     */
    public static function autoload($class)
    {
        // Build the class file path
        $classfile = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';

        if ($realpath = stream_resolve_include_path($classfile)) {
            require_once $classfile;

            return true;
        }

        return false;
    }

    /**
     * Use a namespace
     * @param string $namespace
     * @param bool   $deep
     * 
     * @return array
     */
    public static function import($namespace, $deep=false)
    {
        $imported = [];

        $dirname = str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
        if ($dirname = stream_resolve_include_path($dirname)) {
            foreach (glob($dirname.DIRECTORY_SEPARATOR.'*.php') as $phpfile) {
                require_once $phpfile;

                $imported[] = $phpfile;
            }
        }
        
        if ($deep) {
            foreach (glob($dirname.DIRECTORY_SEPARATOR."*", GLOB_ONLYDIR) as $subdirname) {
                $imported = array_merge($imported, static::import($subdirname, true));
            }
        }
        
        return $imported;
    }
}
