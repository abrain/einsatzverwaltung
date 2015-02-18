<?php
use abrain\Einsatzverwaltung\Utilities;

/**
 * Erzeugt den neuen Beitragstyp Einsatzbericht und die zugehörigen Taxonomien
 */
function einsatzverwaltung_create_post_type()
{
    $args_einsatz = array(
        'labels' => array(
            'name' => 'Einsatzberichte',
            'singular_name' => 'Einsatzbericht',
            'menu_name' => 'Einsatzberichte',
            'add_new' => 'Neu',
            'add_new_item' => 'Neuer Einsatzbericht',
            'edit' => 'Bearbeiten',
            'edit_item' => 'Einsatzbericht bearbeiten',
            'new_item' => 'Neuer Einsatzbericht',
            'view' => 'Ansehen',
            'view_item' => 'Einsatzbericht ansehen',
            'search_items' => 'Einsatzberichte suchen',
            'not_found' => 'Keine Einsatzberichte gefunden',
            'not_found_in_trash' => 'Keine Einsatzberichte im Papierkorb gefunden'
        ),
        'public' => true,
        'has_archive' => true,
        'rewrite' => array(
            'slug' => 'einsaetze',
            'feeds' => true
        ),
        'supports' => array('title', 'editor', 'thumbnail', 'publicize', 'author'),
        'show_in_nav_menus' => false,
        'capability_type' => array('einsatzbericht', 'einsatzberichte'),
        'map_meta_cap' => true,
        'menu_position' => 5
    );
    if (Utilities::isMinWPVersion("3.9")) {
        $args_einsatz['menu_icon'] = 'dashicons-media-document';
    }
    register_post_type('einsatz', $args_einsatz);

    $args_einsatzart = array(
        'label' => 'Einsatzarten',
        'labels' => array(
            'name' => 'Einsatzarten',
            'singular_name' => 'Einsatzart',
            'menu_name' => 'Einsatzarten',
            'all_items' => 'Alle Einsatzarten',
            'edit_item' => 'Einsatzart bearbeiten',
            'view_item' => 'Einsatzart ansehen',
            'update_item' => 'Einsatzart aktualisieren',
            'add_new_item' => 'Neue Einsatzart',
            'new_item_name' => 'Einsatzart hinzuf&uuml;gen',
            'search_items' => 'Einsatzarten suchen',
            'popular_items' => 'H&auml;ufige Einsatzarten',
            'separate_items_with_commas' => 'Einsatzarten mit Kommata trennen',
            'add_or_remove_items' => 'Einsatzarten hinzuf&uuml;gen oder entfernen',
            'choose_from_most_used' => 'Aus h&auml;ufigen Einsatzarten w&auml;hlen'),
        'public' => true,
        'show_in_nav_menus' => false,
        'meta_box_cb' => 'einsatzverwaltung_display_einsatzart_metabox',
        'capabilities' => array (
            'manage_terms' => 'edit_einsatzberichte',
            'edit_terms' => 'edit_einsatzberichte',
            'delete_terms' => 'edit_einsatzberichte',
            'assign_terms' => 'edit_einsatzberichte'
        ),
        'hierarchical' => true
    );
    register_taxonomy('einsatzart', 'einsatz', $args_einsatzart);

    $args_fahrzeug = array(
        'label' => 'Fahrzeuge',
        'labels' => array(
            'name' => 'Fahrzeuge',
            'singular_name' => 'Fahrzeug',
            'menu_name' => 'Fahrzeuge',
            'all_items' => 'Alle Fahrzeuge',
            'edit_item' => 'Fahrzeug bearbeiten',
            'view_item' => 'Fahrzeug ansehen',
            'update_item' => 'Fahrzeug aktualisieren',
            'add_new_item' => 'Neues Fahrzeug',
            'new_item_name' => 'Fahrzeug hinzuf&uuml;gen',
            'search_items' => 'Fahrzeuge suchen',
            'popular_items' => 'Oft eingesetzte Fahrzeuge',
            'separate_items_with_commas' => 'Fahrzeuge mit Kommata trennen',
            'add_or_remove_items' => 'Fahrzeuge hinzuf&uuml;gen oder entfernen',
            'choose_from_most_used' => 'Aus h&auml;ufig eingesetzten Fahrzeugen w&auml;hlen'),
        'public' => true,
        'show_in_nav_menus' => false,
        'capabilities' => array (
            'manage_terms' => 'edit_einsatzberichte',
            'edit_terms' => 'edit_einsatzberichte',
            'delete_terms' => 'edit_einsatzberichte',
            'assign_terms' => 'edit_einsatzberichte'
        )
    );
    register_taxonomy('fahrzeug', 'einsatz', $args_fahrzeug);

    $argsExteinsatzmittel = array(
        'label' => 'Externe Einsatzmittel',
        'labels' => array(
            'name' => 'Externe Einsatzmittel',
            'singular_name' => 'Externes Einsatzmittel',
            'menu_name' => 'Externe Einsatzmittel',
            'all_items' => 'Alle externen Einsatzmittel',
            'edit_item' => 'Externes Einsatzmittel bearbeiten',
            'view_item' => 'Externes Einsatzmittel ansehen',
            'update_item' => 'Externes Einsatzmittel aktualisieren',
            'add_new_item' => 'Neues externes Einsatzmittel',
            'new_item_name' => 'Externes Einsatzmittel hinzuf&uuml;gen',
            'search_items' => 'Externe Einsatzmittel suchen',
            'popular_items' => 'Oft eingesetzte externe Einsatzmittel',
            'separate_items_with_commas' => 'Externe Einsatzmittel mit Kommata trennen',
            'add_or_remove_items' => 'Externe Einsatzmittel hinzuf&uuml;gen oder entfernen',
            'choose_from_most_used' => 'Aus h&auml;ufig eingesetzten externen Einsatzmitteln w&auml;hlen'),
        'public' => true,
        'show_in_nav_menus' => false,
        'capabilities' => array (
            'manage_terms' => 'edit_einsatzberichte',
            'edit_terms' => 'edit_einsatzberichte',
            'delete_terms' => 'edit_einsatzberichte',
            'assign_terms' => 'edit_einsatzberichte'
        ),
        'rewrite' => array(
            'slug' => 'externe-einsatzmittel'
        )
    );
    register_taxonomy('exteinsatzmittel', 'einsatz', $argsExteinsatzmittel);

    $args_alarmierungsart = array(
        'label' => 'Alarmierungsart',
        'labels' => array(
            'name' => 'Alarmierungsarten',
            'singular_name' => 'Alarmierungsart',
            'menu_name' => 'Alarmierungsarten',
            'all_items' => 'Alle Alarmierungsarten',
            'edit_item' => 'Alarmierungsart bearbeiten',
            'view_item' => 'Alarmierungsart ansehen',
            'update_item' => 'Alarmierungsart aktualisieren',
            'add_new_item' => 'Neue Alarmierungsart',
            'new_item_name' => 'Alarmierungsart hinzuf&uuml;gen',
            'search_items' => 'Alarmierungsart suchen',
            'popular_items' => 'H&auml;ufige Alarmierungsarten',
            'separate_items_with_commas' => 'Alarmierungsarten mit Kommata trennen',
            'add_or_remove_items' => 'Alarmierungsarten hinzuf&uuml;gen oder entfernen',
            'choose_from_most_used' => 'Aus h&auml;ufigen Alarmierungsarten w&auml;hlen'),
        'public' => true,
        'show_in_nav_menus' => false,
        'capabilities' => array (
            'manage_terms' => 'edit_einsatzberichte',
            'edit_terms' => 'edit_einsatzberichte',
            'delete_terms' => 'edit_einsatzberichte',
            'assign_terms' => 'edit_einsatzberichte'
        )
    );
    register_taxonomy('alarmierungsart', 'einsatz', $args_alarmierungsart);

    // more rewrite rules
    add_rewrite_rule($args_einsatz['rewrite']['slug'] . '/(\d{4})/page/(\d{1,})/?$', 'index.php?post_type=einsatz&year=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule($args_einsatz['rewrite']['slug'] . '/(\d{4})/?$', 'index.php?post_type=einsatz&year=$matches[1]', 'top');
}
add_action('init', 'einsatzverwaltung_create_post_type');


/**
 * @param $kalenderjahr
 *
 * @return array
 */
function einsatzverwaltung_get_einsatzberichte($kalenderjahr)
{
    if (empty($kalenderjahr) || strlen($kalenderjahr)!=4 || !is_numeric($kalenderjahr)) {
        $kalenderjahr = '';
    }

    return get_posts(array(
        'nopaging' => true,
        'orderby' => 'post_date',
        'order' => 'ASC',
        'post_type' => 'einsatz',
        'post_status' => 'publish',
        'year' => $kalenderjahr
    ));
}


/**
 * Berechnet die nächste freie Einsatznummer für das gegebene Jahr
 *
 * @param string $jahr
 * @param bool $minuseins Wird beim Speichern der zusätzlichen Einsatzdaten in einsatzverwaltung_save_postdata benötigt,
 * da der Einsatzbericht bereits gespeichert wurde, aber bei der Zählung für die Einsatznummer ausgelassen werden soll
 *
 * @return string Nächste freie Einsatznummer im angegebenen Jahr
 */
function einsatzverwaltung_get_next_einsatznummer($jahr, $minuseins = false)
{
    if (empty($jahr) || !is_numeric($jahr)) {
        $jahr = date('Y');
    }
    $query = new WP_Query('year=' . $jahr .'&post_type=einsatz&post_status=publish&nopaging=true');
    return einsatzverwaltung_format_einsatznummer($jahr, $query->found_posts + ($minuseins ? 0 : 1));
}


/**
 * Formatiert die Einsatznummer
 *
 * @param string $jahr Jahreszahl
 * @param int $nummer Laufende Nummer des Einsatzes im angegebenen Jahr
 *
 * @return string Formatierte Einsatznummer
 */
function einsatzverwaltung_format_einsatznummer($jahr, $nummer)
{
    $stellen = get_option('einsatzvw_einsatznummer_stellen', EINSATZVERWALTUNG__EINSATZNR_STELLEN);
    $lfdvorne = get_option('einsatzvw_einsatznummer_lfdvorne', false);
    if ($lfdvorne) {
        return str_pad($nummer, $stellen, "0", STR_PAD_LEFT).$jahr;
    } else {
        return $jahr.str_pad($nummer, $stellen, "0", STR_PAD_LEFT);
    }
}


/**
 * Zusätzliche Metadaten des Einsatzberichts speichern
 *
 * @param int $post_id ID des Posts
 */
function einsatzverwaltung_save_postdata($post_id)
{

    // verify if this is an auto save routine.
    // If it is our form has not been submitted, so we dont want to do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (array_key_exists('post_type', $_POST) && 'einsatz' == $_POST['post_type']) {
        // Prüfen, ob Aufruf über das Formular erfolgt ist
        if (
            !isset($_POST['einsatzverwaltung_nonce']) ||
            !wp_verify_nonce($_POST['einsatzverwaltung_nonce'], 'save_einsatz_details')
        ) {
            return;
        }

        // Schreibrechte prüfen
        if (!current_user_can('edit_einsatzbericht', $post_id)) {
            return;
        }

        $update_args = array();

        // Alarmzeit validieren
        $input_alarmzeit = sanitize_text_field($_POST['einsatzverwaltung_alarmzeit']);
        if (!empty($input_alarmzeit)) {
            $alarmzeit = date_create($input_alarmzeit);
        }
        if (empty($alarmzeit)) {
            $alarmzeit = date_create(
                sprintf(
                    '%s-%s-%s %s:%s:%s',
                    $_POST['aa'],
                    $_POST['mm'],
                    $_POST['jj'],
                    $_POST['hh'],
                    $_POST['mn'],
                    $_POST['ss']
                )
            );
        } else {
            $update_args['post_date'] = date_format($alarmzeit, 'Y-m-d H:i:s');
            $update_args['post_date_gmt'] = get_gmt_from_date($update_args['post_date']);
        }

        // Einsatznummer validieren
        $einsatzjahr = date_format($alarmzeit, 'Y');
        $einsatzNrFallback = einsatzverwaltung_get_next_einsatznummer($einsatzjahr, $einsatzjahr == date('Y'));
        $einsatznummer = sanitize_title($_POST['einsatzverwaltung_nummer'], $einsatzNrFallback, 'save');
        if (!empty($einsatznummer)) {
            $update_args['post_name'] = $einsatznummer; // Slug setzen
        }

        // Einsatzende validieren
        $input_einsatzende = sanitize_text_field($_POST['einsatzverwaltung_einsatzende']);
        if (!empty($input_einsatzende)) {
            $einsatzende = date_create($input_einsatzende);
        }
        if (empty($einsatzende)) {
            $einsatzende = "";
        }

        // Einsatzort validieren
        $einsatzort = sanitize_text_field($_POST['einsatzverwaltung_einsatzort']);

        // Einsatzleiter validieren
        $einsatzleiter = sanitize_text_field($_POST['einsatzverwaltung_einsatzleiter']);

        // Mannschaftsstärke validieren
        $mannschaftsstaerke = Utilities::sanitizePositiveNumber($_POST['einsatzverwaltung_mannschaft'], 0);

        // Fehlalarm validieren
        $fehlalarm = Utilities::sanitizeCheckbox(array($_POST, 'einsatzverwaltung_fehlalarm'));

        // Metadaten schreiben
        update_post_meta($post_id, 'einsatz_alarmzeit', date_format($alarmzeit, 'Y-m-d H:i'));
        update_post_meta($post_id, 'einsatz_einsatzende', ($einsatzende == "" ? "" : date_format($einsatzende, 'Y-m-d H:i')));
        update_post_meta($post_id, 'einsatz_einsatzort', $einsatzort);
        update_post_meta($post_id, 'einsatz_einsatzleiter', $einsatzleiter);
        update_post_meta($post_id, 'einsatz_mannschaft', $mannschaftsstaerke);
        update_post_meta($post_id, 'einsatz_fehlalarm', $fehlalarm);

        if (!empty($update_args)) {
            if (! wp_is_post_revision($post_id)) {
                $update_args['ID'] = $post_id;

                // unhook this function so it doesn't loop infinitely
                remove_action('save_post', 'einsatzverwaltung_save_postdata');

                // update the post, which calls save_post again
                wp_update_post($update_args);

                // re-hook this function
                add_action('save_post', 'einsatzverwaltung_save_postdata');
            }
        }
    }
}
add_action('save_post', 'einsatzverwaltung_save_postdata');


/**
 * Bestimmt die Einsatzart eines bestimmten Einsatzes. Ist nötig, weil die Taxonomie
 * 'einsatzart' mehrere Werte speichern kann, aber nur einer genutzt werden soll
 *
 * @param int $postId
 * @return object|bool
 */
function einsatzverwaltung_get_einsatzart($postId)
{
    $einsatzarten = get_the_terms($postId, 'einsatzart');
    if ($einsatzarten && !is_wp_error($einsatzarten) && !empty($einsatzarten)) {
        $keys = array_keys($einsatzarten);
        return $einsatzarten[$keys[0]];
    } else {
        return false;
    }
}


/**
 * Gibt die Einsatzart als String zurück, wenn vorhanden auch mit den übergeordneten Einsatzarten
 *
 * @param object $einsatzart
 * @param bool $make_links
 * @param bool $show_archive_links
 *
 * @return string
 */
function einsatzverwaltung_get_einsatzart_string($einsatzart, $make_links, $show_archive_links)
{
    $str = '';
    do {
        if (!empty($str)) {
            $str = ' > '.$str;
            $einsatzart = get_term($einsatzart->parent, 'einsatzart');
        }

        if ($make_links && $show_archive_links) {
            $str = '&nbsp;<a href="' . get_term_link($einsatzart) . '" class="fa fa-filter" style="text-decoration:none;" title="Alle Eins&auml;tze vom Typ '. $einsatzart->name . ' anzeigen"></a>' . $str;
        }
        $str = $einsatzart->name . $str;
    } while ($einsatzart->parent != 0);
    return $str;
}


/**
 * Gibt die Namen aller bisher verwendeten Einsatzleiter zurück
 *
 * @return array
 */
function einsatzverwaltung_get_einsatzleiter()
{
    /** @var wpdb $wpdb */
    global $wpdb;

    $names = array();
    $query = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'einsatz_einsatzleiter' AND meta_value <> ''";
    $results = $wpdb->get_results($query, OBJECT);

    foreach ($results as $result) {
        $names[] = $result->meta_value;
    }
    return $names;
}


/**
 * Gibt ein Array mit Jahreszahlen zurück, in denen Einsätze vorliegen
 */
function einsatzverwaltung_get_jahremiteinsatz()
{
    $jahre = array();
    $query = new WP_Query('&post_type=einsatz&post_status=publish&nopaging=true');
    while ($query->have_posts()) {
        $nextPost = $query->next_post();
        $timestamp = strtotime($nextPost->post_date);
        $jahre[date("Y", $timestamp)] = 1;
    }
    return array_keys($jahre);
}


/**
 * Gibt ein Array aller Felder und deren Namen zurück,
 * Hauptverwendungszweck ist das Mapping beim Import
 */
function einsatzverwaltung_get_fields()
{
    global $evw_meta_fields, $evw_terms, $evw_post_fields;
    return array_merge($evw_meta_fields, $evw_terms, $evw_post_fields);
}
