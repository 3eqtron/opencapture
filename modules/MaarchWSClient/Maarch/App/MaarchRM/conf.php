<?php 
/*
 * Copyright (C) 2017 Maarch
 *
 * This file is part of MaarchRM.
 *
 * MaarchRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MaarchRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with MaarchRM. If not, see <http://www.gnu.org/licenses/>.
 */

// Add the source to include path for class autoload + static file load
set_include_path(
    __DIR__.'/../../..'.PATH_SEPARATOR. // Maarch container directory
    __DIR__.'/../..'.PATH_SEPARATOR.    // Psr container directory
    __DIR__.'/..'.PATH_SEPARATOR.       // Current application container directory
    get_include_path()
);

define('APP_NAME', 'Maars');
define('START_RESOURCE', 'MaarchRM\Rest\Resources');
define('CONTAINER', 'MaarchRM\ServiceContainer');
define('CONTAINER_CONF', __DIR__.'/configuration.ini');
