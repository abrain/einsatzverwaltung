<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

use abrain\Einsatzverwaltung\Frontend\ReportListSettings;
use abrain\Einsatzverwaltung\Utilities;

/**
 * ReportList settings page
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class ReportList extends SubPage
{
    /**
     * @var ReportListSettings
     */
    private $reportListSettings;

    public function __construct()
    {
        parent::__construct('list', 'Einsatzliste');

        $this->reportListSettings = new ReportListSettings();
    }

    public function addSettingsFields()
    {
        add_settings_field(
            'einsatzvw_settings_columns',
            'Spalten der Einsatzliste',
            array($this, 'echoFieldColumns'),
            $this->settingsApiPage,
            'einsatzvw_settings_einsatzliste'
        );
        add_settings_field(
            'einsatzvw_settings_column_settings',
            'Einstellungen zu einzelnen Spalten',
            array($this, 'echoFieldColumnSettings'),
            $this->settingsApiPage,
            'einsatzvw_settings_einsatzliste'
        );
        add_settings_field(
            'einsatzvw_settings_zebralist',
            'Zebrastreifen',
            array($this, 'echoFieldZebra'),
            $this->settingsApiPage,
            'einsatzvw_settings_einsatzliste'
        );
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

    /**
     *
     */
    public function echoFieldColumns()
    {
        $columns = \abrain\Einsatzverwaltung\Frontend\ReportList::getListColumns();
        $enabledColumns = self::$options->getEinsatzlisteEnabledColumns();

        echo '<table id="columns-available"><tr><td style="width: 250px;">';
        echo '<span class="evw-area-title">Verf&uuml;gbare Spalten</span>';
        echo '<p class="description">Spaltennamen in unteres Feld ziehen, um sie auf der Seite anzuzeigen</p>';
        echo '</td><td class="columns"><ul>';
        foreach ($columns as $colId => $colInfo) {
            if (in_array($colId, $enabledColumns)) {
                continue;
            }
            $name = array_key_exists('longName', $colInfo) ? $colInfo['longName'] : $colInfo['name'];
            echo '<li id="' . $colId . '" class="evw-column"><span>' . $name . '</span></li>';
        }
        echo '</ul></td></tr></table>';

        echo '<table id="columns-enabled"><tr><td style="width: 250px;">';
        echo '<span class="evw-area-title">Aktive Spalten</span>';
        echo '<p class="description">Die Reihenfolge kann ebenfalls durch Ziehen ge&auml;ndert werden</p>';
        echo '</td><td class="columns"><ul>';
        foreach ($enabledColumns as $colId) {
            if (!array_key_exists($colId, $columns)) {
                continue;
            }

            $colInfo = $columns[$colId];
            $name = array_key_exists('longName', $colInfo) ? $colInfo['longName'] : $colInfo['name'];
            echo '<li id="' . $colId . '" class="evw-column"><span>' . $name . '</span></li>';
        }
        echo '</ul></td></tr></table>';
        echo '<input name="einsatzvw_list_columns" id="einsatzvw_list_columns" type="hidden" value="' . implode(',', $enabledColumns) . '">';
    }

    public function echoFieldColumnSettings()
    {
        echo '<fieldset>';
        $this->echoSettingsCheckbox(
            'einsatzvw_list_art_hierarchy',
            '<strong>Einsatzart</strong>: Hierarchie der Einsatzart anzeigen'
        );
        echo '<br/>';
        $this->echoSettingsCheckbox(
            'einsatzvw_list_fahrzeuge_link',
            '<strong>Fahrzeuge</strong>: Links zu den Fahrzeugseiten anzeigen, sofern verf&uuml;gbar'
        );
        echo '<br/>';
        $this->echoSettingsCheckbox(
            'einsatzvw_list_ext_link',
            '<strong>Weitere Kr&auml;fte</strong>: Links anzeigen, sofern verf&uuml;gbar'
        );
        echo '</fieldset>';
    }

    public function echoFieldZebra()
    {
        echo '<fieldset>';
        $this->echoSettingsCheckbox(
            'einsatzvw_list_zebra',
            'Zebrastreifen anzeigen'
        );
        echo '<p class="description">Die Zeilen der Tabelle werden abwechselnd eingef&auml;rbt, um die Lesbarkeit zu verbessern. Wenn das Theme das ebenfalls tut, sollte diese Option deaktiviert werden, um Probleme bei der Darstellung zu vermeiden.</p>';

        echo '<p>Farbe f&uuml;r Zebrastreifen:</p>';
        $this->echoColorPicker('einsatzvw_list_zebracolor', ReportListSettings::DEFAULT_ZEBRACOLOR);
        echo '<p class="description">Diese Farbe wird f&uuml;r jede zweite Zeile verwendet, die jeweils andere Zeile beh&auml;lt die vom Theme vorgegebene Farbe.</p>';

        echo '<p><fieldset><label><input type="radio" name="einsatzvw_list_zebra_nth" value="even" ';
        checked($this->reportListSettings->getZebraNthChildArg(), 'even');
        echo '>Gerade Zeilen einf&auml;rben</label> <label><input type="radio" name="einsatzvw_list_zebra_nth" value="odd" ';
        checked($this->reportListSettings->getZebraNthChildArg(), 'odd');
        echo '>Ungerade Zeilen einf&auml;rben</label></fieldset></p>';
        echo '</fieldset>';
    }

    public function registerSettings()
    {
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_columns',
            array('\abrain\Einsatzverwaltung\Frontend\ReportList', 'sanitizeColumns')
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_art_hierarchy',
            array('Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_fahrzeuge_link',
            array('Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_ext_link',
            array('Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_zebra',
            array('Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_zebracolor',
            array($this, 'sanitizeZebraColor') // NEEDS_WP4.6 das globale sanitize_hex_color() verwenden
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_zebra_nth',
            array($this->reportListSettings, 'sanitizeZebraNthChildArg')
        );
    }

    /**
     * Stellt sicher, dass die Farbe für die Zebrastreifen gültig ist
     *
     * @param string $input Der zu prüfende Farbwert
     *
     * @return string Der übergebene Farbwert, wenn er gültig ist, ansonsten die Standardeinstellung
     */
    public function sanitizeZebraColor($input)
    {
        return Utilities::sanitizeHexColor($input, ReportListSettings::DEFAULT_ZEBRACOLOR);
    }
}
