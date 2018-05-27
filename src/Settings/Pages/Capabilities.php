<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

/**
 * Capabilities settings page
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class Capabilities extends SubPage
{
    public function __construct()
    {
        parent::__construct('capabilities', 'Berechtigungen');
    }

    public function addSettingsFields()
    {
        // TODO: Implement addSettingsFields() method.
    }

    public function addSettingsSections()
    {
        add_settings_section(
            'einsatzvw_settings_caps',
            '',
            function () {
                echo '<p>Hier kann festgelegt werden, welche Benutzer die Einsatzberichte verwalten k&ouml;nnen.</p>';
            },
            $this->settingsApiPage
        );
    }

    public function registerSettings()
    {
        // TODO: Implement registerSettings() method.
    }
}
