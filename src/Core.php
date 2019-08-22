<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Shortcodes\Initializer as ShortcodeInitializer;
use abrain\Einsatzverwaltung\Util\Formatter;

/**
 * Grundlegende Funktionen
 */
class Core
{
    const VERSION = '1.6.3';
    const DB_VERSION = 41;

   /**
    * Statische Variable, um die aktuelle (einzige!) Instanz dieser Klasse zu halten
    * @var Core
    */
    private static $instance = null;

    public static $pluginFile;
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
     * Constructor
     */
    private function __construct()
    {
        $this->utilities = new Utilities();
        $this->options = new Options();

        $this->typeRegistry = new TypeRegistry();
        $this->permalinkController = new PermalinkController();

        $this->formatter = new Formatter($this->options, $this->permalinkController); // TODO In Singleton umwandeln

        $this->data = new Data($this->options);
        add_action('save_post_einsatz', array($this->data, 'savePostdata'), 10, 2);
        add_action('private_einsatz', array($this->data, 'onPublish'), 10, 2);
        add_action('publish_einsatz', array($this->data, 'onPublish'), 10, 2);
        add_action('trash_einsatz', array($this->data, 'onTrash'), 10, 2);
        add_action('transition_post_status', array($this->data, 'onTransitionPostStatus'), 10, 3);

        new Frontend($this->options, $this->formatter);
        new ShortcodeInitializer($this->data, $this->formatter, $this->permalinkController);

        $numberController = new ReportNumberController();
        add_action('updated_postmeta', array($numberController, 'onPostMetaChanged'), 10, 4);
        add_action('added_post_meta', array($numberController, 'onPostMetaChanged'), 10, 4);
        add_action('updated_option', array($numberController, 'maybeAutoIncidentNumbersChanged'), 10, 3);
        add_action('updated_option', array($numberController, 'maybeIncidentNumberFormatChanged'), 10, 3);
        add_action('add_option_einsatzverwaltung_incidentnumbers_auto', array($numberController, 'onOptionAdded'), 10, 2);

        new Widgets\Initializer($this->formatter, $this->options);

        if (is_admin()) {
            new Admin\Initializer($this->data, $this->options, $this->utilities, $this->permalinkController);
        }

        $this->addHooks();
    }

    private function addHooks()
    {
        add_action('admin_notices', array($this, 'onAdminNotices'));
        add_action('init', array($this, 'onInit'));
        add_action('plugins_loaded', array($this, 'onPluginsLoaded'));
        register_activation_hook(self::$pluginFile, array($this, 'onActivation'));
        register_deactivation_hook(self::$pluginFile, array($this, 'onDeactivation'));

        $userRightsManager = new UserRightsManager();
        add_filter('user_has_cap', array($userRightsManager, 'userHasCap'), 10, 4);

        add_filter('option_einsatz_permalink', array('abrain\Einsatzverwaltung\PermalinkController', 'sanitizePermalink'));
        add_action('parse_query', array($this->permalinkController, 'einsatznummerMetaQuery'));
        add_filter('post_type_link', array($this->permalinkController, 'filterPostTypeLink'), 10, 4);
        add_filter('request', array($this->permalinkController, 'filterRequest'));
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
        try {
            $this->typeRegistry->registerTypes($this->permalinkController);
        } catch (Exceptions\TypeRegistrationException $e) {
            array_push($this->adminErrorMessages, $e->getMessage());
            return;
        }

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
    public function getData()
    {
        return $this->data;
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
