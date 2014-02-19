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
 *
 */
function einsatzverwaltung_register_settings()
{
    register_setting( 'my_options_group', 'my_option_name', 'intval' );
    register_setting( 'my_options_group', 'my_option_bla', 'intval' );
    register_setting( 'my_options_group', 'my_option_ble', 'intval' );
}
add_action( 'admin_init', 'einsatzverwaltung_register_settings' );


/**
 * Fügt die Tabs für den Wechsel zwischen den verschiedenen Einstellungskategorien ein
 */
function einsatzverwaltung_settings_tabs($current = 'general')
{
    echo '<h2 class="nav-tab-wrapper">';
    $tabs = array(
        'general' => 'Allgemein',
        'view' => 'Darstellung'
    );
    
    foreach ( $tabs as $tab => $name ) {

        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
        $url = add_query_arg( array(
            'page' => EVW_SETTINGS_SLUG,
            'tab' => $tab
        ), admin_url( 'options-general.php' ) );
        echo '<a class="nav-tab' . esc_attr( $class ) . '" href="' . $url . '">' . esc_html( $name ) . '</a>';

    }
    echo '</h2>';
}


/**
 * Generiert den Inhalt der Einstellungsseite
 */
function einsatzverwaltung_settings_page()
{
    if ( ! current_user_can( 'manage_options' ) )
    wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );
    
    echo '<div class="wrap">';
    echo '<h2>Einstellungen &rsaquo; Einsatzverwaltung</h2>';
    
    $current_tab = (isset( $_GET['tab'] ) ? $_GET['tab'] : 'general');
    einsatzverwaltung_settings_tabs($current_tab);
    
    switch ($current_tab) {
        case 'general':
            einsatzverwaltung_settings_page_general();
            break;
        case 'view':
            einsatzverwaltung_settings_page_view();
            break;
        default:
            einsatzverwaltung_settings_page_general();
    }
}


/**
 * Gibt den Tab 'Allgemein' aus
 */
function einsatzverwaltung_settings_page_general()
{
    //
}


/**
 * Gibt den Tab 'Darstellung' aus
 */
function einsatzverwaltung_settings_page_view()
{
    //
}

?>