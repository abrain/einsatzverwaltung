<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

/**
 * Numbers settings page
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class Numbers extends SubPage
{
    public function __construct()
    {
        parent::__construct('numbers', 'Einsatznummern');
    }

    public function addSettingsFields()
    {
        // TODO: Implement addSettingsFields() method.
    }

    public function addSettingsSections()
    {
        add_settings_section(
            'einsatzvw_settings_numbers',
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
