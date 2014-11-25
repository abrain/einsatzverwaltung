<?php

define( 'EVW_TOOL_WPE_SLUG', 'einsatzvw-tool-wpe' );


/**
 * Fügt das Werkzeug für wp-einsatz zum Menü hinzu
 */
function einsatzverwaltung_tool_wpe_menu()
{
    add_management_page('wp-einsatz Import', 'wp-einsatz Import', 'manage_options', EVW_TOOL_WPE_SLUG, 'einsatzverwaltung_tool_wpe_page');
}
add_action('admin_menu', 'einsatzverwaltung_tool_wpe_menu');


/**
 * 
 */
function einsatzverwaltung_tool_wpe_page()
{
    global $wpdb;
    echo '<div class="wrap">';
    echo '<h2>Import von wp-einsatz</h2>';
    
    echo '<p>Dieses Werkzeug importiert Einsätze aus wp-einsatz.</p>';
    
    // Existenz der wp-einsatz Datenbank feststellen
    $tablename = $wpdb->prefix . "einsaetze";
    if($wpdb->get_var("SHOW TABLES LIKE '$tablename'") != $tablename) {
        echo '<span class="evw_error"><i class="fa fa-exclamation-circle"></i>&nbsp;Die Tabelle, in der wp-einsatz seine Daten speichert, konnte nicht gefunden werden.</span>';
    } else {
        if(array_key_exists('submit', $_POST) && array_key_exists('aktion', $_POST) && $_POST['aktion'] == 'analyse') {
            // Datenbank analysieren
            echo "<h3>Analyse</h3>";
            echo "Die Daten von wp-einsatz werden analysiert...<br><br>";
            foreach ( $wpdb->get_col( "DESC " . $tablename, 0 ) as $column_name ) {
                // Unwichtiges ignorieren
                if($column_name == 'ID' || $column_name == 'Nr_Jahr' || $column_name == 'Nr_Monat') {
                    continue;
                }
                
                echo 'Feld <strong>' . $column_name . '</strong> gefunden<br>';
            }
            
            // TODO Felder matchen
            // TODO Import starten
        } else {
            echo '<span class="evw_success"><i class="fa fa-check-circle"></i>&nbsp;Die Tabelle, in der wp-einsatz seine Daten speichert, wurde gefunden. Analyse jetzt starten?</span>';
            echo '<form method="post">';
            echo '<input type="hidden" name="aktion" value="analyse" />';
            submit_button('Analyse starten');
            echo '</form>';
        }
    }
}

?>