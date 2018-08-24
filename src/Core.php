<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Import\Tool as ImportTool;
use abrain\Einsatzverwaltung\Export\Tool as ExportTool;
use abrain\Einsatzverwaltung\Settings\MainPage;
use abrain\Einsatzverwaltung\Util\Formatter;
use abrain\Einsatzverwaltung\Widgets\RecentIncidents;
use abrain\Einsatzverwaltung\Widgets\RecentIncidentsFormatted;

/**
 * Grundlegende Funktionen
 */
class Core
{
    const VERSION = '1.4.2';
    const DB_VERSION = 30;

   /**
    * Statische Variable, um die aktuelle (einzige!) Instanz dieser Klasse zu halten
    * @var Core
    */
    private static $instance = null;

    public $pluginFile;
    public $pluginBasename;
    public $pluginDir;
    public $pluginUrl;
    public $scriptUrl;
    public $styleUrl;

    /**
     * @var Admin
     */
    private $admin;

    /**
     * @var Data
     */
    private $data;

    /**
     * @var Frontend
     */
    private $frontend;
    
    /**
     * @var Options
     */
    public $options;
    
    /**
     * @var ImportTool
     */
    private $importTool;
    
    /**
     * @var ExportTool
     */
    private $exportTool;
    
    /**
     * @var TasksPage
     */
    private $tasksPage;
    
    /**
     * @var Utilities
     */
    public $utilities;

    /**
     * @var Formatter
     */
    public $formatter;

    /**
     * @var TypeRegistry
     */
    private $typeRegistry;

    /**
     * @var array
     */
    private $adminErrorMessages = array();

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->pluginFile = einsatzverwaltung_plugin_file();
        $this->pluginBasename = plugin_basename($this->pluginFile);
        $this->pluginDir = plugin_dir_path($this->pluginFile);
        $this->pluginUrl = plugin_dir_url($this->pluginFile);
        $this->scriptUrl = $this->pluginUrl . 'js/';
        $this->styleUrl = $this->pluginUrl . 'css/';

        $this->utilities = new Utilities($this);
        $this->options = new Options($this->utilities);
        $this->utilities->setDependencies($this->options); // FIXME Yay, zirkuläre Abhängigkeiten!

        $this->formatter = new Formatter($this->options, $this->utilities); // TODO In Singleton umwandeln

        $this->data = new Data($this, $this->utilities, $this->options);
        $this->frontend = new Frontend($this, $this->options, $this->utilities, $this->formatter);
        new Shortcodes($this->utilities, $this, $this->options, $this->formatter);

        $this->typeRegistry = new TypeRegistry($this->data);

        if (is_admin()) {
            $this->loadClassesForAdmin();
        }

        $this->addHooks();
    }

    private function loadClassesForAdmin()
    {
        $this->admin = new Admin($this);
        add_action('add_meta_boxes_einsatz', array($this->admin, 'addMetaBoxes'));
        add_action('admin_menu', array($this->admin, 'adjustTaxonomies'));
        add_action('admin_notices', array($this->admin, 'displayAdminNotices'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueueEditScripts'));
        add_filter('manage_edit-einsatz_columns', array($this->admin, 'filterColumnsEinsatz'));
        add_action('manage_einsatz_posts_custom_column', array($this->admin, 'filterColumnContentEinsatz'), 10, 2);
        add_action('dashboard_glance_items', array($this->admin, 'addEinsatzberichteToDashboard')); // since WP 3.8
        add_filter('plugin_row_meta', array($this->admin, 'pluginMetaLinks'), 10, 2);
        add_filter('plugin_action_links_' . $this->pluginBasename, array($this->admin,'addActionLinks'));

        $this->registerSettings();

        $this->importTool = new ImportTool($this->utilities, $this->options, $this->data);
        add_action('admin_menu', array($this->importTool, 'addToolToMenu'));

        $this->exportTool = new ExportTool();
        add_action('admin_menu', array($this->exportTool, 'addToolToMenu'));
        add_action('init', array($this->exportTool, 'startExport'), 20); // 20, damit alles andere initialisiert ist
        add_action('admin_enqueue_scripts', array($this->exportTool, 'enqueueAdminScripts'));

        $this->tasksPage = new TasksPage($this->utilities, $this->data);
        add_action('admin_menu', array($this->tasksPage, 'registerPage'));
        add_action('admin_menu', array($this->tasksPage, 'hidePage'), 999);
    }

    private function addHooks()
    {
        add_action('admin_notices', array($this, 'onAdminNotices'));
        add_action('init', array($this, 'onInit'));
        add_action('plugins_loaded', array($this, 'onPluginsLoaded'));
        register_activation_hook($this->pluginFile, array($this, 'onActivation'));
        register_deactivation_hook($this->pluginFile, array($this, 'onDeactivation'));
        add_action('widgets_init', array($this, 'registerWidgets'));

        $userRightsManager = new UserRightsManager();
        add_filter('user_has_cap', array($userRightsManager, 'userHasCap'), 10, 4);

        add_action('parse_query', array($this, 'einsatznummerMetaQuery'));
    }

    /**
     * Wird beim Aktivieren des Plugins aufgerufen
     */
    public function onActivation()
    {
        add_option('einsatzvw_db_version', self::DB_VERSION);

        $this->maybeUpdate();
        update_option('einsatzvw_version', self::VERSION);

        // Posttypen registrieren
        try {
            $this->typeRegistry->registerTypes();
        } catch (Exceptions\TypeRegistrationException $e) {
            array_push($this->adminErrorMessages, $e->getMessage());
            return;
        }
        $this->addRewriteRules();

        // Permalinks aktualisieren
        flush_rewrite_rules();
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
        try {
            $this->typeRegistry->registerTypes();
        } catch (Exceptions\TypeRegistrationException $e) {
            array_push($this->adminErrorMessages, $e->getMessage());
            return;
        }
        $this->addRewriteRules();
        if ($this->options->isFlushRewriteRules()) {
            flush_rewrite_rules();
            $this->options->setFlushRewriteRules(false);
        }
    }

    public function onPluginsLoaded()
    {
        $this->maybeUpdate();
        update_option('einsatzvw_version', self::VERSION);
    }

    public function onAdminNotices()
    {
        if (empty($this->adminErrorMessages)) {
            return;
        }
        
        $pluginData = get_plugin_data(einsatzverwaltung_plugin_file());
        foreach ($this->adminErrorMessages as $errorMessage) {
            $message = sprintf('Plugin %s: %s', $pluginData['Name'], $errorMessage);
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr('notice notice-error'), esc_html($message));
        }
    }

    private function registerSettings()
    {
        $mainPage = new MainPage($this->options);
        add_action('admin_menu', array($mainPage, 'addToSettingsMenu'));
        add_action('admin_init', array($mainPage, 'registerSettings'));
    }

    private function addRewriteRules()
    {
        global $wp_rewrite;
        if ($wp_rewrite->using_permalinks()) {
            $base = ltrim($wp_rewrite->front, '/') . $this->options->getRewriteSlug();
            add_rewrite_rule(
                $base . '/(\d{4})/page/(\d{1,})/?$',
                'index.php?post_type=einsatz&year=$matches[1]&paged=$matches[2]',
                'top'
            );
            add_rewrite_rule($base . '/(\d{4})/?$', 'index.php?post_type=einsatz&year=$matches[1]', 'top');
        }

        add_rewrite_tag('%einsatznummer%', '([^&]+)');
    }

    /**
     * @param \WP_Query $query
     */
    public function einsatznummerMetaQuery($query)
    {
        $enr = $query->get('einsatznummer');
        if (!empty($enr)) {
            $query->set('post_type', 'einsatz');
            $query->set('meta_key', 'einsatz_incidentNumber');
            $query->set('meta_value', $enr);
        }
    }

    public function registerWidgets()
    {
        register_widget(new RecentIncidents($this->options, $this->utilities, $this->formatter));
        register_widget(new RecentIncidentsFormatted($this->formatter, $this->utilities));
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

        return $this->formatEinsatznummer($jahr, $this->data->getNumberOfIncidentReports($jahr) + ($minuseins ? 0 : 1));
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
     * Gibt den Link zu einem bestimmten Jahresarchiv zurück, berücksichtigt dabei die Permalink-Einstellungen
     *
     * @param string $year
     *
     * @return string
     */
    public function getYearArchiveLink($year)
    {
        global $wp_rewrite;
        $link = get_post_type_archive_link('einsatz');
        $link = ($wp_rewrite->using_permalinks() ? trailingslashit($link) : $link . '&year=') . $year;
        return user_trailingslashit($link);
    }

    private function maybeUpdate()
    {
        $currentDbVersion = get_option('einsatzvw_db_version');
        if (!empty($currentDbVersion) && $currentDbVersion >= self::DB_VERSION) {
            return;
        }

        $update = $this->getUpdater();
        $updateResult = $update->doUpdate($currentDbVersion, self::DB_VERSION);
        if (is_wp_error($updateResult)) {
            error_log("Das Datenbank-Upgrade wurde mit folgendem Fehler beendet: {$updateResult->get_error_message()}");
        }
    }

    /**
     * @return Update
     */
    public function getUpdater()
    {
        return new Update($this, $this->options, $this->utilities, $this->data);
    }

    /**
     * @return Admin
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * @return Data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return Frontend
     */
    public function getFrontend()
    {
        return $this->frontend;
    }

    /**
     * @return ImportTool
     */
    public function getImportTool()
    {
        return $this->importTool;
    }

    /**
     * @return ExportTool
     */
    public function getExportTool()
    {
        return $this->exportTool;
    }

    /**
     * @return TasksPage
     */
    public function getTasksPage()
    {
        return $this->tasksPage;
    }

    /**
     * Falls die einzige Instanz noch nicht existiert, erstelle sie
     * Gebe die einzige Instanz dann zurück
     *
     * @return   Core
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new Core();
        }
        return self::$instance;
    }
}
