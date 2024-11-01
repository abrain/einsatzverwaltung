<?php
/*
Plugin Name: Einsatzverwaltung
Plugin URI: https://einsatzverwaltung.org
Description: Public incident reports for fire departments and other rescue services
Version: 1.12.0
Author: Andreas Brain
Author URI: https://www.abrain.de
License: GPLv2
Text Domain: einsatzverwaltung
Requires at least: 5.6.0
Requires PHP: 7.1.0
*/

use abrain\Einsatzverwaltung\Core;

if (!defined('ABSPATH')) {
    die('You shall not pass!');
}

try {
    spl_autoload_register(function (string $class) {
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

        include dirname(__FILE__) . "$filename.php";
    });
} catch (Exception $exception) {
    add_action('admin_notices', function () {
        $pluginData = get_plugin_data(__FILE__);
        $message = sprintf(
            // translators: 1: plugin name
            __('The plugin %s cannot be initialized (spl_autoload_register() failed)', 'einsatzverwaltung'),
            $pluginData['Name']
        );
        printf('<div class="notice notice-error"><p>%1$s</p></div>', esc_html($message));
    });
    return;
}

// Bootstrap the plugin
Core::getInstance()->setPluginFile(__FILE__)->addHooks();

// Register REST API routes
add_action('rest_api_init', function () {
    (new abrain\Einsatzverwaltung\Api\Initializer())->onRestApiInit();
});
