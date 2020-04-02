<?php
/*
Plugin Name: Einsatzverwaltung
Plugin URI: https://einsatzverwaltung.abrain.de
Description: Public incident reports for fire brigades and other rescue services
Version: 1.6.7
Author: Andreas Brain
Author URI: https://www.abrain.de
License: GPLv2
Text Domain: einsatzverwaltung
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
$php_version = phpversion();
if (version_compare($php_version, einsatzverwaltung_minPHPversion()) < 0) {
    wp_die(
        sprintf(
            __('The plugin Einsatzverwaltung requires PHP version %s or newer. Please update PHP on your server.', 'einsatzverwaltung'),
            einsatzverwaltung_minPHPversion()
        ),
        __('Outdated PHP version', 'einsatzverwaltung'),
        array('back_link' => true)
    );
}

require_once dirname(__FILE__) . '/backcompat.php';
require_once dirname(__FILE__) . '/Loader.php';
