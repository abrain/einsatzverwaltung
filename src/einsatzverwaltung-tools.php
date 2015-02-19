<?php

define('EVW_TOOL_ENR_SLUG', 'einsatzvw-tool-enr');


/**
 * Fügt das Reparaturwerkzeug zum Menü hinzu
 */
function einsatzverwaltung_tools_menu()
{
    add_management_page('Einsatznummern reparieren', 'Einsatznummern reparieren', 'manage_options', EVW_TOOL_ENR_SLUG, 'einsatzverwaltung_tool_enr_page');
}
add_action('admin_menu', 'einsatzverwaltung_tools_menu');


/**
 * Gibt die Oberfläche des Werkzeugs "Einsatznummern reparieren" aus
 */
function einsatzverwaltung_tool_enr_page()
{
    echo '<div class="wrap">';
    echo '<h2>Einsatznummern reparieren</h2>';

    echo '<p>Dieses Werkzeug stellt sicher, dass alle Einsatznummern in korrekter Abfolge und Formatierung vorliegen.</p>';

    $simulieren = array_key_exists('evw_tool_enr_sim', $_POST) && $_POST['evw_tool_enr_sim'] == 1;
    $jahr = (array_key_exists('jahr', $_POST) ? $_POST['jahr'] : '');

    echo '<form method="post">';
    echo '<label for"jahr">Einsatznummern reparieren für Jahr:</label>&nbsp;<select name="jahr">';
    echo '<option value="all">alle</option>';
    $jahre = einsatzverwaltung_get_jahremiteinsatz();
    foreach ($jahre as $j) {
        echo '<option value="'.$j.'">'.$j.'</option>';
    }
    echo '</select><br>';
    echo '<input type="checkbox" name="evw_tool_enr_sim" value="1" checked="checked" />&nbsp;<label for="evw_tool_enr_sim">Simulieren (zeigt nur, was sich ändern würde)</label>';
    submit_button('Starten');
    echo '</form>';

    if (array_key_exists('submit', $_POST) && $_POST['submit'] == 'Starten') {
        einsatzverwaltung_enr_vergeben($jahr, $simulieren);
    }
}

/**
 * Stellt korrekte Abfolge und Formatierung der Einsatznummern sicher
 *
 * @param string $kalenderjahr
 * @param bool $simulieren
 */
function einsatzverwaltung_enr_vergeben($kalenderjahr, $simulieren = false)
{
    if ($simulieren) {
        echo '<h3>Simulation</h3>';
        echo '<p>Die folgenden &Auml;nderungen w&uuml;rden bei einer Reparatur angewendet:</p>';
    } else {
        echo '<h3>Reparatur</h3>';
        echo '<p>Die folgenden &Auml;nderungen werden angewendet:</p>';
    }

    $einsatzberichte = einsatzverwaltung_get_einsatzberichte($kalenderjahr);

    $format = get_option('date_format', 'd.m.Y').' '.get_option('time_format', 'H:i');
    $jahr_alt = '';
    $aenderungen = 0;
    $kollisionen = 0;
    $counter = 1;
    foreach ($einsatzberichte as $einsatzbericht) {
        // Zähler beginnt jedes Jahr von neuem
        $datum = date_create($einsatzbericht->post_date);
        $jahr = date_format($datum, "Y");
        if ($jahr_alt != $jahr) {
            $counter = 1;
        }

        // Den Einsatzbericht nur aktualisieren, wenn sich die Einsatznummer ändert
        $enr = $einsatzbericht->post_name;
        $enr_neu = einsatzverwaltung_format_einsatznummer($jahr, $counter);
        if ($enr != $enr_neu) {
            $aenderungen++;
            printf('Einsatz %s (%s) erh&auml;lt die Nummer %s', '<strong>'.$enr.'</strong>', date_i18n($format, date_timestamp_get($datum)), '<strong>'.$enr_neu.'</strong>');
            if (!$simulieren) {
                einsatzverwaltung_set_einsatznummer($einsatzbericht->ID, $enr_neu);
                $enr_neu_slug = get_post_field('post_name', $einsatzbericht->ID);
                printf(' ... ge&auml;ndert zu %s', '<strong>'.$enr_neu_slug.'</strong>');
                if ($enr_neu_slug != $enr_neu) {
                    $kollisionen++;
                    print(' *');
                }
            }
            echo '<br/>';
        }
        $jahr_alt = $jahr;
        $counter++;
    }

    if ($aenderungen == 0) {
        if ($simulieren) {
            echo 'Keine &Auml;nderungen erforderlich.';
        } else {
            echo 'Keine &Auml;nderungen vorgenommen.';
        }
    }

    if ($kollisionen != 0) {
        echo '<br>* = Die vorgesehene Einsatznummer war zum Zeitpunkt des Abspeicherns noch von einem anderen Einsatzbericht belegt, deshalb wurde von WordPress automatisch eine unbelegte Nummer vergeben. Mit einem weiteren Durchlauf dieses Werkzeugs wird dieser Zustand korrigiert.';
    } else {
        if (!$simulieren && $aenderungen > 0) {
            echo '<br>Die Einsatznummern wurden ohne Probleme repariert.';
        }
    }
}


/**
 * Ändert die Einsatznummer eines bestehenden Einsatzes
 *
 * @param int $post_id ID des Einsatzberichts
 * @param string $einsatznummer Einsatznummer
 */
function einsatzverwaltung_set_einsatznummer($post_id, $einsatznummer)
{
    if (empty($post_id) || empty($einsatznummer)) {
        return;
    }

    $update_args = array();
    $update_args['post_name'] = $einsatznummer;
    $update_args['ID'] = $post_id;

    // keine Sonderbehandlung beim Speichern
    remove_action('save_post', 'einsatzverwaltung_save_postdata');
    wp_update_post($update_args);
    add_action('save_post', 'einsatzverwaltung_save_postdata');
}
