<?php
namespace abrain\Einsatzverwaltung;

require_once dirname(__FILE__) . '/einsatzverwaltung-admin.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-data.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-utilities.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-frontend.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-widget.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-options.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-shortcodes.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-settings.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-tools.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-tools-wpe.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-taxonomies.php';

use WP_Query;
use wpdb;

/**
 * Grundlegende Funktionen
 */
class Core
{
    const DB_VERSION = 4;

    //public static $pluginBase;
    public static $pluginDir;
    public static $pluginUrl;
    public static $scriptUrl;
    public static $styleUrl;

    private static $args_einsatz = array(
        'labels' => array(
            'name' => 'Einsatzberichte',
            'singular_name' => 'Einsatzbericht',
            'menu_name' => 'Einsatzberichte',
            'add_new' => 'Neu',
            'add_new_item' => 'Neuer Einsatzbericht',
            'edit' => 'Bearbeiten',
            'edit_item' => 'Einsatzbericht bearbeiten',
            'new_item' => 'Neuer Einsatzbericht',
            'view' => 'Ansehen',
            'view_item' => 'Einsatzbericht ansehen',
            'search_items' => 'Einsatzberichte suchen',
            'not_found' => 'Keine Einsatzberichte gefunden',
            'not_found_in_trash' => 'Keine Einsatzberichte im Papierkorb gefunden'
        ),
        'public' => true,
        'has_archive' => true,
        'rewrite' => array(
            'slug' => 'einsaetze',
            'feeds' => true
        ),
        'supports' => array('title', 'editor', 'thumbnail', 'publicize', 'author'),
        'show_in_nav_menus' => false,
        'capability_type' => array('einsatzbericht', 'einsatzberichte'),
        'map_meta_cap' => true,
        'menu_position' => 5
    );

    private static $args_einsatzart = array(
        'label' => 'Einsatzarten',
        'labels' => array(
            'name' => 'Einsatzarten',
            'singular_name' => 'Einsatzart',
            'menu_name' => 'Einsatzarten',
            'all_items' => 'Alle Einsatzarten',
            'edit_item' => 'Einsatzart bearbeiten',
            'view_item' => 'Einsatzart ansehen',
            'update_item' => 'Einsatzart aktualisieren',
            'add_new_item' => 'Neue Einsatzart',
            'new_item_name' => 'Einsatzart hinzuf&uuml;gen',
            'search_items' => 'Einsatzarten suchen',
            'popular_items' => 'H&auml;ufige Einsatzarten',
            'separate_items_with_commas' => 'Einsatzarten mit Kommata trennen',
            'add_or_remove_items' => 'Einsatzarten hinzuf&uuml;gen oder entfernen',
            'choose_from_most_used' => 'Aus h&auml;ufigen Einsatzarten w&auml;hlen'),
        'public' => true,
        'show_in_nav_menus' => false,
        'meta_box_cb' => 'abrain\Einsatzverwaltung\Admin::displayMetaBoxEinsatzart',
        'capabilities' => array (
            'manage_terms' => 'edit_einsatzberichte',
            'edit_terms' => 'edit_einsatzberichte',
            'delete_terms' => 'edit_einsatzberichte',
            'assign_terms' => 'edit_einsatzberichte'
        ),
        'hierarchical' => true
    );

    private static $args_fahrzeug = array(
        'label' => 'Fahrzeuge',
        'labels' => array(
            'name' => 'Fahrzeuge',
            'singular_name' => 'Fahrzeug',
            'menu_name' => 'Fahrzeuge',
            'all_items' => 'Alle Fahrzeuge',
            'edit_item' => 'Fahrzeug bearbeiten',
            'view_item' => 'Fahrzeug ansehen',
            'update_item' => 'Fahrzeug aktualisieren',
            'add_new_item' => 'Neues Fahrzeug',
            'new_item_name' => 'Fahrzeug hinzuf&uuml;gen',
            'search_items' => 'Fahrzeuge suchen',
            'popular_items' => 'Oft eingesetzte Fahrzeuge',
            'separate_items_with_commas' => 'Fahrzeuge mit Kommata trennen',
            'add_or_remove_items' => 'Fahrzeuge hinzuf&uuml;gen oder entfernen',
            'choose_from_most_used' => 'Aus h&auml;ufig eingesetzten Fahrzeugen w&auml;hlen'),
        'public' => true,
        'show_in_nav_menus' => false,
        'capabilities' => array (
            'manage_terms' => 'edit_einsatzberichte',
            'edit_terms' => 'edit_einsatzberichte',
            'delete_terms' => 'edit_einsatzberichte',
            'assign_terms' => 'edit_einsatzberichte'
        )
    );

    private static $argsExteinsatzmittel = array(
        'label' => 'Externe Einsatzmittel',
        'labels' => array(
            'name' => 'Externe Einsatzmittel',
            'singular_name' => 'Externes Einsatzmittel',
            'menu_name' => 'Externe Einsatzmittel',
            'all_items' => 'Alle externen Einsatzmittel',
            'edit_item' => 'Externes Einsatzmittel bearbeiten',
            'view_item' => 'Externes Einsatzmittel ansehen',
            'update_item' => 'Externes Einsatzmittel aktualisieren',
            'add_new_item' => 'Neues externes Einsatzmittel',
            'new_item_name' => 'Externes Einsatzmittel hinzuf&uuml;gen',
            'search_items' => 'Externe Einsatzmittel suchen',
            'popular_items' => 'Oft eingesetzte externe Einsatzmittel',
            'separate_items_with_commas' => 'Externe Einsatzmittel mit Kommata trennen',
            'add_or_remove_items' => 'Externe Einsatzmittel hinzuf&uuml;gen oder entfernen',
            'choose_from_most_used' => 'Aus h&auml;ufig eingesetzten externen Einsatzmitteln w&auml;hlen'),
        'public' => true,
        'show_in_nav_menus' => false,
        'capabilities' => array (
            'manage_terms' => 'edit_einsatzberichte',
            'edit_terms' => 'edit_einsatzberichte',
            'delete_terms' => 'edit_einsatzberichte',
            'assign_terms' => 'edit_einsatzberichte'
        ),
        'rewrite' => array(
            'slug' => 'externe-einsatzmittel'
        )
    );

    private static $args_alarmierungsart = array(
        'label' => 'Alarmierungsart',
        'labels' => array(
            'name' => 'Alarmierungsarten',
            'singular_name' => 'Alarmierungsart',
            'menu_name' => 'Alarmierungsarten',
            'all_items' => 'Alle Alarmierungsarten',
            'edit_item' => 'Alarmierungsart bearbeiten',
            'view_item' => 'Alarmierungsart ansehen',
            'update_item' => 'Alarmierungsart aktualisieren',
            'add_new_item' => 'Neue Alarmierungsart',
            'new_item_name' => 'Alarmierungsart hinzuf&uuml;gen',
            'search_items' => 'Alarmierungsart suchen',
            'popular_items' => 'H&auml;ufige Alarmierungsarten',
            'separate_items_with_commas' => 'Alarmierungsarten mit Kommata trennen',
            'add_or_remove_items' => 'Alarmierungsarten hinzuf&uuml;gen oder entfernen',
            'choose_from_most_used' => 'Aus h&auml;ufigen Alarmierungsarten w&auml;hlen'),
        'public' => true,
        'show_in_nav_menus' => false,
        'capabilities' => array (
            'manage_terms' => 'edit_einsatzberichte',
            'edit_terms' => 'edit_einsatzberichte',
            'delete_terms' => 'edit_einsatzberichte',
            'assign_terms' => 'edit_einsatzberichte'
        )
    );

    private $data;

    /**
     * Constructor
     */
    public function __construct()
    {
        self::$pluginDir = plugin_dir_path(__FILE__);
        self::$pluginUrl = plugin_dir_url(__FILE__);
        self::$scriptUrl = self::$pluginUrl . 'js/';
        self::$styleUrl = self::$pluginUrl . 'css/';

        if (Utilities::isMinWPVersion("3.9")) {
            self::$args_einsatz['menu_icon'] = 'dashicons-media-document';
        }

        new Admin();
        $this->data = new Data();
        $frontend = new Frontend();
        new Settings();
        new Shortcodes($frontend);
        new Taxonomies();
        new ToolEinsatznummernReparieren($this->data);
        new ToolImportWpEinsatz();

        $this->addHooks();
    }

    private function addHooks()
    {
        add_action('init', array($this, 'onInit'));
        add_action('plugins_loaded', array($this, 'onPluginsLoaded'));
        add_action('save_post', array($this->data, 'savePostdata'));
    }

    /**
     * Wird beim Aktivieren des Plugins aufgerufen
     */
    public static function onActivation()
    {
        // Posttypen registrieren
        self::registerTypes();

        // Permalinks aktualisieren
        flush_rewrite_rules();
    }

    /**
     * Plugin initialisieren
     */
    public function onInit()
    {
        self::registerTypes();
        $this->addRewriteRules();
    }

    public function onPluginsLoaded()
    {
        $this->checkDatabaseVersion();
    }

    /**
     * Erzeugt den neuen Beitragstyp Einsatzbericht und die zugehörigen Taxonomien
     */
    private static function registerTypes()
    {
        register_post_type('einsatz', self::$args_einsatz);
        register_taxonomy('einsatzart', 'einsatz', self::$args_einsatzart);
        register_taxonomy('fahrzeug', 'einsatz', self::$args_fahrzeug);
        register_taxonomy('exteinsatzmittel', 'einsatz', self::$argsExteinsatzmittel);
        register_taxonomy('alarmierungsart', 'einsatz', self::$args_alarmierungsart);
    }

    private function addRewriteRules()
    {
        $base = self::$args_einsatz['rewrite']['slug'];
        add_rewrite_rule(
            $base . '/(\d{4})/page/(\d{1,})/?$',
            'index.php?post_type=einsatz&year=$matches[1]&paged=$matches[2]',
            'top'
        );
        add_rewrite_rule($base . '/(\d{4})/?$', 'index.php?post_type=einsatz&year=$matches[1]', 'top');
    }

    /**
     * Berechnet die nächste freie Einsatznummer für das gegebene Jahr
     *
     * @param string $jahr
     * @param bool $minuseins Wird beim Speichern der zusätzlichen Einsatzdaten in einsatzverwaltung_save_postdata
     * benötigt, da der Einsatzbericht bereits gespeichert wurde, aber bei der Zählung für die Einsatznummer
     * ausgelassen werden soll
     *
     * @return string Nächste freie Einsatznummer im angegebenen Jahr
     */
    public static function getNextEinsatznummer($jahr, $minuseins = false)
    {
        if (empty($jahr) || !is_numeric($jahr)) {
            $jahr = date('Y');
        }
        $query = new WP_Query('year=' . $jahr .'&post_type=einsatz&post_status=publish&nopaging=true');
        return self::formatEinsatznummer($jahr, $query->found_posts + ($minuseins ? 0 : 1));
    }

    /**
     * Formatiert die Einsatznummer
     *
     * @param string $jahr Jahreszahl
     * @param int $nummer Laufende Nummer des Einsatzes im angegebenen Jahr
     *
     * @return string Formatierte Einsatznummer
     */
    public static function formatEinsatznummer($jahr, $nummer)
    {
        $stellen = Options::getEinsatznummerStellen();
        $lfdvorne = Options::isEinsatznummerLfdVorne();
        if ($lfdvorne) {
            return str_pad($nummer, $stellen, "0", STR_PAD_LEFT).$jahr;
        } else {
            return $jahr.str_pad($nummer, $stellen, "0", STR_PAD_LEFT);
        }
    }

    /**
     * Gibt ein Array aller Felder und deren Namen zurück,
     * Hauptverwendungszweck ist das Mapping beim Import
     */
    public static function getFields()
    {
        return array_merge(self::getMetaFields(), self::getTerms(), self::getPostFields());
    }

    /**
     * Gibt die möglichen Spalten für die Einsatzübersicht zurück
     *
     * @return array
     */
    public static function getListColumns()
    {
        return array(
            'number' => array(
                'name' => 'Nummer'
            ),
            'date' => array(
                'name' => 'Datum'
            ),
            'time' => array(
                'name' => 'Zeit'
            ),
            'title' => array(
                'name' => 'Einsatzmeldung'
            ),
            'incidentCommander' => array(
                'name' => 'Einsatzleiter'
            ),
            'location' => array(
                'name' => 'Einsatzort'
            ),
            'workforce' => array(
                'name' => 'Mannschaftsst&auml;rke'
            ),
            'duration' => array(
                'name' => 'Dauer'
            ),
            'vehicles' => array(
                'name' => 'Fahrzeuge'
            ),
            'alarmType' => array(
                'name' => 'Alarmierungsart'
            ),
            'additionalForces' => array(
                'name' => 'Weitere Kräfte'
            ),
            'incidentType' => array(
                'name' => 'Einsatzart'
            ),
            'seqNum' => array(
                'name' => 'Lfd.',
                'longName' => 'Laufende Nummer'
            )
        );
    }

    /**
     * Gibt die möglichen Kurzfassungstypen zurück
     *
     * @return array
     */
    public static function getExcerptTypes()
    {
        return array(
            'none' => 'Leer',
            'details' => 'Einsatzdetails',
            'text' => 'Berichtstext'
        );
    }

    /**
     * Gibt die möglichen Berechtigungen für Einsatzberichte zurück
     *
     * @return array
     */
    public static function getCapabilities()
    {
        return array(
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
    }

    /**
     * Gibt die slugs und Namen der Metafelder zurück
     *
     * @return array
     */
    public static function getMetaFields()
    {
        return array(
            'einsatz_einsatzort' => 'Einsatzort',
            'einsatz_einsatzleiter' => 'Einsatzleiter',
            'einsatz_einsatzende' => 'Einsatzende',
            'einsatz_fehlalarm' => 'Fehlalarm',
            'einsatz_mannschaft' => 'Mannschaftsstärke'
        );
    }

    /**
     * Gibt die slugs und Namen der Taxonomien zurück
     *
     * @return array
     */
    public static function getTerms()
    {
        return array(
            'alarmierungsart' => 'Alarmierungsart',
            'einsatzart' => 'Einsatzart',
            'fahrzeug' => 'Fahrzeuge',
            'exteinsatzmittel' => 'Externe Einsatzmittel'
        );
    }

    /**
     * Gibt slugs und Namen der Direkt dem Post zugeordneten Felder zurück
     *
     * @return array
     */
    public static function getPostFields()
    {
        return array(
            'post_date' => 'Alarmzeit',
            'post_name' => 'Einsatznummer',
            'post_content' => 'Berichtstext',
            'post_title' => 'Einsatzstichwort'
        );
    }

    /**
     * Reparaturen oder Anpassungen der Datenbank nach einem Update
     */
    private function checkDatabaseVersion()
    {
        $evwInstalledVersion = get_option('einsatzvw_db_version');

        if ($evwInstalledVersion === false) {
            $evwInstalledVersion = 0;
        } elseif (is_numeric($evwInstalledVersion)) {
            $evwInstalledVersion = intval($evwInstalledVersion);
        } else {
            $evwInstalledVersion = 0;
        }

        if ($evwInstalledVersion < self::DB_VERSION) {
            /** @var wpdb $wpdb */
            global $wpdb;

            switch ($evwInstalledVersion) {
                case 0:
                    // GMT-Datum wurde nicht gespeichert EVW-58
                    foreach (Data::getEinsatzberichte('') as $bericht) {
                        $gmtdate = get_gmt_from_date($bericht->post_date);
                        $result = $wpdb->update(
                            $wpdb->posts,
                            array('post_date_gmt' => $gmtdate),
                            array('ID' => $bericht->ID),
                            array('%s'),
                            array('%d')
                        );
                        if (false === $result) {
                            error_log('Problem beim Aktualisieren des GMT-Datums bei Post-ID ' . $bericht->ID);
                        }
                    }
                    $this->setDatabaseVersion(1);
                    // no break
                case 1:
                    update_option('einsatzvw_cap_roles_administrator', 1);
                    $role_obj = get_role('administrator');
                    foreach (self::getCapabilities() as $cap) {
                        $role_obj->add_cap($cap);
                    }
                    $this->setDatabaseVersion(2);
                    // no break
                case 2:
                    delete_option('einsatzvw_show_links_in_excerpt');
                    $this->setDatabaseVersion(3);
                    // no break
                case 3:
                    $result = $wpdb->delete(
                        $wpdb->postmeta,
                        array(
                            'meta_key' => 'einsatz_mannschaft',
                            'meta_value' => '0'
                        )
                    );
                    if (false === $result) {
                        break;
                    }
                    $this->setDatabaseVersion(4);
                    // no break
            }
        }
    }

    /**
     * @param int $version
     */
    private function setDatabaseVersion($version)
    {
        update_option('einsatzvw_db_version', $version);
    }
}

new Core();
