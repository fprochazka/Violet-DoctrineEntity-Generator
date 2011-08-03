<?php

/**
 * My Application bootstrap file.
 */

use Nette\Diagnostics\Debugger;


// Load Nette Framework
$params['libsDir'] = __DIR__ . '/../libs';
if (!file_exists($params['libsDir'] . '/Nette/loader.php')) {
	die('Please copy Nette Framework to libs/Nette');
}
require $params['libsDir'] . '/Nette/loader.php';


// Enable Nette Debugger for error visualisation & logging
Debugger::$logDirectory = __DIR__ . '/../log';
Debugger::$strictMode = TRUE;
Debugger::enable();


// Load configuration from config.neon file
$configurator = new Nette\Configurator;
$configurator->container->params += $params;
$configurator->container->params['tempDir'] = __DIR__ . '/../temp';
$container = $configurator->loadConfig(__DIR__ . '/config.neon');


// Setup router
$router = $container->router;
$router[] = new Nette\Application\Routers\SimpleRouter('Entities:default');


// Configure and run the application!
$application = $container->application;
//$application->catchExceptions = TRUE;
$application->errorPresenter = 'Error';
$application->run();
