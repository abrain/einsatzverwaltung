<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

/**
 * General settings page
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class General extends SubPage
{
    public function __construct()
    {
        parent::__construct('general', 'Allgemein');
    }

    public function addSettingsFields()
    {
        // TODO: Implement addSettingsFields() method.
    }

    public function addSettingsSections()
    {
        add_settings_section(
            'einsatzvw_settings_general',
            '',
            null,
            $this->settingsApiPage
        );
    }

    public function registerSettings()
    {
        // TODO: Implement registerSettings() method.
    }
}
