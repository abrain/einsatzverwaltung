<?php

class EinsatzverwaltungSettings
{
    const EVW_SETTINGS_SLUG = 'einsatzvw-settings';

    function __construct()
    {
        $this->addHooks();
    }


    private function addHooks()
    {
        add_action('admin_menu', array($this, 'einsatzverwaltung_settings_menu'));
        add_action('admin_init', array($this, 'einsatzverwaltung_register_settings'));
        add_filter('plugin_action_links_' . EINSATZVERWALTUNG__PLUGIN_BASE, 'einsatzverwaltung_add_action_links');
    }


    /**
     * Fügt die Einstellungsseite zum Menü hinzu
     */
    public function einsatzverwaltung_settings_menu()
    {
        add_options_page('Einstellungen', 'Einsatzverwaltung', 'manage_options', self::EVW_SETTINGS_SLUG, array($this, 'einsatzverwaltung_settings_page'));
    }


    /**
     * Zeigt einen Link zu den Einstellungen direkt auf der Plugin-Seite an
     */
    public function einsatzverwaltung_add_action_links($links)
    {
        $mylinks = array('<a href="' . admin_url('options-general.php?page='.self::EVW_SETTINGS_SLUG) . '">Einstellungen</a>');
        return array_merge($links, $mylinks);
    }


    /**
     * Macht Einstellungen im System bekannt und regelt die Zugehörigkeit zu Abschnitten auf Einstellungsseiten
     */
    public function einsatzverwaltung_register_settings()
    {
        // Sections
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
        /*add_settings_section('einsatzvw_settings_einsatzliste',
            'Einsatzliste',
            function() {
                echo '<p>Mit diesen Einstellungen kann das Aussehen der Einsatzlisten beeinflusst werden.</p>';
            },
            self::EVW_SETTINGS_SLUG
        );*/
        add_settings_section(
            'einsatzvw_settings_caps',
            'Berechtigungen',
            function() {
                echo '<p>Hier kann festgelegt werden, welche Benutzer die Einsatzberichte verwalten k&ouml;nnen.</p>';
            },
            self::EVW_SETTINGS_SLUG
        );
    
        // Fields
        add_settings_field(
            'einsatzvw_einsatznummer_stellen',
            'Format der Einsatznummer',
            array($this, 'einsatzverwaltung_echo_einsatznummer_format'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_general'
        );
        add_settings_field(
            'einsatzvw_einsatznummer_mainloop',
            'Einsatzbericht als Beitrag',
            array($this, 'einsatzverwaltung_echo_einsatzberichte_mainloop'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_general'
        );
        add_settings_field(
            'einsatzvw_einsatz_hideemptydetails',
            'Einsatzdetails',
            array($this, 'einsatzverwaltung_echo_settings_empty_details'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_einsatzberichte'
        );
        add_settings_field(
            'einsatzvw_settings_archivelinks',
            'Gefilterte Einsatzübersicht verlinken',
            array($this, 'einsatzverwaltung_echo_settings_archive'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_einsatzberichte'
        );
        add_settings_field(
            'einsatzvw_settings_ext_newwindow',
            'Links zu externen Einsatzmitteln',
            array($this, 'einsatzverwaltung_echo_settings_extnew'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_einsatzberichte'
        );
        add_settings_field(
            'einsatzvw_settings_excerpt',
            'Auszug / Exzerpt',
            array($this, 'einsatzverwaltung_echo_settings_excerpt'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_einsatzberichte'
        );
        add_settings_field(
            'einsatzvw_settings_caps_roles',
            'Rollen',
            array($this, 'einsatzverwaltung_echo_settings_caps_roles'),
            self::EVW_SETTINGS_SLUG,
            'einsatzvw_settings_caps'
        );
    
        // Registration
        register_setting('einsatzvw_settings', 'einsatzvw_einsatznummer_stellen', array($this, 'einsatzverwaltung_sanitize_einsatznummer_stellen'));
        register_setting('einsatzvw_settings', 'einsatzvw_einsatznummer_lfdvorne', 'einsatzverwaltung_sanitize_checkbox');
        register_setting('einsatzvw_settings', 'einsatzvw_show_einsatzberichte_mainloop', 'einsatzverwaltung_sanitize_checkbox');
        register_setting('einsatzvw_settings', 'einsatzvw_einsatz_hideemptydetails', 'einsatzverwaltung_sanitize_checkbox');
        register_setting('einsatzvw_settings', 'einsatzvw_show_exteinsatzmittel_archive', 'einsatzverwaltung_sanitize_checkbox');
        register_setting('einsatzvw_settings', 'einsatzvw_show_einsatzart_archive', 'einsatzverwaltung_sanitize_checkbox');
        register_setting('einsatzvw_settings', 'einsatzvw_show_fahrzeug_archive', 'einsatzverwaltung_sanitize_checkbox');
        register_setting('einsatzvw_settings', 'einsatzvw_open_ext_in_new', 'einsatzverwaltung_sanitize_checkbox');
        register_setting('einsatzvw_settings', 'einsatzvw_show_links_in_excerpt', 'einsatzverwaltung_sanitize_checkbox');
    
        $roles = get_editable_roles();
        if (!empty($roles)) {
            foreach (array_keys($roles) as $role_slug) {
                register_setting('einsatzvw_settings', 'einsatzvw_cap_roles_' . $role_slug, 'einsatzverwaltung_sanitize_checkbox');
            }
        }
    }


    /**
     *
     */
    private function einsatzverwaltung_echo_settings_checkbox($args)
    {
        $id = $args[0];
        $text = $args[1];
        $default = (count($args) > 2 ? $args[2] : false);
        printf('<input type="checkbox" value="1" id="%1$s" name="%1$s" %2$s/><label for="%1$s">%3$s</label>', $id, einsatzverwaltung_checked(get_option($id, $default)), $text);
    }


    /**
     *
     */
    private function einsatzverwaltung_echo_settings_input($args)
    {
        $id = $args[0];
        $text = $args[1];
        printf('<input type="text" value="%2$s" id="%1$s" name="%1$s" /><p class="description">%3$s</p>', $id, get_option($id), $text);
    }


    /**
     *
     */
    public function einsatzverwaltung_echo_einsatznummer_format()
    {
        printf('Jahreszahl + jahresbezogene, fortlaufende Nummer mit <input type="text" value="%2$s" size="2" id="%1$s" name="%1$s" /> Stellen<p class="description">Beispiel f&uuml;r den f&uuml;nften Einsatz in 2014:<br>bei 2 Stellen: 201405<br>bei 4 Stellen: 20140005</p><br>', 'einsatzvw_einsatznummer_stellen', get_option('einsatzvw_einsatznummer_stellen'));
        $this->einsatzverwaltung_echo_settings_checkbox(
            array(
                'einsatzvw_einsatznummer_lfdvorne',
                'Laufende Nummer vor das Jahr stellen'
            )
        );
    
        echo '<br><br><strong>Hinweis:</strong> Nach einer &Auml;nderung des Formats erhalten die bestehenden Einsatzberichte nicht automatisch aktualisierte Nummern. Nutzen Sie daf&uuml;r das Werkzeug <a href="'.admin_url('tools.php?page=einsatzvw-tool-enr').'">Einsatznummern reparieren</a>.';
    }


    /**
     * Stellt einen sinnvollen Wert für die Anzahl Stellen der laufenden Einsatznummer sicher
     */
    public function einsatzverwaltung_sanitize_einsatznummer_stellen($input)
    {
        $val = intval($input);
        if (is_numeric($val) && $val > 0) {
            return $val;
        } else {
            return EINSATZVERWALTUNG__EINSATZNR_STELLEN;
        }
    }


    /**
     *
     */
    public function einsatzverwaltung_echo_einsatzberichte_mainloop()
    {
        $this->einsatzverwaltung_echo_settings_checkbox(
            array(
                'einsatzvw_show_einsatzberichte_mainloop',
                'Einsatzberichte wie reguläre Beitr&auml;ge anzeigen',
                EINSATZVERWALTUNG__D__SHOW_EINSATZBERICHTE_MAINLOOP
            )
        );
        echo '<p class="description">Mit dieser Option werden Einsatzberichte zwischen den anderen WordPress-Beiträgen (z.B. auf der Startseite) angezeigt.</p>';
    }


    /**
     * Gibt die Einstellmöglichkeiten für nicht ausgefüllte Einsatzdetails aus
     */
    public function einsatzverwaltung_echo_settings_empty_details()
    {
        $this->einsatzverwaltung_echo_settings_checkbox(
            array(
                'einsatzvw_einsatz_hideemptydetails',
                'Nicht ausgef&uuml;llte Details ausblenden',
                EINSATZVERWALTUNG__D__HIDE_EMPTY_DETAILS
            )
        );
        echo '<p class="description">Ein Einsatzdetail gilt als nicht ausgef&uuml;llt, wenn das entsprechende Textfeld oder die entsprechende Liste leer ist. Bei der Mannschaftsst&auml;rke z&auml;hlt auch eine eingetragene 0 als leer.</p>';
    }


    /**
     * Gibt die Einstellmöglichkeiten für gefilterte Ansichten aus
     */
    public function einsatzverwaltung_echo_settings_archive()
    {
        $this->einsatzverwaltung_echo_settings_checkbox(
            array(
                'einsatzvw_show_einsatzart_archive',
                'Einsatzart',
                EINSATZVERWALTUNG__D__SHOW_EINSATZART_ARCHIVE
            )
        );
        echo '<br>';
        $this->einsatzverwaltung_echo_settings_checkbox(
            array(
                'einsatzvw_show_exteinsatzmittel_archive',
                'Externe Einsatzkr&auml;fte',
                EINSATZVERWALTUNG__D__SHOW_EXTEINSATZMITTEL_ARCHIVE
            )
        );
        echo '<br>';
        $this->einsatzverwaltung_echo_settings_checkbox(
            array(
                'einsatzvw_show_fahrzeug_archive',
                'Fahrzeuge',
                EINSATZVERWALTUNG__D__SHOW_FAHRZEUG_ARCHIVE
            )
        );
        echo '<p class="description">F&uuml;r alle hier aktivierten Arten von Einsatzdetails werden im Kopfbereich des Einsatzberichts f&uuml;r alle auftretenden Werte Links zu einer gefilterten Einsatz&uuml;bersicht angezeigt. Beispielsweise kann man damit alle Eins&auml;tze unter Beteiligung einer bestimmten externen Einsatzkraft auflisten lassen.</p>';
    }


    /**
     * Gibt die Einstellmöglichkeiten aus, ob Links zu externen Einsatzmitteln in einem neuen Fenster geöffnet werden sollen
     */
    public function einsatzverwaltung_echo_settings_extnew()
    {
        $this->einsatzverwaltung_echo_settings_checkbox(
            array(
                'einsatzvw_open_ext_in_new',
                'Links zu externen Einsatzmitteln in einem neuen Fenster öffnen',
                EINSATZVERWALTUNG__D__OPEN_EXTEINSATZMITTEL_NEWWINDOW
            )
        );
    }


    /**
     * Gibt die Einstellmöglichkeiten für den Auszug aus
     */
    public function einsatzverwaltung_echo_settings_excerpt()
    {
        $this->einsatzverwaltung_echo_settings_checkbox(
            array(
                'einsatzvw_show_links_in_excerpt',
                'Auszug darf Links enthalten',
                EINSATZVERWALTUNG__D__SHOW_LINKS_IN_EXCERPT
            )
        );
        echo '<p class="description">Welche Links tats&auml;chlich generiert werden, h&auml;ngt von den anderen Einstellungen ab. Der Auszug im Newsfeed enth&auml;lt niemals Links.</p>';
    }


    /**
     * Gibt die Einstellmöglichkeiten für die Berechtigungen aus
     */
    public function einsatzverwaltung_echo_settings_caps_roles()
    {
        $roles = get_editable_roles();
        if (empty($roles)) {
            echo "Es konnten keine Rollen gefunden werden.";
        } else {
            foreach ($roles as $role_slug => $role) {
                $this->einsatzverwaltung_echo_settings_checkbox(array('einsatzvw_cap_roles_' . $role_slug, translate_user_role($role['name']), false));
                echo '<br>';
            }
            echo '<p class="description">Die Benutzer mit den hier ausgew&auml;hlten Rollen haben alle Rechte, um die Einsatzberichte und die zugeh&ouml;rigen Eigenschaften (z.B. Einsatzarten) zu verwalten. Zu dieser Einstellungsseite und den Werkzeugen haben in jedem Fall nur Administratoren Zugang.</p>';
        }
    }


    /**
     * Generiert den Inhalt der Einstellungsseite
     */
    public function einsatzverwaltung_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to manage options for this site.'));
        }
    
        echo '<div id="einsatzverwaltung_contactinfo">';
        echo '<h3>Entwicklerkontakt &amp; Social Media</h3>';
        echo 'eMail: <a href="mailto:kontakt@abrain.de">kontakt@abrain.de</a><br>';
        echo 'Twitter: <a href="https://twitter.com/einsatzvw">@einsatzvw</a><br>';
        echo 'App.net: <a href="https://alpha.app.net/einsatzverwaltung">@einsatzverwaltung</a><br>';
        echo 'Facebook: <a href="https://www.facebook.com/einsatzverwaltung/">Einsatzverwaltung</a>';
        echo '</div>';
    
        echo '<div class="wrap">';
        echo '<h2>Einstellungen &rsaquo; Einsatzverwaltung</h2>';
    
        // Berechtigungen aktualisieren
        $roles = get_editable_roles();
        if (!empty($roles)) {
            global $evw_caps;
            foreach (array_keys($roles) as $role_slug) {
                $role_obj = get_role($role_slug);
                $allowed = get_option('einsatzvw_cap_roles_' . $role_slug, false);
                foreach ($evw_caps as $cap) {
                    $role_obj->add_cap($cap, $allowed);
                }
            }
        }
    
        echo '<form method="post" action="options.php">';
        echo settings_fields('einsatzvw_settings');
        echo do_settings_sections(self::EVW_SETTINGS_SLUG);
        submit_button();
        echo '</form>';
    }
}
