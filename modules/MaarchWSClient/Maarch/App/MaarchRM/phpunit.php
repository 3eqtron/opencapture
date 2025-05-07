<?php
set_include_path(
    __DIR__.'/../../..'.PATH_SEPARATOR. // Maarch container directory
    __DIR__.'/../..'.PATH_SEPARATOR.    // Psr container directory
    __DIR__.'/..'.PATH_SEPARATOR.       // Current application container directory
    get_include_path()
);

// Require base class
require_once '../../Loader.php';

// Dependency injection container
$GLOBALS["container"] = new \Maarch\Container\ReflectionContainer('MaarchRM\ServiceContainer', parse_ini_file(__DIR__.'/configuration.ini', true));
