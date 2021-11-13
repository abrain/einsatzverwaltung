<?php

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', function () {
    require dirname(dirname(__FILE__)) . '/../src/einsatzverwaltung.php';

    // Simulate the activation of the plugin
    do_action('activate_src/einsatzverwaltung.php');

    // Zeitzone setzen
    update_option('timezone_string', 'Europe/Berlin');
});

require $_tests_dir . '/includes/bootstrap.php';

require_once 'ReportFactory.php';
