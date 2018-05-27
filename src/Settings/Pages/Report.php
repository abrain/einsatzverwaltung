<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

/**
 * Report settings page
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class Report extends SubPage
{
    private $useReportTemplateOptions = array(
        'no' => 'Nicht verwenden (zeigt die klassische Einzelansicht)',
        'singular' => 'In der Einzelansicht verwenden',
        'loops' => 'In der Einzelansicht und in &Uuml;bersichten (Startseite, Archive, Suchergebnisse, ...) verwenden',
        'everywhere' => '&Uuml;berall verwenden',
    );

    public function __construct()
    {
        parent::__construct('report', 'Einsatzberichte');
    }

    public function addSettingsFields()
    {
        add_settings_field(
            'einsatzvw_einsatz_hideemptydetails',
            'Einsatzdetails',
            array($this, 'echoFieldEmptyDetails'),
            $this->settingsApiPage,
            'einsatzvw_settings_einsatzberichte'
        );
        add_settings_field(
            'einsatzvw_settings_archivelinks',
            'Gefilterte Einsatzübersicht verlinken',
            array($this, 'echoFieldArchiveLinks'),
            $this->settingsApiPage,
            'einsatzvw_settings_einsatzberichte'
        );
        add_settings_field(
            'einsatzvw_settings_ext_newwindow',
            'Links zu externen Einsatzmitteln',
            array($this, 'echoFieldExtNew'),
            $this->settingsApiPage,
            'einsatzvw_settings_einsatzberichte'
        );
        add_settings_field(
            'einsatzvw_settings_reporttemplate',
            'Template f&uuml;r Einsatzbericht',
            array($this, 'echoFieldReportTemplate'),
            $this->settingsApiPage,
            'einsatzvw_settings_reporttemplates'
        );
        add_settings_field(
            'einsatzvw_settings_excerpttemplate',
            'Template f&uuml;r Auszug',
            array($this, 'echoFieldExcerptTemplate'),
            $this->settingsApiPage,
            'einsatzvw_settings_reporttemplates'
        );
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

    /**
     * Gibt die Einstellmöglichkeiten für nicht ausgefüllte Einsatzdetails aus
     */
    public function echoFieldEmptyDetails()
    {
        echo '<fieldset>';
        $this->echoSettingsCheckbox(
            'einsatzvw_einsatz_hideemptydetails',
            'Nicht ausgef&uuml;llte Details ausblenden',
            true
        );
        echo '<p class="description">Ein Einsatzdetail gilt als nicht ausgef&uuml;llt, wenn das entsprechende Textfeld oder die entsprechende Liste leer ist. Diese Einstellung greift nur bei der klassischen Darstellung ohne Template.</p>';
        echo '</fieldset>';
    }

    /**
     * Gibt die Einstellmöglichkeiten für gefilterte Ansichten aus
     */
    public function echoFieldArchiveLinks()
    {
        echo '<fieldset>';
        $this->echoSettingsCheckbox(
            'einsatzvw_show_einsatzart_archive',
            'Einsatzart'
        );
        echo '<br>';
        $this->echoSettingsCheckbox(
            'einsatzvw_show_exteinsatzmittel_archive',
            'Externe Einsatzkr&auml;fte'
        );
        echo '<br>';
        $this->echoSettingsCheckbox(
            'einsatzvw_show_fahrzeug_archive',
            'Fahrzeuge'
        );
        echo '<p class="description">F&uuml;r alle hier aktivierten Arten von Einsatzdetails werden im Kopfbereich des Einsatzberichts f&uuml;r alle auftretenden Werte Links zu einer gefilterten Einsatz&uuml;bersicht angezeigt. Beispielsweise kann man damit alle Eins&auml;tze unter Beteiligung einer bestimmten externen Einsatzkraft auflisten lassen.</p>';
        echo '</fieldset>';
    }


    /**
     * Gibt die Einstellmöglichkeiten aus, ob Links zu externen Einsatzmitteln in einem neuen Fenster geöffnet werden
     * sollen
     */
    public function echoFieldExtNew()
    {
        echo '<fieldset>';
        $this->echoSettingsCheckbox(
            'einsatzvw_open_ext_in_new',
            'Links zu externen Einsatzmitteln in einem neuen Fenster öffnen'
        );
        echo '</fieldset>';
    }

    /**
     * Einstellungen für die Gestaltung der Einsatzberichte per Template
     */
    public function echoFieldReportTemplate()
    {
        echo '<fieldset>';
        $this->echoRadioButtons('einsatzverwaltung_use_reporttemplate', $this->useReportTemplateOptions, 'no');
        echo '<p class="description">';
        printf('Die Option &quot;%s&quot; wird nicht empfohlen, ist aber bei manchen Themes die einzige M&ouml;glichkeit, das Template in &Uuml;bersichten nutzen zu k&ouml;nnen.', $this->useReportTemplateOptions['everywhere']);
        echo '</p>';
        $this->echoTextarea('einsatzverwaltung_reporttemplate');
        echo '<p class="description">Es kann sein, dass das Theme in &Uuml;bersichten nur den Auszug anzeigt. Dessen Aussehen kann mit einem eigenen Template festgelegt werden (siehe unten).</p>';
        echo '</fieldset>';
    }

    /**
     * Einstellungen für die Gestaltung des Auszugs von Einsatzberichten per Template
     */
    public function echoFieldExcerptTemplate()
    {
        echo '<fieldset>';
        $this->echoSettingsCheckbox('einsatzverwaltung_use_excerpttemplate', 'Template verwenden');
        echo '<p class="description">Im Gegensatz zum von WordPress generierten Auszug wird dieser nicht auf eine bestimmte L&auml;nge begrenzt. Das Einf&uuml;gen des Beitragstextes (<code>%content%</code>) ist also nicht zu empfehlen.</p>';
        $this->echoTextarea('einsatzverwaltung_excerpttemplate');
        echo '</fieldset>';
    }

    public function registerSettings()
    {
        register_setting(
            'einsatzvw_settings_report',
            'einsatzvw_einsatz_hideemptydetails',
            array(self::$utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_report',
            'einsatzvw_show_exteinsatzmittel_archive',
            array(self::$utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_report',
            'einsatzvw_show_einsatzart_archive',
            array(self::$utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_report',
            'einsatzvw_show_fahrzeug_archive',
            array(self::$utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_report',
            'einsatzvw_open_ext_in_new',
            array(self::$utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_report',
            'einsatzverwaltung_use_reporttemplate',
            array($this, 'sanitizeReportTemplateUsage')
        );
        register_setting(
            'einsatzvw_settings_report',
            'einsatzverwaltung_reporttemplate',
            array($this, 'sanitizeTemplate')
        );
        register_setting(
            'einsatzvw_settings_report',
            'einsatzverwaltung_use_excerpttemplate',
            array(self::$utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_report',
            'einsatzverwaltung_excerpttemplate',
            array($this, 'sanitizeTemplate')
        );
    }

    /**
     * @param string $input
     * @return string
     */
    public function sanitizeReportTemplateUsage($input)
    {
        if (!in_array($input, array_keys($this->useReportTemplateOptions))) {
            return 'no';
        }

        return $input;
    }

    /**
     * @param string $input
     * @return string
     */
    public function sanitizeTemplate($input)
    {
        return stripslashes(wp_filter_post_kses(addslashes($input)));
    }
}
