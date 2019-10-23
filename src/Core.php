<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Shortcodes\Initializer as ShortcodeInitializer;
use abrain\Einsatzverwaltung\Util\Formatter;

/**
 * Grundlegende Funktionen
 */
class Core
{
    const VERSION = '1.6.4';
    const DB_VERSION = 50;

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
        // Empty, but private constructor. Use ::getInstance() instead.
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
        $this->maybeUpdate();
        update_option('einsatzvw_version', self::VERSION);

        $this->utilities = new Utilities();
        $options = new Options();

        $customFieldsRepo = new CustomFieldsRepository();
        $this->typeRegistry = new TypeRegistry($customFieldsRepo);

        $this->permalinkController = new PermalinkController();
        add_filter('option_einsatz_permalink', array(PermalinkController::class, 'sanitizePermalink'));
        add_action('parse_query', array($this->permalinkController, 'einsatznummerMetaQuery'));
        add_filter('post_type_link', array($this->permalinkController, 'filterPostTypeLink'), 10, 4);
        add_filter('request', array($this->permalinkController, 'filterRequest'));

        $this->formatter = new Formatter($options, $this->permalinkController);

        $this->data = new Data($options);
        add_action('save_post_einsatz', array($this->data, 'savePostdata'), 10, 2);
        add_action('private_einsatz', array($this->data, 'onPublish'), 10, 2);
        add_action('publish_einsatz', array($this->data, 'onPublish'), 10, 2);
        add_action('trash_einsatz', array($this->data, 'onTrash'), 10, 2);
        add_action('transition_post_status', array($this->data, 'onTransitionPostStatus'), 10, 3);

        new Frontend($options, $this->formatter);
        new ShortcodeInitializer($this->data, $this->formatter, $this->permalinkController);

        $numberController = new ReportNumberController($this->data);
        add_action('updated_postmeta', array($numberController, 'onPostMetaChanged'), 10, 4);
        add_action('added_post_meta', array($numberController, 'onPostMetaChanged'), 10, 4);
        add_action('updated_option', array($numberController, 'maybeAutoIncidentNumbersChanged'), 10, 3);
        add_action('updated_option', array($numberController, 'maybeIncidentNumberFormatChanged'), 10, 3);
        add_action('add_option_einsatzverwaltung_incidentnumbers_auto', array($numberController, 'onOptionAdded'), 10, 2);

        new Widgets\Initializer($this->formatter);

        if (is_admin()) {
            add_action('admin_notices', array($this, 'onAdminNotices'));
            new Admin\Initializer($this->data, $options, $this->utilities, $this->permalinkController, $customFieldsRepo);
        }

        $userRightsManager = new UserRightsManager();
        add_filter('user_has_cap', array($userRightsManager, 'userHasCap'), 10, 4);

        try {
            $this->typeRegistry->registerTypes($this->permalinkController);
        } catch (Exceptions\TypeRegistrationException $e) {
            array_push($this->adminErrorMessages, $e->getMessage());
            return;
        }

        if ($options->isFlushRewriteRules()) {
            flush_rewrite_rules();
            $options->setFlushRewriteRules(false);
        }
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
