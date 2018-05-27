<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

use abrain\Einsatzverwaltung\Settings\MainPage;

/**
 * Base class for a sub page of the plugin settings
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
abstract class SubPage
{
    public $identifier;
    public $settingsApiPage;
    public $title;

    /**
     * SubPage constructor.
     * @param $identifier
     * @param $title
     */
    public function __construct($identifier, $title)
    {
        $this->identifier = $identifier;
        $this->settingsApiPage = MainPage::EVW_SETTINGS_SLUG . '-' . $identifier;
        $this->title = $title;
    }

    abstract public function addSettingsFields();
    abstract public function addSettingsSections();
    abstract public function registerSettings();
}
