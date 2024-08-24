<?php
namespace abrain\Einsatzverwaltung\Settings\Pages;

use abrain\Einsatzverwaltung\Frontend\ReportList\Column;
use abrain\Einsatzverwaltung\Frontend\ReportList\ColumnRepository;
use abrain\Einsatzverwaltung\Frontend\ReportList\Parameters;
use abrain\Einsatzverwaltung\Frontend\ReportList\Settings;
use abrain\Einsatzverwaltung\Utilities;

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
        parent::__construct('list', __('Report list', 'einsatzverwaltung'));

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
                echo '<p>Mit diesen Einstellungen kann das Aussehen der Einsatzlisten beeinflusst werden. Einsatzlisten k&ouml;nnen &uuml;ber den Shortcode [einsatzliste] in Seiten und Beitr&auml;ge eingebunden werden.</p>';
            },
            $this->settingsApiPage
        );
    }

    /**
     * @param Column $column
     */
    private function echoDraggableColumn(Column $column)
    {
        printf(
            '<li id="%s" class="evw-column"><span>%s</span></li>',
            esc_attr($column->getIdentifier()),
            esc_html($column->getNameForSettings())
        );
    }

    public function echoFieldColumns()
    {
        $columnRepository = ColumnRepository::getInstance();
        $columnIdentifiers = explode(',', get_option('einsatzvw_list_columns', ''));
        $enabledColumns = $columnRepository->getColumnsByIdentifier($columnIdentifiers);
        $availableColumns = $columnRepository->getAvailableColumns();

        echo '<table id="columns-available"><tr><td style="width: 250px;">';
        echo '<span class="evw-area-title">Verf&uuml;gbare Spalten</span>';
        echo '<p class="description">Spaltennamen in unteres Feld ziehen, um sie auf der Seite anzuzeigen</p>';
        echo '</td><td class="columns"><ul>';
        foreach ($availableColumns as $column) {
            if (in_array($column, $enabledColumns)) {
                continue;
            }
            $this->echoDraggableColumn($column);
        }
        echo '</ul></td></tr></table>';

        echo '<table id="columns-enabled"><tr><td style="width: 250px;">';
        echo '<span class="evw-area-title">Aktive Spalten</span>';
        echo '<p class="description">Die Reihenfolge kann ebenfalls durch Ziehen ge&auml;ndert werden</p>';
        echo '</td><td class="columns"><ul>';
        foreach ($enabledColumns as $column) {
            $this->echoDraggableColumn($column);
        }
        echo '</ul></td></tr></table>';
        printf(
            '<input name="einsatzvw_list_columns" id="einsatzvw_list_columns" type="hidden" value="%s">',
            esc_attr($columnRepository->getIdentifiers($enabledColumns))
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
            [Parameters::class , 'sanitizeColumns']
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_art_hierarchy',
            [Utilities::class, 'sanitizeCheckbox']
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_fahrzeuge_link',
            [Utilities::class, 'sanitizeCheckbox']
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_ext_link',
            [Utilities::class, 'sanitizeCheckbox']
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_zebra',
            [Utilities::class, 'sanitizeCheckbox']
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_zebracolor',
            'sanitize_hex_color'
        );
        register_setting(
            'einsatzvw_settings_list',
            'einsatzvw_list_zebra_nth',
            [$this->reportListSettings, 'sanitizeZebraNthChildArg']
        );
    }
}
