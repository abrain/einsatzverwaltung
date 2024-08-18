<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Api\Ajax;
use abrain\Einsatzverwaltung\Jobs\MigrateUnitsJob;
use abrain\Einsatzverwaltung\Shortcodes\Initializer as ShortcodeInitializer;
use abrain\Einsatzverwaltung\Util\Formatter;
use function add_action;
use function add_option;
use function error_log;
use function get_option;
use function plugin_basename;
use function plugin_dir_url;
use function register_activation_hook;
use function register_deactivation_hook;

/**
 * Grundlegende Funktionen
 */
class Core
{
    const VERSION = '1.11.2';
    const DB_VERSION = 80;

    /**
     * Statische Variable, um die aktuelle (einzige!) Instanz dieser Klasse zu halten
     * @var Core
     */
    private static $instance = null;

    public static $pluginBasename;
    public static $pluginUrl;
    public static $scriptUrl;
    public static $styleUrl;

    /**
     * Absolute path to the main plugin file.
     *
     * @var string
     */
    private $pluginFile;

    /**
     * @var Data
     */
    private $data;

    /**
     * @var CustomFieldsRepository
     */
    private $customFieldsRepo;
    
    /**
     * @var Options
     */
    public $options;
    
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
     * @var PermalinkController
     */
    private $permalinkController;

    /**
     * Private Constructor, use ::getInstance() instead.
     */
    private function __construct()
    {
        $this->options = new Options();

        $this->customFieldsRepo = new CustomFieldsRepository();
        $this->typeRegistry = new TypeRegistry($this->customFieldsRepo);

        $this->permalinkController = new PermalinkController();
        $this->formatter = new Formatter($this->options, $this->permalinkController);
    }

    /**
     * Registers action hooks that are essential to load the plugin.
     */
    public function addHooks()
    {
        if (empty($this->pluginFile)) {
            error_log('einsatzverwaltung: Plugin file has not been set via setPluginFile()');
            return;
        }

        register_activation_hook($this->pluginFile, array($this, 'onActivation'));
        register_deactivation_hook($this->pluginFile, array($this, 'onDeactivation'));

        add_action('init', array($this, 'onInit'));
        add_action('widgets_init', array(new Widgets\Initializer($this->formatter), 'registerWidgets'));
    }

    /**
     * Wird beim Aktivieren des Plugins aufgerufen
     */
    public function onActivation()
    {
        add_option('einsatzvw_db_version', self::DB_VERSION);

        // Add default values for some options explicitly
        add_option('einsatzvw_category', '-1');

        $this->maybeUpdate();

        // Add user roles
        (new UserRightsManager())->updateRoles();

        // Posttypen registrieren
        try {
            $this->typeRegistry->registerTypes($this->permalinkController);
        } catch (Exceptions\TypeRegistrationException $e) {
            array_push($this->adminErrorMessages, $e->getMessage());
            return;
        }

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
        $this->maybeUpdate();

        $this->utilities = new Utilities();

        $this->customFieldsRepo->addHooks();
        $this->permalinkController->addHooks();

        $this->data = new Data($this->options);
        $this->data->addHooks();

        $frontend = new Frontend($this->options, $this->formatter);
        $frontend->addHooks();

        new ShortcodeInitializer($this->data, $this->formatter, $this->permalinkController);

        $numberController = new ReportNumberController($this->data);
        $numberController->addHooks();

        if (is_admin()) {
            add_action('admin_notices', array($this, 'onAdminNotices'));
            new Admin\Initializer($this->data, $this->options, $this->utilities, $this->permalinkController);
            (new Ajax())->addHooks();
        }

        $userRightsManager = new UserRightsManager();
        $userRightsManager->maybeUpdateRoles();

        try {
            $this->typeRegistry->registerTypes($this->permalinkController);
        } catch (Exceptions\TypeRegistrationException $e) {
            array_push($this->adminErrorMessages, $e->getMessage());
            return;
        }

        add_action('einsatzverwaltung_migrate_units', array(new MigrateUnitsJob(), 'run'));

        if ($this->options->isFlushRewriteRules()) {
            flush_rewrite_rules();
            $this->options->setFlushRewriteRules(false);
        }

        $numberController->maybeReformatIncidentNumbers();
    }

    public function onAdminNotices()
    {
        if (empty($this->adminErrorMessages)) {
            return;
        }
        
        $pluginData = get_plugin_data($this->pluginFile);
        foreach ($this->adminErrorMessages as $errorMessage) {
            $message = sprintf('Plugin %s: %s', $pluginData['Name'], $errorMessage);
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr('notice notice-error'), esc_html($message));
        }
    }

    private function maybeUpdate()
    {
        $currentDbVersion = get_option('einsatzvw_db_version');
        if (!empty($currentDbVersion) && $currentDbVersion >= self::DB_VERSION) {
            return;
        }

        $update = new Update();
        $updateResult = $update->doUpdate($currentDbVersion, self::DB_VERSION);
        if (is_wp_error($updateResult)) {
            error_log("Das Datenbank-Upgrade wurde mit folgendem Fehler beendet: {$updateResult->get_error_message()}");
        }
    }

    /**
     * @return Data
     */
    public function getData(): Data
    {
        return $this->data;
    }

    /**
     * Falls die einzige Instanz noch nicht existiert, erstelle sie
     * Gebe die einzige Instanz dann zurück
     *
     * @return   Core
     */
    public static function getInstance(): Core
    {
        if (null === self::$instance) {
            self::$instance = new Core();
        }
        return self::$instance;
    }

    /**
     * Intializes basic path variables crucial to plugin functionality
     *
     * @param string $fileName
     *
     * @return $this
     */
    public function setPluginFile(string $fileName): Core
    {
        $this->pluginFile = $fileName;

        // Initialize some basic paths and URLs
        self::$pluginBasename = plugin_basename($this->pluginFile);
        self::$pluginUrl = plugin_dir_url($this->pluginFile);
        self::$scriptUrl = self::$pluginUrl . 'js/';
        self::$styleUrl = self::$pluginUrl . 'css/';

        return $this;
    }
}
