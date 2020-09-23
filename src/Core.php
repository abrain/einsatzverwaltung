<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Shortcodes\Initializer as ShortcodeInitializer;
use abrain\Einsatzverwaltung\Types\Unit;
use abrain\Einsatzverwaltung\Util\Formatter;
use function add_action;

/**
 * Grundlegende Funktionen
 */
class Core
{
    const VERSION = '1.7.0';
    const DB_VERSION = 51;

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

        $this->maybeUpdate();

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

        add_filter('option_einsatz_permalink', array(PermalinkController::class, 'sanitizePermalink'));
        add_action('parse_query', array($this->permalinkController, 'einsatznummerMetaQuery'));
        add_filter('post_type_link', array($this->permalinkController, 'filterPostTypeLink'), 10, 4);
        add_filter('request', array($this->permalinkController, 'filterRequest'));

        $this->data = new Data($this->options);
        add_action('save_post_einsatz', array($this->data, 'savePostdata'), 10, 2);
        add_action('private_einsatz', array($this->data, 'onPublish'), 10, 2);
        add_action('publish_einsatz', array($this->data, 'onPublish'), 10, 2);
        add_action('trash_einsatz', array($this->data, 'onTrash'), 10, 2);
        add_action('transition_post_status', array($this->data, 'onTransitionPostStatus'), 10, 3);
        $unitSlug = Unit::getSlug();
        add_action("save_post_$unitSlug", array($this->data, 'saveUnitData'), 10, 2);
        add_action('before_delete_post', array($this->data, 'onBeforeDeletePost'));

        new Frontend($this->options, $this->formatter);
        new ShortcodeInitializer($this->data, $this->formatter, $this->permalinkController);

        $numberController = new ReportNumberController($this->data);
        add_action('updated_postmeta', array($numberController, 'onPostMetaChanged'), 10, 4);
        add_action('added_post_meta', array($numberController, 'onPostMetaChanged'), 10, 4);
        add_action('updated_option', array($numberController, 'maybeAutoIncidentNumbersChanged'), 10, 3);
        add_action('updated_option', array($numberController, 'maybeIncidentNumberFormatChanged'), 10, 3);
        add_action('add_option_einsatzverwaltung_incidentnumbers_auto', array($numberController, 'onOptionAdded'), 10, 2);

        if (is_admin()) {
            add_action('admin_notices', array($this, 'onAdminNotices'));
            new Admin\Initializer($this->data, $this->options, $this->utilities, $this->permalinkController, $this->customFieldsRepo);
        }

        $userRightsManager = new UserRightsManager();
        add_filter('user_has_cap', array($userRightsManager, 'userHasCap'), 10, 4);

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
