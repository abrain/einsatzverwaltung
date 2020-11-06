<?php

// Define test environment
define('EINSATZVERWALTUNG_PHPUNIT', true);

// Define fake ABSPATH
if (! defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir());
}

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/constants.php';
require __DIR__ . '/UnitTestCase.php';

spl_autoload_register(function ($class) {
    // Do not load classes from other namespaces
    if (strpos($class, 'abrain\\Einsatzverwaltung') === false) {
        return;
    }

    $parts = explode('\\', $class);
    $filename = '';
    for ($index = 2; $index < count($parts); $index++) {
        $filename .= DIRECTORY_SEPARATOR;
        $filename .= $parts[$index];
    }

    include __DIR__ . '/../../src/' . $filename . '.php';
}, true);
