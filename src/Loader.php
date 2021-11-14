<?php
namespace abrain\Einsatzverwaltung;

use function add_action;
use function count;
use function get_plugin_data;
use function plugin_basename;
use function plugin_dir_path;
use function plugin_dir_url;
use function spl_autoload_register;

/**
 * Takes care of loading classes whereever they are needed
 *
 * @package abrain\Einsatzverwaltung
 */
class Loader
{
    /**
     * Includes the class file based on the class name
     *
     * @param string $class The fully-qualified name of the class to load
     */
    public static function load(string $class)
    {
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

        include dirname(__FILE__) . $filename . '.php';
    }
}

if (!defined('ABSPATH')) {
    die('You shall not pass!');
}

if (spl_autoload_register(__NAMESPACE__ . '\Loader::load', false) === false) {
    add_action('admin_notices', function () {
        $pluginData = get_plugin_data(einsatzverwaltung_plugin_file());
        $message = sprintf(
            __('The plugin %s cannot be initialized (spl_autoload_register() failed)', 'einsatzverwaltung'),
            $pluginData['Name']
        );
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr('notice notice-error'), esc_html($message));
    });
    return;
}

$einsatzverwaltung_core = Core::getInstance();
add_action('init', array($einsatzverwaltung_core, 'onInit'));
register_activation_hook(einsatzverwaltung_plugin_file(), array($einsatzverwaltung_core, 'onActivation'));
register_deactivation_hook(einsatzverwaltung_plugin_file(), array($einsatzverwaltung_core, 'onDeactivation'));

// Register REST API routes
add_action('rest_api_init', function () {
    (new Api\Initializer())->onRestApiInit();
});
