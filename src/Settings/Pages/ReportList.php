<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

use abrain\Einsatzverwaltung\Frontend\ReportList\Parameters as ReportListParameters;
use abrain\Einsatzverwaltung\Frontend\ReportList\Renderer as ReportListRenderer;
use abrain\Einsatzverwaltung\Frontend\ReportList\Settings;

/**
 * ReportList settings page
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class ReportList extends SubPage
{
    /**
     * @var Settings
     */
    private $reportListSettings;

    public function __construct()
    {
        parent::__construct('list', 'Einsatzliste');

        $this->reportListSettings = new Settings();
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

    public function echoFieldColumns()
    {
        $columns = ReportListRenderer::getListColumns();
        $enabledColumnsString = ReportListParameters::sanitizeColumns(get_option('einsatzvw_list_columns', ''));
        $enabledColumns = explode(',', $enabledColumnsString);

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
        printf(
            '<input name="einsatzvw_list_columns" id="einsatzvw_list_columns" type="hidden" value="%s">',
            esc_attr($enabledColumnsString)
        );
    }

    public function echoFieldColumnSettings()
    {
        echo '<fieldset>';
        $this->echoSettingsCheckbox(
            'einsatzvw_list_art_hierarchy',
            'Einsatzart: Hierarchie der Einsatzart anzeigen'
        );
        echo '<br/>';
        $this->echoSettingsCheckbox(
            'einsatzvw_list_fahrzeuge_link',
            'Fahrzeuge: Links zu den Fahrzeugseiten anzeigen, sofern verf&uuml;gbar'
        );
        echo '<br/>';
        $this->echoSettingsCheckbox(
            'einsatzvw_list_ext_link',
            'Weitere Kr&auml;fte: Links anzeigen, sofern verf&uuml;gbar'
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
        $this->echoColorPicker('einsatzvw_list_zebracolor', Settings::DEFAULT_ZEBRACOLOR);
        echo '<p class="description">Diese Farbe wird f&uuml;r jede zweite Zeile verwendet, die jeweils andere Zeile beh&auml;lt die vom Theme vorgegebene Farbe.</p>';

        echo '<p><fieldset><label><input type="radio" name="einsatzvw_list_zebra_nth" value="even" ';
        $zebraNthChildArg = $this->reportListSettings->getZebraNthChildArg();
        checked($zebraNthChildArg, 'even');
        echo '>Gerade Zeilen einf&auml;rben</label> <label><input type="radio" name="einsatzvw_list_zebra_nth" value="odd" ';
        checked($zebraNthChildArg, 'odd');
        echo '>Ungerade Zeilen einf&auml;rben</label></fieldset></p>';
        echo '</fieldset>';
    }

    public function registerSettings()
    {
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_columns',
            array('\abrain\Einsatzverwaltung\Frontend\ReportList\Parameters', 'sanitizeColumns')
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_art_hierarchy',
            array('\abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_fahrzeuge_link',
            array('\abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_ext_link',
            array('\abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_zebra',
            array('\abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_zebracolor',
            'sanitize_hex_color'
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_zebra_nth',
            array($this->reportListSettings, 'sanitizeZebraNthChildArg')
        );
    }
}
