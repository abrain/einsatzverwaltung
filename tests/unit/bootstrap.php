<?php // phpcs:ignore PSR1.Files.SideEffects.FoundWithSymbols

// Define test environment
define('EINSATZVERWALTUNG_PHPUNIT', true);

// Define fake ABSPATH
if (! defined('ABSPATH')) {
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
    define('ABSPATH', sys_get_temp_dir());
}

// Set default time zone to UTC as WordPress would do
date_default_timezone_set('UTC');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/constants.php';
require __DIR__ . '/UnitTestCase.php';
require __DIR__ . '/Stubs/WPRESTServerStub.php';

spl_autoload_register(function ($class) {
    // Do not load classes from other namespaces
    if (strpos($class, 'abrain\\Einsatzverwaltung') === false) {
        return;
    }

    $parts = explode('\\', $class);
    $filename = '';
    $numberOfParts = count($parts);
    for ($index = 2; $index < $numberOfParts; $index++) {
        $filename .= DIRECTORY_SEPARATOR;
        $filename .= $parts[$index];
    }

    if (str_ends_with($filename, 'Test')) {
        include __DIR__ . '/../../tests/unit' . $filename . '.php';
    } else {
        include __DIR__ . '/../../src/includes' . $filename . '.php';
    }
}, true);
