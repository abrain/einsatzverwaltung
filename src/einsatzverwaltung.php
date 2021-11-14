<?php
/*
Plugin Name: Einsatzverwaltung
Plugin URI: https://einsatzverwaltung.org
Description: Public incident reports for fire departments and other rescue services
Version: 1.10.0
Author: Andreas Brain
Author URI: https://www.abrain.de
License: GPLv2
Text Domain: einsatzverwaltung
Requires at least: 5.1.0
Requires PHP: 7.1.0
*/

if (!defined('ABSPATH')) {
    die('You shall not pass!');
}

/**
 * Gibt die Hauptdatei des Plugins zurück, wichtig für bestimmte Hooks
 * @return string
 */
function einsatzverwaltung_plugin_file()
{
    return __FILE__;
}

/**
 * Returns the required PHP version for this plugin
 *
 * @return string
 */
function einsatzverwaltung_minPHPversion()
{
    $file = dirname(einsatzverwaltung_plugin_file()) . '/readme.txt';
    $fileData = get_file_data($file, array('PHPmin' => 'Requires PHP'));
    return $fileData['PHPmin'];
}

/**
 * Prüfe, ob PHP mindestens in Version $php_version_min läuft
 */
if (version_compare(phpversion(), einsatzverwaltung_minPHPversion()) < 0) {
    wp_die(
        sprintf(
            // translators: 1: PHP version number
            __('The plugin Einsatzverwaltung requires PHP version %s or newer. Please update PHP on your server.', 'einsatzverwaltung'),
            einsatzverwaltung_minPHPversion()
        ),
        __('Outdated PHP version', 'einsatzverwaltung'),
        array('back_link' => true)
    );
}

require_once dirname(__FILE__) . '/backcompat.php';
require_once dirname(__FILE__) . '/Loader.php';
