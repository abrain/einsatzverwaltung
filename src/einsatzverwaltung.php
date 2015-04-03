<?php
/*
Plugin Name: Einsatzverwaltung
Plugin URI: https://www.abrain.de/software/einsatzverwaltung/
Description: Verwaltung und Darstellung von Einsatzberichten der Feuerwehr und anderer Hilfsorganisationen
Version: 0.9.0
Author: Andreas Brain
Author URI: https://www.abrain.de
License: GPLv2
Text Domain: einsatzverwaltung
*/

$php_version_min = '5.3.0';

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

register_activation_hook(__FILE__, array('abrain\Einsatzverwaltung\Core', 'onActivation'));
add_filter(
    'plugin_action_links_' . plugin_basename(__FILE__),
    array('abrain\Einsatzverwaltung\Settings','addActionLinks')
);
