<?php
namespace abrain\Einsatzverwaltung;

require_once dirname(__FILE__) . '/einsatzverwaltung-admin.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-data.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-utilities.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-frontend.php';
require_once dirname(__FILE__) . '/Model/IncidentReport.php';
require_once dirname(__FILE__) . '/Util/Formatter.php';
require_once dirname(__FILE__) . '/Widgets/RecentIncidents.php';
require_once dirname(__FILE__) . '/Widgets/RecentIncidentsFormatted.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-options.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-shortcodes.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-settings.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-tools.php';
require_once dirname(__FILE__) . '/Import/Tool.php';
require_once dirname(__FILE__) . '/einsatzverwaltung-taxonomies.php';

use abrain\Einsatzverwaltung\Import\Tool as ImportTool;
use abrain\Einsatzverwaltung\Util\Formatter;
use abrain\Einsatzverwaltung\Widgets\RecentIncidents;
use abrain\Einsatzverwaltung\Widgets\RecentIncidentsFormatted;
use WP_Query;

/**
 * Grundlegende Funktionen
 */
class Core
{
    const VERSION = '1.1.2';
    const DB_VERSION = 5;

    public $pluginFile;
    public $pluginBasename;
    public $pluginDir;
    public $pluginUrl;
    public $scriptUrl;
    public $styleUrl;

    private $args_einsatz = array(
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
            'not_found_in_trash' => 'Keine Einsatzberichte im Papierkorb gefunden',
            'filter_items_list' => 'Liste der Einsatzberichte filtern',
            'items_list_navigation' => 'Navigation der Liste der Einsatzberichte',
            'items_list' => 'Liste der Einsatzberichte',
        ),
        'public' => true,
        'has_archive' => true,
        'rewrite' => array(
            'feeds' => true
        ),
        'supports' => array('title', 'editor', 'thumbnail', 'publicize', 'author', 'revisions'),
        'show_in_nav_menus' => false,
        'capability_type' => array('einsatzbericht', 'einsatzberichte'),
        'map_meta_cap' => true,
        'capabilities' => array(
            'edit_post' => 'edit_einsatzbericht',
            'read_post' => 'read_einsatzbericht',
            'delete_post' => 'delete_einsatzbericht',
            'edit_posts' => 'edit_einsatzberichte',
            'edit_others_posts' => 'edit_others_einsatzberichte',
            'publish_posts' => 'publish_einsatzberichte',
            'read_private_posts' => 'read_private_einsatzberichte',
            'read' => 'read',
            'delete_posts' => 'delete_einsatzberichte',
            'delete_private_posts' => 'delete_private_einsatzberichte',
            'delete_published_posts' => 'delete_published_einsatzberichte',
            'delete_others_posts' => 'delete_others_einsatzberichte',
            'edit_private_posts' => 'edit_private_einsatzberichte',
            'edit_published_posts' => 'edit_published_einsatzberichte'
        ),
        'menu_position' => 5,
        'taxonomies' => array('post_tag'),
        'delete_with_user' => false,
    );

    private $args_einsatzart = array(
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
            'choose_from_most_used' => 'Aus h&auml;ufigen Einsatzarten w&auml;hlen',
            'items_list_navigation' => 'Navigation der Liste der Einsatzarten',
            'items_list' => 'Liste der Einsatzarten',
        ),
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

    private $args_fahrzeug = array(
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
            'parent_item' => '&Uuml;bergeordnete Einheit',
            'parent_item_colon' => '&Uuml;bergeordnete Einheit:',
            'items_list_navigation' => 'Navigation der Fahrzeugliste',
            'items_list' => 'Fahrzeugliste',
        ),
        'public' => true,
        'show_in_nav_menus' => false,
        'hierarchical' => true,
        'capabilities' => array (
            'manage_terms' => 'edit_einsatzberichte',
            'edit_terms' => 'edit_einsatzberichte',
            'delete_terms' => 'edit_einsatzberichte',
            'assign_terms' => 'edit_einsatzberichte'
        )
    );

    private $argsExteinsatzmittel = array(
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
            'choose_from_most_used' => 'Aus h&auml;ufig eingesetzten externen Einsatzmitteln w&auml;hlen',
            'items_list_navigation' => 'Navigation der Liste der externen Einsatzmittel',
            'items_list' => 'Liste der externen Einsatzmittel',
        ),
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

    private $args_alarmierungsart = array(
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
            'choose_from_most_used' => 'Aus h&auml;ufigen Alarmierungsarten w&auml;hlen',
            'items_list_navigation' => 'Navigation der Liste der Alarmierungsarten',
            'items_list' => 'Liste der Alarmierungsarten',
        ),
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
     * @var Data
     */
    private $data;
    
    /**
     * @var Options
     */
    private $options;
    
    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pluginFile = einsatzverwaltung_plugin_file();
        $this->pluginBasename = plugin_basename($this->pluginFile);
        $this->pluginDir = plugin_dir_path($this->pluginFile);
        $this->pluginUrl = plugin_dir_url($this->pluginFile);
        $this->scriptUrl = $this->pluginUrl . 'js/';
        $this->styleUrl = $this->pluginUrl . 'css/';

        $this->utilities = new Utilities($this);
        $this->options = new Options($this->utilities);
        $this->utilities->setDependencies($this->options);

        new Admin($this, $this->utilities);
        $this->data = new Data($this, $this->utilities);
        $frontend = new Frontend($this, $this->options, $this->utilities);
        new Settings($this, $this->options, $this->utilities);
        new Shortcodes($frontend);
        new Taxonomies($this->utilities);

        // Tools
        new ToolEinsatznummernReparieren($this, $this->data, $this->options);
        new ImportTool($this, $this->utilities);

        // Widgets
        RecentIncidents::setDependencies($this->options, $this->utilities);
        $formatter = new Formatter($this->options, $this->utilities);
        RecentIncidentsFormatted::setDependencies($formatter, $this->utilities);

        $this->addHooks();
    }

    private function addHooks()
    {
        add_action('init', array($this, 'onInit'));
        add_action('plugins_loaded', array($this, 'onPluginsLoaded'));
        add_action('save_post', array($this->data, 'savePostdata'));
        register_activation_hook($this->pluginFile, array($this, 'onActivation'));
        register_deactivation_hook($this->pluginFile, array($this, 'onDeactivation'));
        add_filter('posts_where', array($this, 'postsWhere'), 10, 2);
        add_action('widgets_init', array($this, 'registerWidgets'));
    }

    /**
     * Wird beim Aktivieren des Plugins aufgerufen
     */
    public function onActivation()
    {
        update_option('einsatzvw_version', self::VERSION);
        add_option('einsatzvw_db_version', self::DB_VERSION);

        $this->maybeUpdate();

        // Posttypen registrieren
        $this->registerTypes();
        $this->addRewriteRules();

        // Permalinks aktualisieren
        flush_rewrite_rules();

        // Rechte für Administratoren setzen
        $role_obj = get_role('administrator');
        foreach ($this->getCapabilities() as $cap) {
            $role_obj->add_cap($cap, true);
        }
    }

    /**
     * Wird beim Deaktivieren des Plugins aufgerufen
     */
    public function onDeactivation()
    {
        // Permalinks aktualisieren (derzeit ohne Effekt, siehe https://core.trac.wordpress.org/ticket/29118)
        flush_rewrite_rules();
    }

    /**
     * Plugin initialisieren
     */
    public function onInit()
    {
        $this->registerTypes();
        $this->addRewriteRules();
        if ($this->options->isFlushRewriteRules()) {
            flush_rewrite_rules();
            $this->options->setFlushRewriteRules(false);
        }
    }

    public function onPluginsLoaded()
    {
        $this->maybeUpdate();
    }

    /**
     * Erzeugt den neuen Beitragstyp Einsatzbericht und die zugehörigen Taxonomien
     */
    private function registerTypes()
    {
        // Anpassungen der Parameter
        if ($this->utilities->isMinWPVersion("3.9")) {
            $this->args_einsatz['menu_icon'] = 'dashicons-media-document';
        }
        $this->args_einsatz['rewrite']['slug'] = $this->options->getRewriteSlug();

        register_post_type('einsatz', $this->args_einsatz);
        register_taxonomy('einsatzart', 'einsatz', $this->args_einsatzart);
        register_taxonomy('fahrzeug', 'einsatz', $this->args_fahrzeug);
        register_taxonomy('exteinsatzmittel', 'einsatz', $this->argsExteinsatzmittel);
        register_taxonomy('alarmierungsart', 'einsatz', $this->args_alarmierungsart);
    }

    private function addRewriteRules()
    {
        $base = $this->options->getRewriteSlug();
        add_rewrite_rule(
            $base . '/(\d{4})/page/(\d{1,})/?$',
            'index.php?post_type=einsatz&year=$matches[1]&paged=$matches[2]',
            'top'
        );
        add_rewrite_rule($base . '/(\d{4})/?$', 'index.php?post_type=einsatz&year=$matches[1]', 'top');
    }

    public function registerWidgets()
    {
        register_widget('abrain\Einsatzverwaltung\Widgets\RecentIncidents');
        register_widget('abrain\Einsatzverwaltung\Widgets\RecentIncidentsFormatted');
    }

    /**
     * Modifiziert die WHERE-Klausel bei bestimmten Datenbankabfragen
     *
     * @since 1.0.0
     *
     * @param string $where Die original WHERE-Klausel
     * @param WP_Query $wpq Die verwendete WP-Query-Instanz
     *
     * @return string Die zu verwendende WHERE-Klausel
     */
    public function postsWhere($where, $wpq)
    {
        if ($wpq->is_category && $wpq->get_queried_object_id() === $this->options->getEinsatzberichteCategory()) {
            // Einsatzberichte in die eingestellte Kategorie einblenden
            global $wpdb;
            return $where . " OR {$wpdb->posts}.post_type = 'einsatz' AND ({$wpdb->posts}.post_status = 'publish' OR {$wpdb->posts}.post_status = 'private')";
        }
        return $where;
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
    public function getNextEinsatznummer($jahr, $minuseins = false)
    {
        if (empty($jahr) || !is_numeric($jahr)) {
            $jahr = date('Y');
        }
        $query = new WP_Query(array(
            'year' =>  $jahr,
            'post_type' => 'einsatz',
            'post_status' => array('publish', 'private'),
            'nopaging' => true
        ));
        return $this->formatEinsatznummer($jahr, $query->found_posts + ($minuseins ? 0 : 1));
    }

    /**
     * Formatiert die Einsatznummer
     *
     * @param string $jahr Jahreszahl
     * @param int $nummer Laufende Nummer des Einsatzes im angegebenen Jahr
     *
     * @return string Formatierte Einsatznummer
     */
    public function formatEinsatznummer($jahr, $nummer)
    {
        $stellen = $this->options->getEinsatznummerStellen();
        $lfdvorne = $this->options->isEinsatznummerLfdVorne();
        if ($lfdvorne) {
            return str_pad($nummer, $stellen, "0", STR_PAD_LEFT).$jahr;
        } else {
            return $jahr.str_pad($nummer, $stellen, "0", STR_PAD_LEFT);
        }
    }

    /**
     * Gibt die möglichen Spalten für die Einsatzübersicht zurück
     *
     * @return array
     */
    public function getListColumns()
    {
        return array(
            'number' => array(
                'name' => 'Nummer',
                'nowrap' => true
            ),
            'date' => array(
                'name' => 'Datum',
                'nowrap' => true
            ),
            'time' => array(
                'name' => 'Zeit',
                'nowrap' => true
            ),
            'datetime' => array(
                'name' => 'Datum',
                'longName' => 'Datum + Zeit',
                'nowrap' => true
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
                'name' => 'Dauer',
                'nowrap' => true
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
    public function getExcerptTypes()
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
    public function getCapabilities()
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

    private function maybeUpdate()
    {
        $currentDbVersion = get_option('einsatzvw_db_version', self::DB_VERSION);
        if ($currentDbVersion >= self::DB_VERSION) {
            return;
        }

        require_once(__DIR__ . '/einsatzverwaltung-update.php');
        $update = new Update($this);
        $update->doUpdate($currentDbVersion, self::DB_VERSION);
    }
}

new Core();
