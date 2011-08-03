<?php

use Nette\Diagnostics\Debugger;

// required constants
define('APP_DIR', __DIR__ . '/../app');
define('TEMP_DIR', __DIR__ . '/../temp');
define('TESTS_DIR', __DIR__);
define('LIBS_DIR', __DIR__ . '/../libs');

// Take care of autoloading
require_once LIBS_DIR . '/Nette/loader.php';

// Setup Nette debuger
Debugger::enable(Debugger::PRODUCTION);
Debugger::$logDirectory = APP_DIR . '/../log';
Debugger::$maxLen = 4096;

// Init Nette Framework robot loader
$loader = new Nette\Loaders\RobotLoader;
$loader->setCacheStorage(new Nette\Caching\Storages\MemoryStorage);
$loader->addDirectory(TESTS_DIR);
$loader->register();

// start session on time
$configurator = new Nette\Configurator();
$configurator->loadConfig(APP_DIR . '/config.neon');
$configurator->container->session->start();