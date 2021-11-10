<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Jobs\MigrateUnitsJob;
use abrain\Einsatzverwaltung\Shortcodes\Initializer as ShortcodeInitializer;
use abrain\Einsatzverwaltung\Util\Formatter;
use function add_action;
use function add_option;
use function error_log;
use function get_option;

/**
 * Grundlegende Funktionen
 */
class Core
{
    const VERSION = '1.9.7';
    const DB_VERSION = 71;

    /**
     * Statische Variable, um die aktuelle (einzige!) Instanz dieser Klasse zu halten
     * @var Core
     */
    private static $instance = null;

    public static $pluginBasename;
    public static $pluginDir;
    public static $pluginUrl;
    public static $scriptUrl;
    public static $styleUrl;

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

        $widgetInitializer = new Widgets\Initializer($this->formatter);
        add_action('widgets_init', array($widgetInitializer, 'registerWidgets'));
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
        
        $pluginData = get_plugin_data(einsatzverwaltung_plugin_file());
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
    public static function getInstance(): ?Core
    {
        if (null === self::$instance) {
            self::$instance = new Core();
        }
        return self::$instance;
    }
}
