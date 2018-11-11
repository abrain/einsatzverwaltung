<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

/**
 * Settings page for advanced stuff
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class Advanced extends SubPage
{
    public function __construct()
    {
        parent::__construct('advanced', __('Advanced', 'einsatzverwaltung'));

        add_filter('pre_update_option_einsatzvw_rewrite_slug', array($this, 'maybeRewriteSlugChanged'), 10, 2);
    }

    public function addSettingsFields()
    {
        add_settings_field(
            'einsatzvw_permalinks',
            'Basis',
            array($this, 'echoFieldBase'),
            $this->settingsApiPage,
            'einsatzvw_settings_permalinks'
        );
    }

    public function addSettingsSections()
    {
        add_settings_section(
            'einsatzvw_settings_permalinks',
            __('Permalinks', 'einsatzverwaltung'),
            function () {
                echo '<p>TODO: Hinweis über die Folgen von Änderungen</p>';
            },
            $this->settingsApiPage
        );
    }

    public function echoFieldBase()
    {
        global $wp_rewrite;
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
        if ($wp_rewrite->using_permalinks() === false) {
            echo '</p><p class="description">';
            printf(
                __('Note: This setting has no effect, as WordPress currently uses plain %s', 'einsatzverwaltung'),
                sprintf(
                    '<a href="%s">%s</a>',
                    admin_url('options-permalink.php'),
                    __('permalinks', 'einsatzverwaltung')
                )
            );
        }
        echo '</p></fieldset>';
    }

    /**
     * Prüft, ob sich die Basis für die Links zu Einsatzberichten ändert und veranlasst gegebenenfalls ein Erneuern der
     * Permalinkstruktur
     *
     * @param string $newValue Der neue Wert
     * @param string $oldValue Der alte Wert
     * @return string Der zu speichernde Wert
     */
    public function maybeRewriteSlugChanged($newValue, $oldValue)
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
    }
}
