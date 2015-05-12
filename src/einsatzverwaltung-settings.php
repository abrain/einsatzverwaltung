<?php
namespace abrain\Einsatzverwaltung;

/**
 * Erzeugt die Einstellungsseite
 *
 * @author Andreas Brain
 */
class Settings
{
    const EVW_SETTINGS_SLUG = 'einsatzvw-settings';

    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->addHooks();
    }


    private function addHooks()
    {
        add_action('admin_menu', array($this, 'addToSettingsMenu'));
        add_action('admin_init', array($this, 'registerSettings'));
    }


    /**
     * Fügt die Einstellungsseite zum Menü hinzu
     */
    public function addToSettingsMenu()
    {
        add_options_page(
            'Einstellungen',
            'Einsatzverwaltung',
            'manage_options',
            self::EVW_SETTINGS_SLUG,
            array($this, 'echoSettingsPage')
        );
    }


    /**
     * Macht Einstellungen im System bekannt und regelt die Zugehörigkeit zu Abschnitten auf Einstellungsseiten
     */
    public function registerSettings()
    {
        // Sections
        $this->addSettingsSections();

        // Fields
        $this->addSettingsFields();

        // Registration
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_einsatznummer_stellen',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeEinsatznummerStellen')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_einsatznummer_lfdvorne',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_show_einsatzberichte_mainloop',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_einsatz_hideemptydetails',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_show_exteinsatzmittel_archive',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_show_einsatzart_archive',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_show_fahrzeug_archive',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_open_ext_in_new',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_excerpt_type',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeExcerptType')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_excerpt_type_feed',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeExcerptType')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_list_columns',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeColumns')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_list_art_hierarchy',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_list_fahrzeuge_link',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_list_ext_link',
            array('abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
        );

        $roles = get_editable_roles();
        if (!empty($roles)) {
            foreach (array_keys($roles) as $role_slug) {
                register_setting(
                    'einsatzvw_settings',
                    'einsatzvw_cap_roles_' . $role_slug,
                    array('abrain\Einsatzverwaltung\Utilities', 'sanitizeCheckbox')
                );
            }
        }
    }


    /**
     * Fügt die großen Abschnitte in die Einstellungsseite ein
     */
    private function addSettingsSections()
    {
        add_settings_section(
            'einsatzvw_settings_general',
            'Allgemein',
            null,
            self::EVW_SETTINGS_SLUG
        );
        add_settings_section(
            'einsatzvw_settings_einsatzberichte',
            'Einsatzberichte',
            function() {
                echo '<p>Mit diesen Einstellungen kann das Aussehen der Einsatzberichte beeinflusst werden.</p>';
            },
            self::EVW_SETTINGS_SLUG
        );
        add_settings_section(
            'einsatzvw_settings_einsatzliste',
            'Einsatzliste',
            function() {
                echo '<p>Mit diesen Einstellungen kann das Aussehen der Einsatzlisten beeinflusst werden.</p>';
            },
            self::EVW_SETTINGS_SLUG
        );
        add_settings_section(
            'einsatzvw_settings_caps',
            'Berechtigungen',
            function() {
                echo '<p>Hier kann festgelegt werden, welche Benutzer die Einsatzberichte verwalten k&ouml;nnen.</p>';
            },
            self::EVW_SETTINGS_SLUG
        );
    }


    /**
     * Namen und Ausgabemethoden der einzelnen Felder in den Abschnitten
     */
    private function addSettingsFields()
    {
        add_settings_field(
            'einsatzvw_einsatznummer_stellen',
            'Format der Einsatznummer',
            array($this, 'echoSettingsEinsatznummerFormat'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_general'
        );
        add_settings_field(
            'einsatzvw_einsatznummer_mainloop',
            'Einsatzbericht als Beitrag',
            array($this, 'echoEinsatzberichteMainloop'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_general'
        );
        add_settings_field(
            'einsatzvw_einsatz_hideemptydetails',
            'Einsatzdetails',
            array($this, 'echoSettingsEmptyDetails'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_einsatzberichte'
        );
        add_settings_field(
            'einsatzvw_settings_archivelinks',
            'Gefilterte Einsatzübersicht verlinken',
            array($this, 'echoSettingsArchive'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_einsatzberichte'
        );
        add_settings_field(
            'einsatzvw_settings_ext_newwindow',
            'Links zu externen Einsatzmitteln',
            array($this, 'echoSettingsExtNew'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_einsatzberichte'
        );
        add_settings_field(
            'einsatzvw_settings_excerpt',
            'Kurzfassung',
            array($this, 'echoSettingsExcerpt'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_einsatzberichte'
        );
        add_settings_field(
            'einsatzvw_settings_columns',
            'Spalten der Einsatzliste',
            array($this, 'echoEinsatzlisteColumns'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_einsatzliste'
        );
        add_settings_field(
            'einsatzvw_settings_column_settings',
            'Einstellungen zu einzelnen Spalten',
            array($this, 'echoEinsatzlisteColumnSettings'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_einsatzliste'
        );
        add_settings_field(
            'einsatzvw_settings_caps_roles',
            'Rollen',
            array($this, 'echoSettingsCapsRoles'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_caps'
        );
    }


    /**
     * Gibt eine Checkbox auf der Einstellungsseite aus
     *
     * @param string $checkboxId Id der Option
     * @param string $text Beschriftung der Checkbox
     */
    private function echoSettingsCheckbox($checkboxId, $text)
    {
        echo '<input type="checkbox" value="1" id="' . $checkboxId . '" name="' . $checkboxId . '" ';
        echo Utilities::checked(Options::getBoolOption($checkboxId)) . '/><label for="' . $checkboxId . '">';
        echo $text . '</label>';
    }


    /**
     * Generiert eine Auswahlliste
     *
     * @param string $name Name des Parameters
     * @param array $options Array aus Wert/Label-Paaren
     * @param string $selectedValue Vorselektierter Wert
     */
    private function echoSelect($name, $options, $selectedValue)
    {
        echo '<select name="' . $name . '">';
        foreach ($options as $value => $label) {
            echo '<option value="' . $value . '"' . ($selectedValue == $value ? ' selected="selected"' : '') . '>';
            echo $label . '</option>';
        }
        echo '</select>';
    }


    /**
     *
     */
    /*private function echoSettingsInput($args)
    {
        $inputId = $args[0];
        $text = $args[1];
        printf(
            '<input type="text" value="%2$s" id="%1$s" name="%1$s" /><p class="description">%3$s</p>',
            $inputId,
            Options::getOption($inputId),
            $text
        );
    }*/


    /**
     *
     */
    public function echoSettingsEinsatznummerFormat()
    {
        printf('Jahreszahl + jahresbezogene, fortlaufende Nummer mit <input type="text" value="%2$s" size="2" id="%1$s" name="%1$s" /> Stellen<p class="description">Beispiel f&uuml;r den f&uuml;nften Einsatz in 2014:<br>bei 2 Stellen: 201405<br>bei 4 Stellen: 20140005</p><br>', 'einsatzvw_einsatznummer_stellen', Options::getEinsatznummerStellen());
        $this->echoSettingsCheckbox('einsatzvw_einsatznummer_lfdvorne', 'Laufende Nummer vor das Jahr stellen');

        echo '<br><br><strong>Hinweis:</strong> Nach einer &Auml;nderung des Formats erhalten die bestehenden Einsatzberichte nicht automatisch aktualisierte Nummern. Nutzen Sie daf&uuml;r das Werkzeug <a href="'.admin_url('tools.php?page=einsatzvw-tool-enr').'">Einsatznummern reparieren</a>.';
    }


    /**
     * Gibt die Einstellmöglichkeit aus, ob Einsatzberichte zusammen mit anderen Beiträgen ausgegeben werden sollen
     */
    public function echoEinsatzberichteMainloop()
    {
        $this->echoSettingsCheckbox(
            'einsatzvw_show_einsatzberichte_mainloop',
            'Einsatzberichte wie reguläre Beitr&auml;ge anzeigen'
        );
        echo '<p class="description">Mit dieser Option werden Einsatzberichte zwischen den anderen WordPress-Beiträgen (z.B. auf der Startseite) angezeigt.</p>';
    }


    /**
     * Gibt die Einstellmöglichkeiten für nicht ausgefüllte Einsatzdetails aus
     */
    public function echoSettingsEmptyDetails()
    {
        $this->echoSettingsCheckbox(
            'einsatzvw_einsatz_hideemptydetails',
            'Nicht ausgef&uuml;llte Details ausblenden'
        );
        echo '<p class="description">Ein Einsatzdetail gilt als nicht ausgef&uuml;llt, wenn das entsprechende Textfeld oder die entsprechende Liste leer ist.</p>';
    }


    /**
     * Gibt die Einstellmöglichkeiten für gefilterte Ansichten aus
     */
    public function echoSettingsArchive()
    {
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
    }


    /**
     * Gibt die Einstellmöglichkeiten aus, ob Links zu externen Einsatzmitteln in einem neuen Fenster geöffnet werden
     * sollen
     */
    public function echoSettingsExtNew()
    {
        $this->echoSettingsCheckbox(
            'einsatzvw_open_ext_in_new',
            'Links zu externen Einsatzmitteln in einem neuen Fenster öffnen'
        );
    }


    /**
     * Gibt die Einstellmöglichkeiten für den Auszug aus
     */
    public function echoSettingsExcerpt()
    {
        $types = Core::getExcerptTypes();

        echo '<p>Kurzfassung auf der Webseite:&nbsp;';
        $this->echoSelect(
            'einsatzvw_excerpt_type',
            $types,
            Options::getExcerptType()
        );
        echo '<p class="description">Sollte diese Einstellung keinen Effekt auf der Webseite zeigen, nutzt das verwendete Theme m&ouml;glicherweise keine Kurzfassungen und zeigt immer den vollen Beitrag.</p>';

        echo '<p>Kurzfassung im Feed:&nbsp;';
        $this->echoSelect(
            'einsatzvw_excerpt_type_feed',
            $types,
            Options::getExcerptTypeFeed()
        );
        echo '<p class="description">Bitte auch die Einstellung zum Umfang der Eintr&auml;ge im Feed (Einstellungen &gt; Lesen) beachten!<br/>Im Feed werden bei den Einsatzdetails aus technischen Gr&uuml;nden keine Links zu gefilterten Einsatzlisten angezeigt.</p>';
    }


    /**
     *
     */
    public function echoEinsatzlisteColumns()
    {
        $columns = Core::getListColumns();
        $enabledColumns = Options::getEinsatzlisteEnabledColumns();

        echo '<table id="columns-available"><tr><td style="width: 250px;">';
        echo '<span class="evw-area-title">Verf&uuml;gbare Spalten</span>';
        echo '<p class="description">Spaltennamen in unteres Feld ziehen, um sie auf der Seite anzuzeigen</p>';
        echo '</td><td class="columns"><ul>';
        foreach ($columns as $colId => $colInfo) {
            if (in_array($colId, $enabledColumns)) {
                continue;
            }
            $name = Utilities::getArrayValueIfKey($colInfo, 'longName', $colInfo['name']);
            echo '<li id="'.$colId.'" class="evw-column"><span>'. $name .'</span></li>';
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
            $name = Utilities::getArrayValueIfKey($colInfo, 'longName', $colInfo['name']);
            echo '<li id="'.$colId.'" class="evw-column"><span>'. $name .'</span></li>';
        }
        echo '</ul></td></tr></table>';
        echo '<input name="einsatzvw_list_columns" id="einsatzvw_list_columns" type="hidden" value="'.implode(',', $enabledColumns).'">';
    }

    public function echoEinsatzlisteColumnSettings()
    {
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
    }

    /**
     * Gibt die Einstellmöglichkeiten für die Berechtigungen aus
     */
    public function echoSettingsCapsRoles()
    {
        $roles = get_editable_roles();
        if (empty($roles)) {
            echo "Es konnten keine Rollen gefunden werden.";
        } else {
            foreach ($roles as $role_slug => $role) {
                $this->echoSettingsCheckbox(
                    'einsatzvw_cap_roles_' . $role_slug,
                    translate_user_role($role['name'])
                );
                echo '<br>';
            }
            echo '<p class="description">Die Benutzer mit den hier ausgew&auml;hlten Rollen haben alle Rechte, um die Einsatzberichte und die zugeh&ouml;rigen Eigenschaften (z.B. Einsatzarten) zu verwalten. Zu dieser Einstellungsseite und den Werkzeugen haben in jedem Fall nur Administratoren Zugang.</p>';
        }
    }


    /**
     * Generiert den Inhalt der Einstellungsseite
     */
    public function echoSettingsPage()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to manage options for this site.'));
        }

        echo '<div id="einsatzverwaltung_contactinfo">';
        echo '<h3>Entwicklerkontakt &amp; Social Media</h3>';
        echo '<p>eMail: <a href="mailto:kontakt@abrain.de">kontakt@abrain.de</a> <span title="PGP Schl&uuml;ssel-ID: 8752EB8F" class="pgpbadge"><i class="fa fa-lock"></i>&nbsp;PGP</span></p>';
        echo '<p align="center"><a href="https://www.facebook.com/einsatzverwaltung/" title="Einsatzverwaltung auf Facebook"><i class="fa fa-facebook-official fa-2x"></i></a>&nbsp;&nbsp;';
        echo '<a href="https://twitter.com/einsatzvw" title="Einsatzverwaltung auf Twitter"><i class="fa fa-twitter fa-2x"></i></a>&nbsp;&nbsp;';
        echo '<a href="https://alpha.app.net/einsatzverwaltung" title="Einsatzverwaltung auf Alpha by App.net"><i class="fa fa-adn fa-2x"></i></a>&nbsp;&nbsp;';
        echo '<a href="https://www.abrain.de/category/software/einsatzverwaltung/feed/" title="RSS-Feed mit Neuigkeiten zu Einsatzverwaltung"><i class="fa fa-rss-square fa-2x"></i></a>';
        echo '</p></div>';

        echo '<div class="wrap">';
        echo '<h2>Einstellungen &rsaquo; Einsatzverwaltung</h2>';

        // Berechtigungen aktualisieren
        $roles = get_editable_roles();
        if (!empty($roles)) {
            foreach (array_keys($roles) as $role_slug) {
                $role_obj = get_role($role_slug);
                $allowed = Options::isRoleAllowedToEdit($role_slug);
                foreach (Core::getCapabilities() as $cap) {
                    $role_obj->add_cap($cap, $allowed);
                }
            }
        }

        echo '<form method="post" action="options.php">';
        settings_fields('einsatzvw_settings');
        do_settings_sections(self::EVW_SETTINGS_SLUG);
        submit_button();
        echo '</form>';
    }
}
