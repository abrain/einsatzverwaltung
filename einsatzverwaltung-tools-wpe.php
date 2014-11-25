<?php

define( 'EVW_TOOL_WPE_SLUG', 'einsatzvw-tool-wpe' );
define( 'EVW_TOOL_WPE_DATE_COLUMN', 'Datum' );


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
        einsatzverwaltung_print_error('Die Tabelle, in der wp-einsatz seine Daten speichert, konnte nicht gefunden werden.');
    } else {
        if(array_key_exists('submit', $_POST) && array_key_exists('aktion', $_POST) && $_POST['aktion'] == 'analyse') {
            // Datenbank analysieren
            echo "<h3>Analyse</h3>";
            echo "Die Daten von wp-einsatz werden analysiert...<br><br>";
            $felder = array();
            foreach ( $wpdb->get_col( "DESC " . $tablename, 0 ) as $column_name ) {
                // Unwichtiges ignorieren
                if($column_name == 'ID' || $column_name == 'Nr_Jahr' || $column_name == 'Nr_Monat') {
                    continue;
                }
                
                echo 'Feld <strong>' . $column_name . '</strong> gefunden<br>';
                $felder[] = $column_name;
            }
            
            // Auf Pflichtfelder prüfen
            if(!in_array(EVW_TOOL_WPE_DATE_COLUMN, $felder)) {
                echo '<br>';
                einsatzverwaltung_print_error('Das Feld "'.EVW_TOOL_WPE_DATE_COLUMN.'" konnte nicht in der Datenbank gefunden werden!');
                return;
            }
            
            // Felder matchen
            echo "<h3>Felder zuordnen</h3>";
            $eigenefelder = array('');
            echo '<form method="post">';
            echo '<input type="hidden" name="aktion" value="zuordnen" />';
            echo '<table><tr><th>Feld in wp-einsatz</th><th>Feld in Einsatzverwaltung</th></tr><tbody>';
            foreach($felder as $feld) {
                echo '<tr><td><strong>' . $feld . '</strong></td><td>';
                if($feld == EVW_TOOL_WPE_DATE_COLUMN) {
                    echo 'wird automatisch zugeordnet';
                } else {
                    echo einsatzverwaltung_dropdown_eigenefelder('evw_wpe_' . strtolower($feld));
                }
                echo '</td></tr>';
            }
            echo '</tbody></table>';
            submit_button('Felder zuordnen');
            echo '</form>';
        } else if(array_key_exists('submit', $_POST) && array_key_exists('aktion', $_POST) && $_POST['aktion'] == 'zuordnen') {
            echo "<h3>Import</h3>";
            // TODO Vorschau des Mappings
            print_r($_POST);
        } else {
            einsatzverwaltung_print_success('Die Tabelle, in der wp-einsatz seine Daten speichert, wurde gefunden. Analyse jetzt starten?');
            echo '<form method="post">';
            echo '<input type="hidden" name="aktion" value="analyse" />';
            submit_button('Analyse starten');
            echo '</form>';
        }
    }
}



function einsatzverwaltung_dropdown_eigenefelder($name, $echo = false)
{
    global $evw_meta_fields, $evw_terms;
    
    $felder = array_merge($evw_meta_fields, $evw_terms);
    asort($felder);
    $string = '';
    $string .= '<select name="' . $name . '">';
    $string .= '<option value="-">-</option>';
    foreach ( $felder as $slug => $name ) {
        $string .= '<option value="' . $slug . '">' . $name . '</option>';
    }
    $string .= '</select>';
        
    if($echo === true) {
        echo $string;
    } else {
        return $string;
    }
}

?>