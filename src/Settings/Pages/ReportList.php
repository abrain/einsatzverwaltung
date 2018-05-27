<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

/**
 * ReportList settings page
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class ReportList extends SubPage
{
    public function __construct()
    {
        parent::__construct('list', 'Einsatzliste');
    }

    public function addSettingsFields()
    {
        // TODO: Implement addSettingsFields() method.
    }

    public function addSettingsSections()
    {
        add_settings_section(
            'einsatzvw_settings_einsatzliste',
            '',
            function () {
                echo '<p>Mit diesen Einstellungen kann das Aussehen der Einsatzlisten beeinflusst werden. Einsatzlisten k&ouml;nnen &uuml;ber den <a href="https://einsatzverwaltung.abrain.de/dokumentation/shortcodes/shortcode-einsatzliste/">Shortcode [einsatzliste]</a> in Seiten und Beitr&auml;ge eingebunden werden.</p>';
            },
            $this->settingsApiPage
        );
    }

    public function registerSettings()
    {
        // TODO: Implement registerSettings() method.
    }
}
