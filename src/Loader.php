<?php
namespace abrain\Einsatzverwaltung;

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
    public static function load($class)
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

$autoloaderRegistered = spl_autoload_register(__NAMESPACE__ . '\Loader::load');

if ($autoloaderRegistered === false) {
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

Core::getInstance();
