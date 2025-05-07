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
require '../conf.php';

// Require base class
require_once 'Maarch/Loader.php';


// Dependency injection container
$container = new Maarch\Container\ReflectionContainer(CONTAINER, parse_ini_file(CONTAINER_CONF, true));

Maarch\Rest\App::setName(APP_NAME);

// Set rest start resource
Maarch\Rest\App::setStart(START_RESOURCE);

// Add auth handlers
//Maarch\Rest\App::addIdentityProvider('token', new Maarch\Http\Auth\TokenAuthentication('foo'));
//Maarch\Rest\App::addIdentityProvider('basic', new Maarch\Auth\HttpAuthentication('bar', 'Basic', ['realm'=>'MaarchRM']));
//Maarch\Http\App::setLogger(new Maarch\Log\File('C:/xampp/htdocs/Maarch/_other/data/log_%d.txt'));
//Maarch\Http\App::setCacheItemPool(new Maarch\Cache\Memcache('127.0.0.1:11211'));

$httpTransport = new Maarch\Http\Transport\ApacheServer();
$httpRequest = $httpTransport->receiveRequest();

$httpResponse = Maarch\Rest\Server::process($httpRequest);

$httpTransport->sendResponse($httpResponse);
