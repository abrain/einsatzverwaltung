<?

define( 'EVW_SETTINGS_SLUG', 'einsatzvw-settings' );

/**
 * Fügt die Einstellungsseite zum Menü hinzu
 */
function einsatzverwaltung_settings_menu()
{
    add_options_page( 'Einstellungen', 'Einsatzverwaltung', 'manage_options', EVW_SETTINGS_SLUG, 'einsatzverwaltung_settings_page');
}
add_action('admin_menu', 'einsatzverwaltung_settings_menu');


/**
 * Zeigt einen Link zu den Einstellungen direkt auf der Plugin-Seite an
 */
function einsatzverwaltung_add_action_links ( $links ) {
    $mylinks = array('<a href="' . admin_url( 'options-general.php?page='.EVW_SETTINGS_SLUG ) . '">Einstellungen</a>');
    return array_merge( $links, $mylinks );
}
add_filter( 'plugin_action_links_' . EINSATZVERWALTUNG__PLUGIN_BASE , 'einsatzverwaltung_add_action_links' );


/**
 * Macht Einstellungen im System bekannt und regelt die Zugehörigkeit zu Abschnitten auf Einstellungsseiten
 */
function einsatzverwaltung_register_settings()
{
    // Sections
    add_settings_section( 'einsatzvw_settings_general',
        'Allgemein',
        null,
        EVW_SETTINGS_SLUG
    );
    add_settings_section( 'einsatzvw_settings_view',
        'Darstellung',
        function() {
            echo '<p>Mit diesen Einstellungen kann das Aussehen der Einsatzberichte beeinflusst werden.</p>';
        },
        EVW_SETTINGS_SLUG
    );
    
    // Fields
    add_settings_field( 'einsatzvw_einsatznummer_stellen',
        'Format der Einsatznummer',
        'einsatzverwaltung_echo_einsatznummer_format',
        EVW_SETTINGS_SLUG,
        'einsatzvw_settings_general'
    );
    add_settings_field( 'einsatzvw_einsatz_hideemptydetails',
        'Einsatzdetails',
        'einsatzverwaltung_echo_settings_checkbox',
        EVW_SETTINGS_SLUG,
        'einsatzvw_settings_view',
        array('einsatzvw_einsatz_hideemptydetails', 'Nicht ausgef&uuml;llte Details ausblenden (z.B. wenn <em>Weitere Kr&auml;fte</em> leer ist)')
    );
    
    // Registration
    register_setting( 'einsatzvw_settings', 'einsatzvw_einsatznummer_stellen', 'einsatzverwaltung_sanitize_einsatznummer_stellen' );
    register_setting( 'einsatzvw_settings', 'einsatzvw_einsatznummer_lfdvorne', 'einsatzverwaltung_sanitize_checkbox' );
    register_setting( 'einsatzvw_settings', 'einsatzvw_einsatz_hideemptydetails', 'einsatzverwaltung_sanitize_checkbox' );
}
add_action( 'admin_init', 'einsatzverwaltung_register_settings' );


/**
 *
 */
function einsatzverwaltung_echo_settings_checkbox($args)
{
    $id = $args[0];
    $text = $args[1];
    printf('<input type="checkbox" value="1" id="%1$s" name="%1$s" %2$s/><label for="%1$s">%3$s</label>', $id, einsatzverwaltung_checked(get_option($id)), $text);
}


/**
 *
 */
function einsatzverwaltung_echo_settings_input($args)
{
    $id = $args[0];
    $text = $args[1];
    printf('<input type="text" value="%2$s" id="%1$s" name="%1$s" /><p class="description">%3$s</p>', $id, get_option($id), $text);
}


/**
 *
 */
function einsatzverwaltung_echo_einsatznummer_format()
{
    printf('Jahreszahl + jahresbezogene, fortlaufende Nummer mit <input type="text" value="%2$s" size="2" id="%1$s" name="%1$s" /> Stellen<p class="description">Beispiel f&uuml;r den f&uuml;nften Einsatz in 2014:<br>bei 2 Stellen: 201405<br>bei 4 Stellen: 20140005</p><br>', 'einsatzvw_einsatznummer_stellen', get_option('einsatzvw_einsatznummer_stellen'));
    einsatzverwaltung_echo_settings_checkbox(array('einsatzvw_einsatznummer_lfdvorne', 'Laufende Nummer vor das Jahr stellen'));
    
    echo '<br><br><strong>Hinweis:</strong> Nach einer &Auml;nderung des Formats erhalten die bestehenden Einsatzberichte nicht automatisch aktualisierte Nummern. Nutzen Sie daf&uuml;r das Werkzeug <a href="'.admin_url('tools.php?page=einsatzvw-tool-enr').'">Einsatznummern reparieren</a>.';
}


/**
 *
 */
function einsatzverwaltung_sanitize_einsatznummer_stellen($input)
{
    $val = intval($input);
    if(is_numeric($val) && $val > 0) {
        return $val;
    } else {
        return EINSATZVERWALTUNG__EINSATZNR_STELLEN;
    }
}


/**
 * Generiert den Inhalt der Einstellungsseite
 */
function einsatzverwaltung_settings_page()
{
    if ( ! current_user_can( 'manage_options' ) )
    wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
    
    echo '<div id="einsatzverwaltung_contactinfo">';
    echo '<h3>Entwicklerkontakt</h3>';
    echo 'Twitter: <a href="https://twitter.com/einsatzvw">@einsatzvw</a><br>';
    echo 'App.net: <a href="https://alpha.app.net/einsatzverwaltung">@einsatzverwaltung</a><br>';
    echo 'eMail: <a href="mailto:kontakt@abrain.de">kontakt@abrain.de</a>';
    echo '</div>';
    
    echo '<div class="wrap">';
    echo '<h2>Einstellungen &rsaquo; Einsatzverwaltung</h2>';
    
    echo '<form method="post" action="options.php">';
    echo settings_fields( 'einsatzvw_settings' );
    echo do_settings_sections( EVW_SETTINGS_SLUG );
    submit_button();
    echo '</form>';
}

?>