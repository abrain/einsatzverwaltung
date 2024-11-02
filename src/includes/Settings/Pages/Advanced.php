<?php
namespace abrain\Einsatzverwaltung\Settings\Pages;

use abrain\Einsatzverwaltung\PermalinkController;
use abrain\Einsatzverwaltung\Utilities;
use WP_Post;
use function esc_html;
use function esc_html__;

/**
 * Settings page for advanced stuff
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class Advanced extends SubPage
{
    /**
     * @var array[]
     */
    private $permalinkOptions;

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

        $this->permalinkOptions = [
            PermalinkController::DEFAULT_REPORT_PERMALINK => [
                'label' => __('Title with counter', 'einsatzverwaltung')
            ],
            '%post_id%-%postname_nosuffix%' => [
                'label' => __('ID + title without counter', 'einsatzverwaltung')
            ]
        ];

        add_filter('pre_update_option_einsatzvw_rewrite_slug', array($this, 'maybeRewriteSlugChanged'), 10, 2);
    }

    public function addSettingsFields()
    {
        add_settings_field(
            'einsatzvw_permalinks_base',
            __('Base', 'einsatzverwaltung'),
            array($this, 'echoFieldBase'),
            $this->settingsApiPage,
            'einsatzvw_settings_permalinks'
        );
        add_settings_field(
            'einsatzvw_permalinks_struct',
            __('URL structure for reports', 'einsatzverwaltung'),
            array($this, 'echoFieldUrlStructure'),
            $this->settingsApiPage,
            'einsatzvw_settings_permalinks'
        );
        add_settings_field(
            'einsatzvw_advreport_corefeatures',
            __('Post features', 'einsatzverwaltung'),
            array($this, 'echoFieldCoreFeatures'),
            $this->settingsApiPage,
            'einsatzvw_settings_advreport'
        );
        add_settings_field(
            'einsatzvw_advreport_gutenberg',
            __('Block editor', 'einsatzverwaltung'),
            array($this, 'echoFieldGutenberg'),
            $this->settingsApiPage,
            'einsatzvw_settings_advreport'
        );
        add_settings_field(
            'einsatzvw_compatibility_fontawesome',
            __('Font Awesome', 'einsatzverwaltung'),
            array($this, 'echoFieldFontAwesome'),
            $this->settingsApiPage,
            'einsatzvw_settings_advanced_compatibility'
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
                printf(
                    "<p>%s</p>",
                    esc_html__('Changing the permalink structure breaks existing links to reports and archives. In case you are setting up the plugin for the first time, this is not a problem. If you have been using the plugin for some time, you should redirect the broken URLs to the working ones.', 'einsatzverwaltung')
                );
            },
            $this->settingsApiPage
        );
        add_settings_section(
            'einsatzvw_settings_advreport',
            __('Incident Reports', 'einsatzverwaltung'),
            null,
            $this->settingsApiPage
        );
        add_settings_section(
            'einsatzvw_settings_advanced_compatibility',
            __('Compatibility', 'einsatzverwaltung'),
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
            /* translators: 1: archive, 2: feed */
            __('Base for links to single reports, the %1$s, and the %2$s.', 'einsatzverwaltung'),
            sprintf(
                '<a href="%s">%s</a>',
                get_post_type_archive_link(\abrain\Einsatzverwaltung\Types\Report::getSlug()),
                esc_html__('archive', 'einsatzverwaltung')
            ),
            sprintf(
                '<a href="%s">%s</a>',
                get_post_type_archive_feed_link(\abrain\Einsatzverwaltung\Types\Report::getSlug()),
                esc_html__('feed', 'einsatzverwaltung')
            )
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
        echo '<br>';
        $this->echoSettingsCheckbox(
            'einsatz_support_comments',
            __('Comments', 'default')
        );
        printf(
            '<p class="description">%s</p>',
            __('You can activate these features of Posts also for Incident Reports.', 'einsatzverwaltung')
        );
        echo '</fieldset>';
    }

    public function echoFieldFontAwesome()
    {
        echo '<fieldset>';
        $this->echoSettingsCheckbox(
            'einsatzvw_disable_fontawesome',
            __('Disable Font Awesome', 'einsatzverwaltung')
        );
        printf(
            '<p class="description">%s</p>',
            esc_html__('If the icons are not shown correctly, there may be a collision with another installed version of Font Awesome. You can try and deactivate this plugin\'s version. This will not affect the admin area.', 'einsatzverwaltung')
        );
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
            // translators: 1: sample-incident, 2: sample-incident-2, 3: sample-incident-3
            __('By default, WordPress uses the post name to build the URL. To ensure uniqueness across posts, the post name can have a number appended if there are other posts with the same title (e.g. %1$s, %2$s, %3$s, ...).', 'einsatzverwaltung'),
            esc_html($sampleSlug),
            esc_html("$sampleSlug-2"),
            esc_html("$sampleSlug-3")
        );
        echo '</p></fieldset>';
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
            'einsatz_support_comments',
            array(Utilities::class, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_advanced',
            'einsatz_disable_blockeditor',
            array(Utilities::class, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_advanced',
            'einsatzvw_disable_fontawesome',
            array(Utilities::class, 'sanitizeCheckbox')
        );
    }
}
