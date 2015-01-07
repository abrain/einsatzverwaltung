<?php

define( 'EVW_TOOL_WPE_SLUG', 'einsatzvw-tool-wpe' );
define( 'EVW_TOOL_WPE_DATE_COLUMN', 'Datum' );
define( 'EVW_TOOL_WPE_INPUT_NAME_PREFIX', 'evw_wpe_' );


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
    if ($wpdb->get_var("SHOW TABLES LIKE '$tablename'") != $tablename) {
        einsatzverwaltung_print_error('Die Tabelle, in der wp-einsatz seine Daten speichert, konnte nicht gefunden werden.');
    } else {
        if (array_key_exists('submit', $_POST) && array_key_exists('aktion', $_POST) && $_POST['aktion'] == 'analyse') {
            // Datenbank analysieren
            echo "<h3>Analyse</h3>";
            echo "<p>Die Daten von wp-einsatz werden analysiert...</p>";
            $felder = einsatzverwaltung_get_wpe_felder($tablename);
            if (empty($felder)) {
                einsatzverwaltung_print_error('Es wurden keine Felder in der Tabelle gefunden');
                return;
            }
            
            einsatzverwaltung_print_success('Es wurden folgende Felder gefunden: ' . implode($felder, ', '));
            
            // Auf Pflichtfelder prüfen
            if (!in_array(EVW_TOOL_WPE_DATE_COLUMN, $felder)) {
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
                if ($feld == EVW_TOOL_WPE_DATE_COLUMN) {
                    echo 'wird automatisch zugeordnet';
                } else {
                    echo einsatzverwaltung_dropdown_eigenefelder(EVW_TOOL_WPE_INPUT_NAME_PREFIX . strtolower($feld));
                }
                echo '</td></tr>';
            }
            echo '</tbody></table>';
            submit_button('Felder zuordnen');
            echo '</form>';
        } elseif (array_key_exists('submit', $_POST) && array_key_exists('aktion', $_POST) && $_POST['aktion'] == 'zuordnen') {
            echo '<h3>Vorschau</h3>';
            echo '<p>Bitte kontrollieren Sie hier, ob die Daten korrekt zugeordnet sind.</p>';
            
            global $evw_meta_fields, $evw_terms;
            $felder = einsatzverwaltung_get_wpe_felder($tablename);
            if (empty($felder)) {
                einsatzverwaltung_print_error('Es wurden keine Felder in der Tabelle gefunden');
                return;
            }
            foreach($felder as $feld) {
                $index = EVW_TOOL_WPE_INPUT_NAME_PREFIX . strtolower($feld);
                if (array_key_exists($index, $_POST)) {
                    $evw_feld_name = $_POST[$index];
                    if (!empty($evw_feld_name) && is_string($evw_feld_name)) {
                        if (array_key_exists($evw_feld_name, $evw_meta_fields)) {
                            einsatzverwaltung_print_info($evw_feld_name . " ist ein Metafeld");
                            // TODO
                        } elseif (array_key_exists($evw_feld_name, $evw_terms)) {
                            einsatzverwaltung_print_info($evw_feld_name . " ist ein Termfeld");
                            // TODO
                        } elseif ($evw_feld_name == '-') {
                            einsatzverwaltung_print_warning('Feld \'' . $feld . '\' nicht zugeordnet');
                        } else {
                            einsatzverwaltung_print_error('Feld \'' . $evw_feld_name . '\' unbekannt');
                        }
                    } else {
                        einsatzverwaltung_print_error('Feld \'' . $evw_feld_name . '\' ung&uuml;ltig');
                    }
                }
            }
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
    $string .= '<option value="-">nicht importieren</option>';
    foreach ( $felder as $slug => $name ) {
        $string .= '<option value="' . $slug . '">' . $name . '</option>';
    }
    $string .= '</select>';
        
    if ($echo === true) {
        echo $string;
    } else {
        return $string;
    }
}


/**
 * Gibt die Spaltennamen der wp-einsatz-Tabelle zurück
 * (ohne ID, Nr_Jahr und Nr_Monat)
 */
function einsatzverwaltung_get_wpe_felder($tablename)
{
    global $wpdb;
    $felder = array();
    foreach ( $wpdb->get_col( "DESC " . $tablename, 0 ) as $column_name ) {
        // Unwichtiges ignorieren
        if ($column_name == 'ID' || $column_name == 'Nr_Jahr' || $column_name == 'Nr_Monat') {
            continue;
        }
        
        $felder[] = $column_name;
    }
    return $felder;
}


/**
 * 
 */
function einsatzverwaltung_import_einsatz($einsatznummer)
{
    if (empty($post_id) || empty($einsatznummer)) {
        return;
    }
    
    $update_args = array();
    $update_args['post_name'] = $einsatznummer;
    $update_args['ID'] = $post_id;

    // keine Sonderbehandlung beim Speichern
    remove_action('save_post', 'einsatzverwaltung_save_postdata');
    wp_update_post( $update_args );
    add_action('save_post', 'einsatzverwaltung_save_postdata');
}
