<?php

if (!defined('WATCHDOG_NOTICE')) {
    define('WATCHDOG_NOTICE', 5);
}

$moduleDir =  dirname(__DIR__);

if (file_exists($moduleDir . "/vendor/autoload.php")) {

    $loader = require_once $moduleDir . "/vendor/autoload.php";

} else {
    die(
        "\n[ERROR] You need to run composer before running the test suite.\n".
            "To do so run the following commands:\n".
            "    curl -s http://getcomposer.org/installer | php\n".
            "    php composer.phar install --dev\n\n"
    );
}

$loader->addClassMap(array("."));
