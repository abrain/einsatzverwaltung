<?php
namespace abrain\Einsatzverwaltung\Settings\Pages;

use abrain\Einsatzverwaltung\PermalinkController;
use abrain\Einsatzverwaltung\Utilities;
use WP_Post;

/**
 * Settings page for advanced stuff
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class Advanced extends SubPage
{
    private $permalinkOptions = array(
        PermalinkController::DEFAULT_REPORT_PERMALINK => array(
            'label' => 'Beitragstitel mit angeh&auml;ngtem Z&auml;hler (Standard)'
        ),
        '%post_id%-%postname_nosuffix%' => array(
            'label' => 'Beitragsnummer und Beitragstitel ohne angeh&auml;ngten Z&auml;hler'
        )
    );
    /**
     * @var PermalinkController
     */
    private $permalinkController;

    /**
     * Advanced Settings page constructor.
     *
     * @param PermalinkController $permalinkController
     */
    public function __construct(PermalinkController $permalinkController)
    {
        parent::__construct('advanced', __('Advanced', 'einsatzverwaltung'));
        $this->permalinkController = $permalinkController;

        add_filter('pre_update_option_einsatzvw_rewrite_slug', array($this, 'maybeRewriteSlugChanged'), 10, 2);
    }

    public function addSettingsFields()
    {
        add_settings_field(
            'einsatzvw_permalinks_base',
            'Basis',
            array($this, 'echoFieldBase'),
            $this->settingsApiPage,
            'einsatzvw_settings_permalinks'
        );
        add_settings_field(
            'einsatzvw_permalinks_struct',
            'URL-Struktur f&uuml;r Einsatzberichte',
            array($this, 'echoFieldUrlStructure'),
            $this->settingsApiPage,
            'einsatzvw_settings_permalinks'
        );
        add_settings_field(
            'einsatzvw_advreport_corefeatures',
            'Beitragsfunktionen',
            array($this, 'echoFieldCoreFeatures'),
            $this->settingsApiPage,
            'einsatzvw_settings_advreport'
        );
        add_settings_field(
            'einsatzvw_advreport_gutenberg',
            'Gutenberg',
            array($this, 'echoFieldGutenberg'),
            $this->settingsApiPage,
            'einsatzvw_settings_advreport'
        );
    }

    public function addSettingsSections()
    {
        add_settings_section(
            'einsatzvw_settings_permalinks',
            __('Permalinks', 'einsatzverwaltung'),
            function () {
                global $wp_rewrite;
                if ($wp_rewrite->using_permalinks() === false) {
                    echo '<p style="">';
                    printf('<strong>%s</strong> ', esc_html(__('Note:', 'einsatzverwaltung')));
                    printf(
                        // Translators: %s: permalinks
                        __('These settings currently have no effect, as WordPress uses plain %s', 'einsatzverwaltung'),
                        sprintf(
                            '<a href="%s">%s</a>',
                            admin_url('options-permalink.php'),
                            __('permalinks', 'einsatzverwaltung')
                        )
                    );
                    echo '</p>';
                }
                echo '<p>Eine &Auml;nderung der Permalinkstruktur hat zur Folge, dass bisherige Links auf Einsatzberichte nicht mehr funktionieren. Dem solltest du als Seitenbetreiber mit Weiterleitungen entgegenwirken.</p>';
            },
            $this->settingsApiPage
        );
        add_settings_section(
            'einsatzvw_settings_advreport',
            __('Incident Reports', 'einsatzverwaltung'),
            null,
            $this->settingsApiPage
        );
    }

    /**
     * @inheritDoc
     */
    public function beforeContent()
    {
        $sampleSlug = _x('sample-incident', 'sample permalink structure', 'einsatzverwaltung');
        $fakePost = new WP_Post((object) array(
            'ID' => 1234,
            'post_name' => "$sampleSlug-3",
            'post_title' => $sampleSlug
        ));
        foreach (array_keys($this->permalinkOptions) as $permalinkStructure) {
            $this->permalinkOptions[$permalinkStructure]['code'] = $this->getSampleUrl($fakePost, $permalinkStructure);
        }
    }

    public function echoFieldBase()
    {
        echo '<fieldset>';
        $this->echoSettingsInput(
            'einsatzvw_rewrite_slug',
            sanitize_title(get_option('einsatzvw_rewrite_slug'), 'einsatzberichte')
        );
        echo '<p class="description">';
        printf(
            'Basis f&uuml;r Links zu Einsatzberichten, zum %s und zum %s.',
            sprintf('<a href="%s">%s</a>', get_post_type_archive_link('einsatz'), 'Archiv'),
            sprintf('<a href="%s">%s</a>', get_post_type_archive_feed_link('einsatz'), 'Feed')
        );
        echo '</p></fieldset>';
    }

    public function echoFieldCoreFeatures()
    {
        echo '<fieldset>';
        $this->echoSettingsCheckbox(
            'einsatz_support_excerpt',
            __('Excerpt', 'einsatzverwaltung')
        );
        echo '<br>';
        $this->echoSettingsCheckbox(
            'einsatz_support_posttag',
            __('Tags', 'einsatzverwaltung')
        );
        echo '<p class="description">Diese Funktionen, die du von Beitr&auml;gen kennst, k&ouml;nnen auch f&uuml;r Einsatzberichte aktiviert werden.</p>';
        echo '</fieldset>';
    }

    public function echoFieldGutenberg()
    {
        echo '<fieldset>';
        $this->echoSettingsCheckbox(
            'einsatz_disable_blockeditor',
            __('Disable block editor for Incident Reports', 'einsatzverwaltung')
        );
        echo '</fieldset>';
    }

    public function echoFieldUrlStructure()
    {
        echo '<fieldset>';
        $this->echoRadioButtons(
            'einsatz_permalink',
            $this->permalinkOptions,
            PermalinkController::DEFAULT_REPORT_PERMALINK
        );
        echo '</fieldset>';

        echo '<p class="description">';
        $sampleSlug = sanitize_title(
            _x('sample-incident', 'sample permalink structure', 'einsatzverwaltung'),
            'sample-incident'
        );
        printf(
            __('By default, WordPress uses the post name to build the URL. To ensure uniqueness across posts, the post name can have a number appended if there are other posts with the same title (e.g. %1$s, %2$s, %3$s, ...).', 'einsatzverwaltung'),
            $sampleSlug,
            "$sampleSlug-2",
            "$sampleSlug-3"
        );
        echo '</p></fieldset>';
    }

    /**
     * @inheritDoc
     */
    public function echoStaticContent()
    {
        echo '<p>Die erweiterten Einstellungen k&ouml;nnen weitreichende Konsequenzen haben und sollten entsprechend nicht leichtfertig ge&auml;ndert werden.</p>';
    }

    /**
     * @param WP_Post $post
     * @param string $permalinkStructure
     *
     * @return string
     */
    private function getSampleUrl(WP_Post $post, $permalinkStructure): string
    {
        $selector = $this->permalinkController->buildSelector($post, $permalinkStructure);
        return $this->permalinkController->getPermalink($selector);
    }

    /**
     * Prüft, ob sich die Basis für die Links zu Einsatzberichten ändert und veranlasst gegebenenfalls ein Erneuern der
     * Permalinkstruktur
     *
     * @param string $newValue Der neue Wert
     * @param string $oldValue Der alte Wert
     * @return string Der zu speichernde Wert
     */
    public function maybeRewriteSlugChanged($newValue, $oldValue): string
    {
        if ($newValue != $oldValue) {
            self::$options->setFlushRewriteRules(true);
        }

        return $newValue;
    }

    public function registerSettings()
    {
        register_setting(
            'einsatzvw_settings_advanced',
            'einsatzvw_rewrite_slug',
            'sanitize_title'
        );
        register_setting(
            'einsatzvw_settings_advanced',
            'einsatz_permalink',
            array(PermalinkController::class, 'sanitizePermalink')
        );
        register_setting(
            'einsatzvw_settings_advanced',
            'einsatz_support_excerpt',
            array(Utilities::class, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_advanced',
            'einsatz_support_posttag',
            array(Utilities::class, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_advanced',
            'einsatz_disable_blockeditor',
            array(Utilities::class, 'sanitizeCheckbox')
        );
    }
}
