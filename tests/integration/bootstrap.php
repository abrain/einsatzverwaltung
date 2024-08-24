<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
$tmpDirEnv = getenv('TMPDIR');
$tmpdir = rtrim(empty($tmpDirEnv) ? '/tmp' : $tmpDirEnv, '/');
$_tests_dir = "{$tmpdir}/wordpress-tests-lib";

require __DIR__ . '/../../vendor/autoload.php';
require_once $_tests_dir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', function () {
    require dirname(__FILE__, 2) . '/../src/einsatzverwaltung.php';

    // Simulate the activation of the plugin
    do_action('activate_src/einsatzverwaltung.php');

    // Zeitzone setzen
    update_option('timezone_string', 'Europe/Berlin');
});

require $_tests_dir . '/includes/bootstrap.php';

require_once 'ReportFactory.php';

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
