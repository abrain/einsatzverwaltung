<?php
namespace abrain\Einsatzverwaltung;

require_once dirname(__FILE__) . '/einsatzverwaltung-admin.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-utilities.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-frontend.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-widget.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-options.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-shortcodes.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-settings.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-tools.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-tools-wpe.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-taxonomies.php';

define('EINSATZVERWALTUNG__DBVERSION_OPTION', 'einsatzvw_db_version');

// Standardwerte
define('EINSATZVERWALTUNG__D__SHOW_EXTEINSATZMITTEL_ARCHIVE', false);
define('EINSATZVERWALTUNG__D__SHOW_EINSATZART_ARCHIVE', false);
define('EINSATZVERWALTUNG__D__SHOW_FAHRZEUG_ARCHIVE', false);
define('EINSATZVERWALTUNG__D__HIDE_EMPTY_DETAILS', true);
define('EINSATZVERWALTUNG__D__EXCERPT_TYPE', 'details');
define('EINSATZVERWALTUNG__D__SHOW_EINSATZBERICHTE_MAINLOOP', false);
define('EINSATZVERWALTUNG__D__OPEN_EXTEINSATZMITTEL_NEWWINDOW', false);

use WP_Query;
use wpdb;

/**
 * Grundlegende Funktionen
 */
class Core
{
    const DB_VERSION = 3;

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
        $frontend = new Frontend();
        new Settings();
        new Shortcodes($frontend);
        new Taxonomies();
        new ToolEinsatznummernReparieren($this);
        new ToolImportWpEinsatz();

        $this->addHooks();
    }

    private function addHooks()
    {
        add_action('init', array($this, 'onInit'));
        add_action('plugins_loaded', array($this, 'onPluginsLoaded'));
        add_action('save_post', array($this, 'savePostdata'));
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
     * @param $kalenderjahr
     *
     * @return array
     */
    public static function getEinsatzberichte($kalenderjahr)
    {
        if (empty($kalenderjahr) || strlen($kalenderjahr)!=4 || !is_numeric($kalenderjahr)) {
            $kalenderjahr = '';
        }

        return get_posts(array(
            'nopaging' => true,
            'orderby' => 'post_date',
            'order' => 'ASC',
            'post_type' => 'einsatz',
            'post_status' => 'publish',
            'year' => $kalenderjahr
        ));
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
        $lfdvorne = get_option('einsatzvw_einsatznummer_lfdvorne', false);
        if ($lfdvorne) {
            return str_pad($nummer, $stellen, "0", STR_PAD_LEFT).$jahr;
        } else {
            return $jahr.str_pad($nummer, $stellen, "0", STR_PAD_LEFT);
        }
    }

    /**
     * Zusätzliche Metadaten des Einsatzberichts speichern
     *
     * @param int $post_id ID des Posts
     */
    public function savePostdata($post_id)
    {
        // verify if this is an auto save routine.
        // If it is our form has not been submitted, so we dont want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!array_key_exists('post_type', $_POST) || 'einsatz' !== $_POST['post_type']) {
            return;
        }

        // Prüfen, ob Aufruf über das Formular erfolgt ist
        if (!array_key_exists('einsatzverwaltung_nonce', $_POST) ||
            !wp_verify_nonce($_POST['einsatzverwaltung_nonce'], 'save_einsatz_details')
        ) {
            return;
        }

        // Schreibrechte prüfen
        if (!current_user_can('edit_einsatzbericht', $post_id)) {
            return;
        }

        $update_args = array();

        // Alarmzeit validieren
        $input_alarmzeit = sanitize_text_field($_POST['einsatzverwaltung_alarmzeit']);
        if (!empty($input_alarmzeit)) {
            $alarmzeit = date_create($input_alarmzeit);
        }
        if (empty($alarmzeit)) {
            $alarmzeit = date_create(
                sprintf(
                    '%s-%s-%s %s:%s:%s',
                    $_POST['aa'],
                    $_POST['mm'],
                    $_POST['jj'],
                    $_POST['hh'],
                    $_POST['mn'],
                    $_POST['ss']
                )
            );
        } else {
            $update_args['post_date'] = date_format($alarmzeit, 'Y-m-d H:i:s');
            $update_args['post_date_gmt'] = get_gmt_from_date($update_args['post_date']);
        }

        // Einsatznummer validieren
        $einsatzjahr = date_format($alarmzeit, 'Y');
        $einsatzNrFallback = self::getNextEinsatznummer($einsatzjahr, $einsatzjahr == date('Y'));
        $einsatznummer = sanitize_title($_POST['einsatzverwaltung_nummer'], $einsatzNrFallback, 'save');
        if (!empty($einsatznummer)) {
            $update_args['post_name'] = $einsatznummer; // Slug setzen
        }

        // Einsatzende validieren
        $input_einsatzende = sanitize_text_field($_POST['einsatzverwaltung_einsatzende']);
        if (!empty($input_einsatzende)) {
            $einsatzende = date_create($input_einsatzende);
        }
        if (empty($einsatzende)) {
            $einsatzende = "";
        } else {
            $einsatzende = date_format($einsatzende, 'Y-m-d H:i');
        }

        // Einsatzort validieren
        $einsatzort = sanitize_text_field($_POST['einsatzverwaltung_einsatzort']);

        // Einsatzleiter validieren
        $einsatzleiter = sanitize_text_field($_POST['einsatzverwaltung_einsatzleiter']);

        // Mannschaftsstärke validieren
        $mannschaftsstaerke = Utilities::sanitizePositiveNumber($_POST['einsatzverwaltung_mannschaft'], 0);

        // Fehlalarm validieren
        $fehlalarm = Utilities::sanitizeCheckbox(array($_POST, 'einsatzverwaltung_fehlalarm'));

        // Metadaten schreiben
        update_post_meta($post_id, 'einsatz_alarmzeit', date_format($alarmzeit, 'Y-m-d H:i'));
        update_post_meta($post_id, 'einsatz_einsatzende', $einsatzende);
        update_post_meta($post_id, 'einsatz_einsatzort', $einsatzort);
        update_post_meta($post_id, 'einsatz_einsatzleiter', $einsatzleiter);
        update_post_meta($post_id, 'einsatz_mannschaft', $mannschaftsstaerke);
        update_post_meta($post_id, 'einsatz_fehlalarm', $fehlalarm);

        if (!empty($update_args)) {
            if (! wp_is_post_revision($post_id)) {
                $update_args['ID'] = $post_id;

                // save_post Filter kurzzeitig deaktivieren, damit keine Dauerschleife entsteht
                remove_action('save_post', array($this, 'savePostdata'));
                wp_update_post($update_args);
                add_action('save_post', array($this, 'savePostdata'));
            }
        }
    }

    /**
     * Bestimmt die Einsatzart eines bestimmten Einsatzes. Ist nötig, weil die Taxonomie
     * 'einsatzart' mehrere Werte speichern kann, aber nur einer genutzt werden soll
     *
     * @param int $postId
     * @return object|bool
     */
    public static function getEinsatzart($postId)
    {
        $einsatzarten = get_the_terms($postId, 'einsatzart');
        if ($einsatzarten && !is_wp_error($einsatzarten) && !empty($einsatzarten)) {
            $keys = array_keys($einsatzarten);
            return $einsatzarten[$keys[0]];
        } else {
            return false;
        }
    }

    /**
     * Gibt die Einsatzart als String zurück, wenn vorhanden auch mit den übergeordneten Einsatzarten
     *
     * @param object $einsatzart
     * @param bool $make_links
     * @param bool $show_archive_links
     *
     * @return string
     */
    public static function getEinsatzartString($einsatzart, $make_links, $show_archive_links)
    {
        $str = '';
        do {
            if (!empty($str)) {
                $str = ' > '.$str;
                $einsatzart = get_term($einsatzart->parent, 'einsatzart');
            }

            if ($make_links && $show_archive_links) {
                $title = 'Alle Eins&auml;tze vom Typ '. $einsatzart->name . ' anzeigen';
                $url = get_term_link($einsatzart);
                $link = '<a href="'.$url.'" class="fa fa-filter" style="text-decoration:none;" title="'.$title.'"></a>';
                $str = '&nbsp;' . $link . $str;
            }
            $str = $einsatzart->name . $str;
        } while ($einsatzart->parent != 0);
        return $str;
    }

    /**
     * Gibt die Namen aller bisher verwendeten Einsatzleiter zurück
     *
     * @return array
     */
    public static function getEinsatzleiter()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $names = array();
        $query = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'einsatz_einsatzleiter' AND meta_value <> ''";
        $results = $wpdb->get_results($query, OBJECT);

        foreach ($results as $result) {
            $names[] = $result->meta_value;
        }
        return $names;
    }

    /**
     * Gibt ein Array mit Jahreszahlen zurück, in denen Einsätze vorliegen
     */
    public static function getJahreMitEinsatz()
    {
        $jahre = array();
        $query = new WP_Query('&post_type=einsatz&post_status=publish&nopaging=true');
        while ($query->have_posts()) {
            $nextPost = $query->next_post();
            $timestamp = strtotime($nextPost->post_date);
            $jahre[date("Y", $timestamp)] = 1;
        }
        return array_keys($jahre);
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
     * Ändert die Einsatznummer eines bestehenden Einsatzes
     *
     * @param int $post_id ID des Einsatzberichts
     * @param string $einsatznummer Einsatznummer
     */
    public function setEinsatznummer($post_id, $einsatznummer)
    {
        if (empty($post_id) || empty($einsatznummer)) {
            return;
        }

        $update_args = array();
        $update_args['post_name'] = $einsatznummer;
        $update_args['ID'] = $post_id;

        // keine Sonderbehandlung beim Speichern
        remove_action('save_post', array($this, 'savePostdata'));
        wp_update_post($update_args);
        add_action('save_post', array($this, 'savePostdata'));
    }

    /**
     * Reparaturen oder Anpassungen der Datenbank nach einem Update
     */
    private function checkDatabaseVersion()
    {
        $evwInstalledVersion = get_site_option(EINSATZVERWALTUNG__DBVERSION_OPTION);

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
                    $berichte = Core::getEinsatzberichte('');

                    remove_action('save_post', array($this, 'savePostdata'));
                    foreach ($berichte as $bericht) {
                        $post_id = $bericht->ID;
                        if (! wp_is_post_revision($post_id)) {
                            $gmtdate = get_gmt_from_date($bericht->post_date);
                            $wpdb->query(
                                $wpdb->prepare(
                                    'UPDATE $wpdb->posts SET post_date_gmt = %s WHERE ID = %d',
                                    $gmtdate,
                                    $post_id
                                )
                            );
                        }
                    }
                    add_action('save_post', array($this, 'savePostdata'));
                    update_site_option(EINSATZVERWALTUNG__DBVERSION_OPTION, 1);
                    // no break
                case 1:
                    update_option('einsatzvw_cap_roles_administrator', 1);
                    $role_obj = get_role('administrator');
                    foreach (Core::getCapabilities() as $cap) {
                        $role_obj->add_cap($cap);
                    }
                    update_site_option(EINSATZVERWALTUNG__DBVERSION_OPTION, 2);
                    // no break
                case 2:
                    delete_option('einsatzvw_show_links_in_excerpt');
                    update_site_option(EINSATZVERWALTUNG__DBVERSION_OPTION, 3);
                    // no break
            }
        }
    }
}

new Core();
