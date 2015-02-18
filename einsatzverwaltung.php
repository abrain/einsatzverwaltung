<?php
/*
Plugin Name: Einsatzverwaltung
Plugin URI: http://www.abrain.de/software/einsatzverwaltung/
Description: Verwaltung von Feuerwehreins&auml;tzen
Version: 0.8.3
Author: Andreas Brain
Author URI: http://www.abrain.de
License: GPLv2
Text Domain: einsatzverwaltung
*/

check_php_version('5.3.0');

define('EINSATZVERWALTUNG__PLUGIN_BASE', plugin_basename(__FILE__));
define('EINSATZVERWALTUNG__PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EINSATZVERWALTUNG__PLUGIN_URL', plugin_dir_url(__FILE__));
define('EINSATZVERWALTUNG__SCRIPT_URL', EINSATZVERWALTUNG__PLUGIN_URL . 'js/');
define('EINSATZVERWALTUNG__STYLE_URL', EINSATZVERWALTUNG__PLUGIN_URL . 'css/');
define('EINSATZVERWALTUNG__DBVERSION_OPTION', 'einsatzvw_db_version');

// Standardwerte
define('EINSATZVERWALTUNG__EINSATZNR_STELLEN', 3);
define('EINSATZVERWALTUNG__D__SHOW_EXTEINSATZMITTEL_ARCHIVE', false);
define('EINSATZVERWALTUNG__D__SHOW_EINSATZART_ARCHIVE', false);
define('EINSATZVERWALTUNG__D__SHOW_FAHRZEUG_ARCHIVE', false);
define('EINSATZVERWALTUNG__D__HIDE_EMPTY_DETAILS', true);
define('EINSATZVERWALTUNG__D__EXCERPT_TYPE', 'details');
define('EINSATZVERWALTUNG__D__SHOW_EINSATZBERICHTE_MAINLOOP', false);
define('EINSATZVERWALTUNG__D__OPEN_EXTEINSATZMITTEL_NEWWINDOW', false);

require_once(EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-admin.php');
require_once(EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-utilities.php');
require_once(EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-core.php');
require_once(EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-frontend.php');
require_once(EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-widget.php');
require_once(EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-shortcodes.php');
require_once(EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-settings.php');
require_once(EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-tools.php');
require_once(EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-tools-wpe.php');
require_once(EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-taxonomies.php');

global $evw_db_version;
$evw_db_version = 3;

global $evw_caps;
$evw_caps = array(
    'edit_einsatzberichte',
    'edit_private_einsatzberichte',
    'edit_published_einsatzberichte',
    'edit_others_einsatzberichte',
    'publish_einsatzberichte',
    'read_private_einsatzberichte',
    'delete_einsatzberichte',
    'delete_private_einsatzberichte',
    'delete_published_einsatzberichte',
    'delete_others_einsatzberichte'
);

global $evw_meta_fields;
$evw_meta_fields = array(
    'einsatz_einsatzort' => 'Einsatzort',
    'einsatz_einsatzleiter' => 'Einsatzleiter',
    'einsatz_einsatzende' => 'Einsatzende',
    'einsatz_fehlalarm' => 'Fehlalarm',
    'einsatz_mannschaft' => 'Mannschaftsstärke'
);

global $evw_terms;
$evw_terms = array(
    'alarmierungsart' => 'Alarmierungsart',
    'einsatzart' => 'Einsatzart',
    'fahrzeug' => 'Fahrzeuge',
    'exteinsatzmittel' => 'Externe Einsatzmittel'
);

global $evw_post_fields;
$evw_post_fields = array(
    'post_date' => 'Alarmzeit',
    'post_name' => 'Einsatznummer',
    'post_content' => 'Berichtstext',
    'post_title' => 'Einsatzstichwort'
);

use abrain\Einsatzverwaltung\Settings;

new Settings;


/**
 * Wird beim Aktivieren des Plugins aufgerufen
 */
function einsatzverwaltung_aktivierung()
{
    // Posttypen registrieren
    einsatzverwaltung_create_post_type();

    // Permalinks aktualisieren
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'einsatzverwaltung_aktivierung');


/**
 *
 */
function einsatzverwaltung_on_plugins_loaded()
{
    // Sicherstellen, dass Optionen gesetzt sind
    add_option('einsatzvw_einsatznummer_stellen', EINSATZVERWALTUNG__EINSATZNR_STELLEN, '', 'no');
}
add_action('plugins_loaded', 'einsatzverwaltung_on_plugins_loaded');


/**
 * Gibt die Option einsatzvw_einsatz_hideemptydetails als bool zurück
 * TODO in Settings auslagern und für alle bool-Options umschreiben
 *
 * @return bool
 */
function einsatzverwaltung_get_hide_empty_details()
{
    $hide_empty_details = get_option('einsatzvw_einsatz_hideemptydetails');
    if ($hide_empty_details === false) {
        return EINSATZVERWALTUNG__D__HIDE_EMPTY_DETAILS;
    } else {
        return ($hide_empty_details == 1 ? true : false);
    }
}


/**
 * Reparaturen oder Anpassungen der Datenbank nach einem Update
 */
function einsatzverwaltung_update_db_check()
{
    global $evw_db_version;
    $evwInstalledVersion = get_site_option(EINSATZVERWALTUNG__DBVERSION_OPTION);

    if ($evwInstalledVersion === false) {
        $evwInstalledVersion = 0;
    } elseif (is_numeric($evwInstalledVersion)) {
        $evwInstalledVersion = intval($evwInstalledVersion);
    } else {
        $evwInstalledVersion = 0;
    }

    if ($evwInstalledVersion < $evw_db_version) {
        /** @var wpdb $wpdb */
        global $wpdb;

        if ($evwInstalledVersion == 0) {
            $berichte = einsatzverwaltung_get_einsatzberichte('');

            // unhook this function so it doesn't loop infinitely
            remove_action('save_post', 'einsatzverwaltung_save_postdata');

            foreach ($berichte as $bericht) {
                $post_id = $bericht->ID;
                if (! wp_is_post_revision($post_id)) {
                    $gmtdate = get_gmt_from_date($bericht->post_date);
                    $wpdb->query(
                        $wpdb->prepare("UPDATE $wpdb->posts SET post_date_gmt = %s WHERE ID = %d", $gmtdate, $post_id)
                    );
                }
            }

            // re-hook this function
            add_action('save_post', 'einsatzverwaltung_save_postdata');

            $evwInstalledVersion = 1;
            update_site_option(EINSATZVERWALTUNG__DBVERSION_OPTION, $evwInstalledVersion);
        }

        if ($evwInstalledVersion == 1) {
            global $evw_caps;
            update_option('einsatzvw_cap_roles_administrator', 1);
            $role_obj = get_role('administrator');
            foreach ($evw_caps as $cap) {
                $role_obj->add_cap($cap);
            }

            $evwInstalledVersion = 2;
            update_site_option(EINSATZVERWALTUNG__DBVERSION_OPTION, $evwInstalledVersion);
        }

        if ($evwInstalledVersion == 2) {
            delete_option('einsatzvw_show_links_in_excerpt');

            $evwInstalledVersion = 3;
            update_site_option(EINSATZVERWALTUNG__DBVERSION_OPTION, $evwInstalledVersion);
        }

    }
}
add_action('plugins_loaded', 'einsatzverwaltung_update_db_check');


/**
 * Check the version of PHP running on the server
 *
 * @param string $ver Versionsnummer, die mindestens vorhanden sein muss
 */
function check_php_version($ver)
{
    $php_version = phpversion();
    if (version_compare($php_version, $ver) < 0) {
        wp_die(
            "Das Plugin Einsatzverwaltung ben&ouml;tigt PHP Version $ver oder neuer. Bitte aktualisieren Sie PHP auf Ihrem Server!",
            'Veraltete PHP-Version!',
            array('back_link' => true)
        );
    }
}
