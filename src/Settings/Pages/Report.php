<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

/**
 * Report settings page
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class Report extends SubPage
{
    public function __construct()
    {
        parent::__construct('report', 'Einsatzberichte');
    }

    public function addSettingsFields()
    {
        // TODO: Implement addSettingsFields() method.
    }

    public function addSettingsSections()
    {
        add_settings_section(
            'einsatzvw_settings_einsatzberichte',
            '',
            function () {
                echo '<p>Mit diesen Einstellungen kann das Aussehen der einzelnen Einsatzberichte beeinflusst werden.</p>';
            },
            $this->settingsApiPage
        );
        add_settings_section(
            'einsatzvw_settings_reporttemplates',
            'Templates',
            function () {
                echo '<p>Mit den beiden folgenden Templates kann das Aussehen der Einsatzberichte bzw. deren Ausz&uuml;ge individuell angepasst werden. Das ausgef&uuml;llte Template erscheint immer dort, wo normal der Beitragstext stehen w&uuml;rde. Wie die Templates funktionieren ist in der <a href="https://einsatzverwaltung.abrain.de/dokumentation/templates/">Dokumentation</a> beschrieben.</p>';
            },
            $this->settingsApiPage
        );
    }

    public function registerSettings()
    {
        // TODO: Implement registerSettings() method.
    }
}
