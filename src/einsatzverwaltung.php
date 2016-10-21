<?php
/*
Plugin Name: Einsatzverwaltung
Plugin URI: https://einsatzverwaltung.abrain.de
Description: Verwaltung und Darstellung von Einsatzberichten der Feuerwehr und anderer Hilfsorganisationen
Version: 1.2.3
Author: Andreas Brain
Author URI: https://www.abrain.de
License: GPLv2
Text Domain: einsatzverwaltung
*/

$php_version_min = '5.3.0';

/**
 * Gibt die Hauptdatei des Plugins zurück, wichtig für bestimmte Hooks
 * @return string
 */
function einsatzverwaltung_plugin_file()
{
    return __FILE__;
}

/**
 * Prüfe, ob PHP mindestens in Version $php_version_min läuft
 */
$php_version = phpversion();
if (version_compare($php_version, $php_version_min) < 0) {
    wp_die(
        'Das Plugin Einsatzverwaltung ben&ouml;tigt PHP Version ' . $php_version_min . ' oder neuer.
        Bitte aktualisieren Sie PHP auf Ihrem Server!',
        'Veraltete PHP-Version!',
        array('back_link' => true)
    );
}

require_once dirname(__FILE__) . '/einsatzverwaltung-core.php';
