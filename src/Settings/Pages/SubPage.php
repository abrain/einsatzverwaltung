<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

/**
 * Base class for a sub page of the plugin settings
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
abstract class SubPage
{
    public $identifier;
    public $title;

    /**
     * SubPage constructor.
     * @param $identifier
     * @param $title
     */
    public function __construct($identifier, $title)
    {
        $this->identifier = $identifier;
        $this->title = $title;
    }

    abstract public function addSettingsFields();
    abstract public function addSettingsSections();
    abstract public function registerSettings();
}
