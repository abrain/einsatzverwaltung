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
     * @var Options
     */
    private $options;

    /**
     * @var Core
     */
    private $core;

    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * Konstruktor
     *
     * @param Core $core
     * @param Options $options
     * @param Utilities $utilities
     */
    public function __construct($core, $options, $utilities)
    {
        $this->core = $core;
        $this->options = $options;
        $this->utilities = $utilities;
        $this->addHooks();
    }


    private function addHooks()
    {
        add_action('admin_menu', array($this, 'addToSettingsMenu'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_filter('pre_update_option_einsatzvw_rewrite_slug', array($this, 'maybeRewriteSlugChanged'), 10, 2);
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
            'einsatzvw_rewrite_slug',
            'sanitize_title'
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_einsatznummer_stellen',
            array($this->utilities, 'sanitizeEinsatznummerStellen')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_einsatznummer_lfdvorne',
            array($this->utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_show_einsatzberichte_mainloop',
            array($this->utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_category',
            'intval'
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_einsatz_hideemptydetails',
            array($this->utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_show_exteinsatzmittel_archive',
            array($this->utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_show_einsatzart_archive',
            array($this->utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_show_fahrzeug_archive',
            array($this->utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_open_ext_in_new',
            array($this->utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_excerpt_type',
            array($this->utilities, 'sanitizeExcerptType')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_excerpt_type_feed',
            array($this->utilities, 'sanitizeExcerptType')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_list_columns',
            array($this->utilities, 'sanitizeColumns')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_list_art_hierarchy',
            array($this->utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_list_fahrzeuge_link',
            array($this->utilities, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings',
            'einsatzvw_list_ext_link',
            array($this->utilities, 'sanitizeCheckbox')
        );

        $roles = get_editable_roles();
        if (!empty($roles)) {
            foreach (array_keys($roles) as $role_slug) {
                register_setting(
                    'einsatzvw_settings',
                    'einsatzvw_cap_roles_' . $role_slug,
                    array($this->utilities, 'sanitizeCheckbox')
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
            function () {
                echo '<p>Mit diesen Einstellungen kann das Aussehen der Einsatzberichte beeinflusst werden.</p>';
            },
            self::EVW_SETTINGS_SLUG
        );
        add_settings_section(
            'einsatzvw_settings_einsatzliste',
            'Einsatzliste',
            function () {
                echo '<p>Mit diesen Einstellungen kann das Aussehen der Einsatzlisten beeinflusst werden.</p>';
            },
            self::EVW_SETTINGS_SLUG
        );
        add_settings_section(
            'einsatzvw_settings_caps',
            'Berechtigungen',
            function () {
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
            'einsatzvw_permalinks',
            'Permalinks',
            array($this, 'echoSettingsPermalinks'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_general'
        );
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
        echo $this->utilities->checked($this->options->getBoolOption($checkboxId)) . '/><label for="' . $checkboxId . '">';
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
     * Gibt ein Eingabefeld aus
     *
     * @since 1.0.0
     *
     * @param string $name Name des Parameters
     * @param string $description Beschreibungstext
     * @param string $value Wert, der im Eingabefeld stehen soll
     */
    private function echoSettingsInput($name, $description, $value = '')
    {
        printf(
            '<input type="text" value="%2$s" id="%1$s" name="%1$s" /><p class="description">%3$s</p>',
            $name,
            (empty($value) ? $this->options->getOption($name) : $value),
            $description
        );
    }


    /**
     * @since 1.0.0
     */
    public function echoSettingsPermalinks()
    {
        $this->echoSettingsInput(
            'einsatzvw_rewrite_slug',
            sprintf(
                'Basis f&uuml;r Links zu Einsatzberichten, zum %1$sArchiv%2$s und zum %3$sFeed%2$s.',
                '<a href="'.get_post_type_archive_link('einsatz').'">',
                '</a>',
                '<a href="'.get_post_type_archive_feed_link('einsatz').'">'
            ),
            $this->options->getRewriteSlug()
        );
    }

    /**
     *
     */
    public function echoSettingsEinsatznummerFormat()
    {
        printf('Jahreszahl + jahresbezogene, fortlaufende Nummer mit <input type="text" value="%2$s" size="2" id="%1$s" name="%1$s" /> Stellen<p class="description">Beispiel f&uuml;r den f&uuml;nften Einsatz in 2014:<br>bei 2 Stellen: 201405<br>bei 4 Stellen: 20140005</p><br>', 'einsatzvw_einsatznummer_stellen', $this->options->getEinsatznummerStellen());
        $this->echoSettingsCheckbox('einsatzvw_einsatznummer_lfdvorne', 'Laufende Nummer vor das Jahr stellen');

        echo '<br><br><strong>Hinweis:</strong> Nach einer &Auml;nderung des Formats erhalten die bestehenden Einsatzberichte nicht automatisch aktualisierte Nummern. Nutzen Sie daf&uuml;r das Werkzeug <a href="'.admin_url('tools.php?page=einsatzvw-tool-enr').'">Einsatznummern reparieren</a>.';
    }


    /**
     * Gibt die Einstellmöglichkeit aus, ob und wie Einsatzberichte zusammen mit anderen Beiträgen ausgegeben werden
     * sollen
     */
    public function echoEinsatzberichteMainloop()
    {
        $this->echoSettingsCheckbox(
            'einsatzvw_show_einsatzberichte_mainloop',
            'Einsatzberichte zwischen den regul&auml;ren WordPress-Beitr&auml;gen (z.B. auf der Startseite) anzeigen'
        );

        echo '<p><label for="einsatzvw_category">';
        _e('Davon unabh&auml;ngig Einsatzberichte in folgender Kategorie einblenden:', 'einsatzverwaltung');
        echo '&nbsp;</label>';
        wp_dropdown_categories(array(
            'show_option_none' => '- keine -',
            'hide_empty' => false,
            'name' => 'einsatzvw_category',
            'selected' => $this->options->getEinsatzberichteCategory(),
            'orderby' => 'name',
            'hierarchical' => true
        ));
        echo '</p>';
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
        $types = $this->core->getExcerptTypes();

        echo '<p>Kurzfassung auf der Webseite:&nbsp;';
        $this->echoSelect(
            'einsatzvw_excerpt_type',
            $types,
            $this->options->getExcerptType()
        );
        echo '<p class="description">Sollte diese Einstellung keinen Effekt auf der Webseite zeigen, nutzt das verwendete Theme m&ouml;glicherweise keine Kurzfassungen und zeigt immer den vollen Beitrag.</p>';

        echo '<p>Kurzfassung im Feed:&nbsp;';
        $this->echoSelect(
            'einsatzvw_excerpt_type_feed',
            $types,
            $this->options->getExcerptTypeFeed()
        );
        echo '<p class="description">Bitte auch die Einstellung zum Umfang der Eintr&auml;ge im Feed (Einstellungen &gt; Lesen) beachten!<br/>Im Feed werden bei den Einsatzdetails aus technischen Gr&uuml;nden keine Links zu gefilterten Einsatzlisten angezeigt.</p>';
    }


    /**
     *
     */
    public function echoEinsatzlisteColumns()
    {
        $columns = $this->core->getListColumns();
        $enabledColumns = $this->options->getEinsatzlisteEnabledColumns();

        echo '<table id="columns-available"><tr><td style="width: 250px;">';
        echo '<span class="evw-area-title">Verf&uuml;gbare Spalten</span>';
        echo '<p class="description">Spaltennamen in unteres Feld ziehen, um sie auf der Seite anzuzeigen</p>';
        echo '</td><td class="columns"><ul>';
        foreach ($columns as $colId => $colInfo) {
            if (in_array($colId, $enabledColumns)) {
                continue;
            }
            $name = $this->utilities->getArrayValueIfKey($colInfo, 'longName', $colInfo['name']);
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
            $name = $this->utilities->getArrayValueIfKey($colInfo, 'longName', $colInfo['name']);
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
        echo '<h2>Entwicklerkontakt &amp; Social Media</h2>';
        echo '<p>eMail: <a href="mailto:kontakt@abrain.de">kontakt@abrain.de</a> <span title="PGP Schl&uuml;ssel-ID: 8752EB8F" class="pgpbadge"><i class="fa fa-lock"></i>&nbsp;PGP</span></p>';
        echo '<p align="center"><a href="https://www.facebook.com/einsatzverwaltung/" title="Einsatzverwaltung auf Facebook"><i class="fa fa-facebook-official fa-2x"></i></a>&nbsp;&nbsp;';
        echo '<a href="https://twitter.com/einsatzvw" title="Einsatzverwaltung auf Twitter"><i class="fa fa-twitter fa-2x"></i></a>&nbsp;&nbsp;';
        echo '<a href="https://alpha.app.net/einsatzverwaltung" title="Einsatzverwaltung auf Alpha by App.net"><i class="fa fa-adn fa-2x"></i></a>&nbsp;&nbsp;';
        echo '<a href="https://www.abrain.de/category/software/einsatzverwaltung/feed/" title="RSS-Feed mit Neuigkeiten zu Einsatzverwaltung"><i class="fa fa-rss-square fa-2x"></i></a>';
        echo '</p></div>';

        echo '<div class="wrap">';
        echo '<h1>Einstellungen &rsaquo; Einsatzverwaltung</h1>';

        // Berechtigungen aktualisieren
        $roles = get_editable_roles();
        if (!empty($roles)) {
            foreach (array_keys($roles) as $role_slug) {
                $role_obj = get_role($role_slug);
                $allowed = $this->options->isRoleAllowedToEdit($role_slug);
                foreach ($this->core->getCapabilities() as $cap) {
                    $role_obj->add_cap($cap, $allowed);
                }
            }
        }

        // Prüfen, ob Rewrite Slug von einer Seite genutzt wird
        $rewriteSlug = $this->options->getRewriteSlug();
        $conflictingPage = get_page_by_path($rewriteSlug);
        if ($conflictingPage instanceof \WP_Post) {
            $pageEditLink = '<a href="' . get_edit_post_link($conflictingPage->ID) . '">' . $conflictingPage->post_title . '</a>';
            $message = sprintf('Die Seite %s und das Archiv der Einsatzberichte haben einen identischen Permalink (%s). &Auml;ndere einen der beiden Permalinks, um beide Seiten erreichen zu k&ouml;nnen.', $pageEditLink, $rewriteSlug);
            echo '<div class="error"><p>' . $message . '</p></div>';
        }

        // Einstellungen ausgeben
        echo '<form method="post" action="options.php">';
        settings_fields('einsatzvw_settings');
        do_settings_sections(self::EVW_SETTINGS_SLUG);
        submit_button();
        echo '</form>';
    }

    /**
     * Prüft, ob sich die Basis für die Links zu Einsatzberichten ändert und veranlasst gegebenenfalls ein Erneuern der
     * Permalinkstruktur
     *
     * @param string $new_value Der neue Wert
     * @param string $old_value Der alte Wert
     * @return string Der zu speichernde Wert
     */
    public function maybeRewriteSlugChanged($new_value, $old_value)
    {
        if ($new_value != $old_value) {
            $this->options->setFlushRewriteRules(true);
        }
        return $new_value;
    }
}
