<?php
namespace abrain\Einsatzverwaltung\Settings\Pages;

use abrain\Einsatzverwaltung\ReportNumberController;
use abrain\Einsatzverwaltung\Utilities;

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
        add_settings_field(
            'einsatzvw_einsatznummer_auto',
            'Einsatznummern automatisch verwalten',
            array($this, 'echoFieldAuto'),
            $this->settingsApiPage,
            'einsatzvw_settings_numbers'
        );
        add_settings_field(
            'einsatzvw_einsatznummer_stellen',
            'Format der Einsatznummer',
            array($this, 'echoFieldFormat'),
            $this->settingsApiPage,
            'einsatzvw_settings_numbers'
        );
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

    public function echoFieldAuto()
    {
        $this->echoSettingsCheckbox(
            'einsatzverwaltung_incidentnumbers_auto',
            'Einsatznummern automatisch verwalten'
        );
        echo '<p class="description">Ist diese Option aktiv, kann die Einsatznummer nicht mehr manuell geändert werden. Sie wird automatisch gem&auml;&szlig; den nachfolgenden Regeln generiert und aktualisiert.</p>';
    }

    /**
     *
     */
    public function echoFieldFormat()
    {
        echo '<fieldset>';
        printf(
            'Jahreszahl + jahresbezogene, fortlaufende Nummer mit <input type="text" value="%2$s" size="2" id="%1$s" name="%1$s" /> Stellen',
            'einsatzvw_einsatznummer_stellen',
            ReportNumberController::sanitizeEinsatznummerStellen(get_option('einsatzvw_einsatznummer_stellen'))
        );
        echo '<p class="description">Beispiel f&uuml;r den f&uuml;nften Einsatz in 2014:<br>bei 2 Stellen: 201405<br>bei 4 Stellen: 20140005</p><br>';
        $this->echoSettingsCheckbox('einsatzvw_einsatznummer_lfdvorne', 'Laufende Nummer vor das Jahr stellen');
        
        echo '<p><strong>Hinweis:</strong> Nach einer &Auml;nderung des Formats erhalten die bestehenden Einsatzberichte nur dann automatisch aktualisierte Nummern, wenn die Option <em>Einsatznummern automatisch verwalten</em> aktiviert ist.</p>';
        echo '</fieldset>';
    }

    public function registerSettings()
    {
        register_setting(
            'einsatzvw_settings_numbers',
            'einsatzverwaltung_incidentnumbers_auto',
            array(Utilities::class, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_numbers',
            'einsatzvw_einsatznummer_stellen',
            array(ReportNumberController::class, 'sanitizeEinsatznummerStellen')
        );
        register_setting(
            'einsatzvw_settings_numbers',
            'einsatzvw_einsatznummer_lfdvorne',
            array(Utilities::class, 'sanitizeCheckbox')
        );
    }
}
