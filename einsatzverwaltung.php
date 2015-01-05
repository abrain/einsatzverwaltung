<?php
/*
Plugin Name: Einsatzverwaltung
Plugin URI: http://www.abrain.de/software/einsatzverwaltung/
Description: Verwaltung von Feuerwehreins&auml;tzen
Version: 0.7.1
Author: Andreas Brain
Author URI: http://www.abrain.de
License: GPLv2
Text Domain: einsatzverwaltung
*/

check_php_version('5.3.0');

define( 'EINSATZVERWALTUNG__PLUGIN_BASE', plugin_basename(__FILE__) );
define( 'EINSATZVERWALTUNG__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EINSATZVERWALTUNG__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EINSATZVERWALTUNG__SCRIPT_URL', EINSATZVERWALTUNG__PLUGIN_URL . 'js/' );
define( 'EINSATZVERWALTUNG__STYLE_URL', EINSATZVERWALTUNG__PLUGIN_URL . 'css/' );
define( 'EINSATZVERWALTUNG__DBVERSION_OPTION', 'einsatzvw_db_version');

// Standardwerte
define( 'EINSATZVERWALTUNG__EINSATZNR_STELLEN', 3 );
define( 'EINSATZVERWALTUNG__D__SHOW_EXTEINSATZMITTEL_ARCHIVE', false );
define( 'EINSATZVERWALTUNG__D__SHOW_EINSATZART_ARCHIVE', false );
define( 'EINSATZVERWALTUNG__D__SHOW_FAHRZEUG_ARCHIVE', false );
define( 'EINSATZVERWALTUNG__D__HIDE_EMPTY_DETAILS', true );
define( 'EINSATZVERWALTUNG__D__SHOW_LINKS_IN_EXCERPT', false );

require_once( EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-widget.php' );
require_once( EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-shortcodes.php' );
require_once( EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-settings.php' );
require_once( EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-tools.php' );
require_once( EINSATZVERWALTUNG__PLUGIN_DIR . 'einsatzverwaltung-taxonomies.php' );

global $evw_db_version;
$evw_db_version = 2;

global $evw_caps;
$evw_caps = array(
    'edit_einsatzberichte',
    'edit_private_einsatzberichte',
    'edit_published_einsatzberichte',
    'edit_others_einsatzberichte',
    'publish_einsatzberichte',
    'read_private_einsatzberichte',
    'delete_einsatzberichte',
    'delete_private_einsatzberichte',
    'delete_published_einsatzberichte',
    'delete_others_einsatzberichte'
);

global $evw_meta_fields;
$evw_meta_fields = array(
    'einsatz_einsatzort' => 'Einsatzort',
    'einsatz_einsatzleiter' => 'Einsatzleiter',
    'einsatz_einsatzende' => 'Einsatzende',
    'einsatz_fehlalarm' => 'Fehlalarm',
    'einsatz_mannschaft' => 'Mannschaftsstärke'
);

global $evw_terms;
$evw_terms = array(
    'alarmierungsart' => 'Alarmierungsart',
    'einsatzart' => 'Einsatzart',
    'fahrzeug' => 'Fahrzeuge',
    'exteinsatzmittel' => 'Externe Einsatzmittel'
);

/**
 * Erzeugt den neuen Beitragstyp Einsatzbericht und die zugehörigen Taxonomien
 */
function einsatzverwaltung_create_post_type() {
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
        'supports' => array('title', 'editor', 'thumbnail', 'publicize'),
        'show_in_nav_menus' => false,
        'capability_type' => array('einsatzbericht', 'einsatzberichte'),
        'map_meta_cap' => true,
        'menu_position' => 5
    );
    if(einsatzverwaltung_is_min_wp_version("3.9")) {
        $args_einsatz['menu_icon'] = 'dashicons-media-document';
    }
    register_post_type( 'einsatz', $args_einsatz);
    
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
    register_taxonomy( 'einsatzart', 'einsatz', $args_einsatzart );
    
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
    register_taxonomy( 'fahrzeug', 'einsatz', $args_fahrzeug );
    
    $args_exteinsatzmittel = array(
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
    register_taxonomy( 'exteinsatzmittel', 'einsatz', $args_exteinsatzmittel );
    
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
    register_taxonomy( 'alarmierungsart', 'einsatz', $args_alarmierungsart );
    
    // more rewrite rules
    add_rewrite_rule($args_einsatz['rewrite']['slug'] . '/(\d{4})/page/(\d{1,})/?$', 'index.php?post_type=einsatz&year=$matches[1]&paged=$matches[2]', 'top');
    add_rewrite_rule($args_einsatz['rewrite']['slug'] . '/(\d{4})/?$', 'index.php?post_type=einsatz&year=$matches[1]', 'top');
}
add_action( 'init', 'einsatzverwaltung_create_post_type' );


/**
 * Wird beim Aktivieren des Plugins aufgerufen
 */
function einsatzverwaltung_aktivierung() {
    // Posttypen registrieren
    einsatzverwaltung_create_post_type();

    // Permalinks aktualisieren
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'einsatzverwaltung_aktivierung' );


/**
 *
 */
function einsatzverwaltung_on_plugins_loaded()
{
    // Sicherstellen, dass Optionen gesetzt sind
    add_option( 'einsatzvw_einsatznummer_stellen', EINSATZVERWALTUNG__EINSATZNR_STELLEN, '', 'no' );
}
add_action( 'plugins_loaded', 'einsatzverwaltung_on_plugins_loaded' );


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
 * Fügt die Metabox zum Bearbeiten der Einsatzdetails ein
 */
function einsatzverwaltung_add_einsatzdetails_meta_box( $post ) {
    add_meta_box( 'einsatzverwaltung_meta_box',
        'Einsatzdetails',
        'einsatzverwaltung_display_meta_box',
        'einsatz', 'normal', 'high'
    );
}
add_action( 'add_meta_boxes_einsatz', 'einsatzverwaltung_add_einsatzdetails_meta_box' );


/**
 * Zusätzliche Skripte im Admin-Bereich einbinden
 */
function einsatzverwaltung_enqueue_edit_scripts($hook) {
    if( 'post.php' == $hook || 'post-new.php' == $hook ) {
        // Nur auf der Bearbeitungsseite anzeigen
        wp_enqueue_script('einsatzverwaltung-edit-script', EINSATZVERWALTUNG__SCRIPT_URL . 'einsatzverwaltung-edit.js', array('jquery'));
        wp_enqueue_style('einsatzverwaltung-edit', EINSATZVERWALTUNG__STYLE_URL . 'style-edit.css');
    }
    
    wp_enqueue_style( 'einsatzverwaltung-fontawesome', EINSATZVERWALTUNG__PLUGIN_URL . 'font-awesome/css/font-awesome.min.css' );
    wp_enqueue_style('einsatzverwaltung-admin', EINSATZVERWALTUNG__STYLE_URL . 'style-admin.css');
}
add_action( 'admin_enqueue_scripts', 'einsatzverwaltung_enqueue_edit_scripts' );


function einsatzverwaltung_enqueue_frontend_style() {
    wp_enqueue_style( 'einsatzverwaltung-fontawesome', EINSATZVERWALTUNG__PLUGIN_URL . 'font-awesome/css/font-awesome.min.css' );
}
add_action( 'wp_enqueue_scripts', 'einsatzverwaltung_enqueue_frontend_style' );


/**
 * Inhalt der Metabox zum Bearbeiten der Einsatzdetails
 */
function einsatzverwaltung_display_meta_box( $post ) {
    // Use nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'einsatzverwaltung_nonce' );

    // The actual fields for data entry
    // Use get_post_meta to retrieve an existing value from the database and use the value for the form
    $nummer = get_post_field('post_name', $post->ID);
    $alarmzeit = get_post_meta( $post->ID, $key = 'einsatz_alarmzeit', $single = true );
    $einsatzende = get_post_meta( $post->ID, $key = 'einsatz_einsatzende', $single = true );
    $einsatzort = get_post_meta( $post->ID, $key = 'einsatz_einsatzort', $single = true );
    $einsatzleiter = get_post_meta( $post->ID, $key = 'einsatz_einsatzleiter', $single = true );
    $fehlalarm = get_post_meta( $post->ID, $key = 'einsatz_fehlalarm', $single = true );
    $mannschaftsstaerke = get_post_meta( $post->ID, $key = 'einsatz_mannschaft', $single = true );

    echo '<table><tbody>';

    echo '<tr><td><label for="einsatzverwaltung_nummer">' . __("Einsatznummer", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_nummer" name="einsatzverwaltung_nummer" value="'.esc_attr($nummer).'" size="10" placeholder="'.einsatzverwaltung_get_next_einsatznummer(date('Y')).'" /></td></tr>';
    
    echo '<tr><td><label for="einsatzverwaltung_alarmzeit">'. __("Alarmzeit", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_alarmzeit" name="einsatzverwaltung_alarmzeit" value="'.esc_attr($alarmzeit).'" size="20" placeholder="JJJJ-MM-TT hh:mm" />&nbsp;<span class="einsatzverwaltung_hint" id="einsatzverwaltung_alarmzeit_hint"></span></td></tr>';

    echo '<tr><td><label for="einsatzverwaltung_einsatzende">'. __("Einsatzende", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_einsatzende" name="einsatzverwaltung_einsatzende" value="'.esc_attr($einsatzende).'" size="20" placeholder="JJJJ-MM-TT hh:mm" />&nbsp;<span class="einsatzverwaltung_hint" id="einsatzverwaltung_einsatzende_hint"></span></td></tr>';
    
    echo '<tr><td><label for="einsatzverwaltung_fehlalarm">'. __("Fehlalarm", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="checkbox" id="einsatzverwaltung_fehlalarm" name="einsatzverwaltung_fehlalarm" value="1" ' . einsatzverwaltung_checked($fehlalarm) . '/></td></tr>';
    
    echo '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
    
    echo '<tr><td><label for="einsatzverwaltung_einsatzort">'. __("Einsatzort", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_einsatzort" name="einsatzverwaltung_einsatzort" value="'.esc_attr($einsatzort).'" size="20" /></td></tr>';
    
    echo '<tr><td><label for="einsatzverwaltung_einsatzleiter">'. __("Einsatzleiter", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_einsatzleiter" name="einsatzverwaltung_einsatzleiter" value="'.esc_attr($einsatzleiter).'" size="20" /></td></tr>';
    
    echo '<tr><td><label for="einsatzverwaltung_mannschaft">'. __("Mannschaftsst&auml;rke", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_mannschaft" name="einsatzverwaltung_mannschaft" value="'.esc_attr($mannschaftsstaerke).'" size="20" /></td></tr>';
    
    echo '</tbody></table>';
}


/**
 * Berechnet die nächste freie Einsatznummer für das gegebene Jahr
 */
function einsatzverwaltung_get_next_einsatznummer($jahr, $minuseins = false) {
    if(empty($jahr) || !is_numeric($jahr)) {
        $jahr = date('Y');
    }
    $query = new WP_Query( 'year=' . $jahr .'&post_type=einsatz&post_status=publish&nopaging=true' );
    return einsatzverwaltung_format_einsatznummer($jahr, $query->found_posts + ($minuseins ? 0 : 1));
}


/**
 * Formatiert die Einsatznummer
 */
function einsatzverwaltung_format_einsatznummer($jahr, $nummer)
{
    $stellen = get_option('einsatzvw_einsatznummer_stellen', EINSATZVERWALTUNG__EINSATZNR_STELLEN);
    $lfdvorne = get_option('einsatzvw_einsatznummer_lfdvorne', false);
    if($lfdvorne) {
        return str_pad($nummer, $stellen, "0", STR_PAD_LEFT).$jahr;
    } else {
        return $jahr.str_pad($nummer, $stellen, "0", STR_PAD_LEFT);
    }
}


/**
 * Zusätzliche Metadaten des Einsatzberichts speichern
 */
function einsatzverwaltung_save_postdata( $post_id ) {

    // verify if this is an auto save routine. 
    // If it is our form has not been submitted, so we dont want to do anything
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
        return;

    if ( array_key_exists('post_type', $_POST) && 'einsatz' == $_POST['post_type'] ) {
        
        // Prüfen, ob Aufruf über das Formular erfolgt ist
        if ( !isset( $_POST['einsatzverwaltung_nonce'] ) || !wp_verify_nonce( $_POST['einsatzverwaltung_nonce'], plugin_basename( __FILE__ ) ) ) {
            return;
        }
        
        // Schreibrechte prüfen
        if ( !current_user_can( 'edit_einsatzbericht', $post_id ) ) {
            return;
        }
        
        $update_args = array();
        
        // Alarmzeit validieren
        $input_alarmzeit = sanitize_text_field( $_POST['einsatzverwaltung_alarmzeit'] );
        if(!empty($input_alarmzeit)) {
            $alarmzeit = date_create($input_alarmzeit);
        }
        if(empty($alarmzeit)) {
            $alarmzeit = date_create($_POST['aa'].'-'.$_POST['mm'].'-'.$_POST['jj'].' '.$_POST['hh'].':'.$_POST['mn'].':'.$_POST['ss']);
        } else {
            $update_args['post_date'] = date_format($alarmzeit, 'Y-m-d H:i:s');
            $update_args['post_date_gmt'] = get_gmt_from_date( $update_args['post_date'] );
        }

        // Einsatznummer validieren
        $einsatzjahr = date_format($alarmzeit, 'Y');
        $einsatznummer_fallback = einsatzverwaltung_get_next_einsatznummer($einsatzjahr, $einsatzjahr == date('Y'));
        $einsatznummer = sanitize_title( $_POST['einsatzverwaltung_nummer'], $einsatznummer_fallback, 'save' );
        if(!empty($einsatznummer)) {
            $update_args['post_name'] = $einsatznummer; // Slug setzen
        }

        // Einsatzende validieren
        $input_einsatzende = sanitize_text_field( $_POST['einsatzverwaltung_einsatzende'] );
        if(!empty($input_einsatzende)) {
            $einsatzende = date_create($input_einsatzende);
        }
        if(empty($einsatzende)) {
            $einsatzende = "";
        }
        
        // Einsatzort validieren
        $einsatzort = sanitize_text_field( $_POST['einsatzverwaltung_einsatzort'] );
        
        // Einsatzleiter validieren
        $einsatzleiter = sanitize_text_field( $_POST['einsatzverwaltung_einsatzleiter'] );
        
        // Mannschaftsstärke validieren
        $mannschaftsstaerke = einsatzverwaltung_sanitize_pos_number( $_POST['einsatzverwaltung_mannschaft'] , 0 );
        
        // Fehlalarm validieren
        $fehlalarm = einsatzverwaltung_sanitize_checkbox(array($_POST, 'einsatzverwaltung_fehlalarm'));
        
        // Metadaten schreiben
        update_post_meta($post_id, 'einsatz_alarmzeit', date_format($alarmzeit, 'Y-m-d H:i'));
        update_post_meta($post_id, 'einsatz_einsatzende', ($einsatzende == "" ? "" : date_format($einsatzende, 'Y-m-d H:i')));
        update_post_meta($post_id, 'einsatz_einsatzort', $einsatzort);
        update_post_meta($post_id, 'einsatz_einsatzleiter', $einsatzleiter);
        update_post_meta($post_id, 'einsatz_mannschaft', $mannschaftsstaerke);
        update_post_meta($post_id, 'einsatz_fehlalarm', $fehlalarm);
        
        if(!empty($update_args)) {
            if ( ! wp_is_post_revision( $post_id ) ) {
                $update_args['ID'] = $post_id;
            
                // unhook this function so it doesn't loop infinitely
                remove_action('save_post', 'einsatzverwaltung_save_postdata');
                
                // update the post, which calls save_post again
                wp_update_post( $update_args );
                
                // re-hook this function
                add_action('save_post', 'einsatzverwaltung_save_postdata');
            }
        }
    }
}
add_action( 'save_post', 'einsatzverwaltung_save_postdata' );


/**
 * Bereitet den Formularwert einer Checkbox für das Speichern in der Datenbank vor
 */
function einsatzverwaltung_sanitize_checkbox($input)
{
    if(is_array($input)) {
        $arr = $input[0];
        $index = $input[1];
        $value = (array_key_exists($index, $arr) ? $arr[$index] : "");
    } else {
        $value = $input;
    }
    
    if(isset($value) && $value == "1") {
        return 1;
    } else {
        return 0;
    }
}


/**
 * 
 */
function einsatzverwaltung_sanitize_pos_number($input, $defaultvalue = 0)
{
    $val = intval($input);
    if(is_numeric($val) && $val >= 0) {
        return $val;
    } else {
        return $defaultvalue;
    }
}


/**
 * 
 */
function einsatzverwaltung_checked($value)
{
    return ($value == 1 ? 'checked="checked" ' : '');
}


/**
 * Zeigt die Metabox für die Einsatzart
 */
function einsatzverwaltung_display_einsatzart_metabox( $post ) {
    $einsatzart = einsatzverwaltung_get_einsatzart($post->ID);
    einsatzverwaltung_dropdown_einsatzart($einsatzart ? $einsatzart->term_id : 0);
}


/**
 * Zeigt Dropdown mit Hierarchie für die Einsatzart
 */
function einsatzverwaltung_dropdown_einsatzart($selected) {
    wp_dropdown_categories(array(
        'show_option_all'    => '',
        'show_option_none'   => '- keine -',
        'orderby'            => 'NAME   ', 
        'order'              => 'ASC',
        'show_count'         => false,
        'hide_empty'         => false, 
        'echo'               => true,
        'selected'           => $selected,
        'hierarchical'       => true,
        'name'               => 'tax_input[einsatzart]',
        'taxonomy'           => 'einsatzart',
        'hide_if_empty'      => false
    ));
}


/**
 * Erzeugt den Kopf eines Einsatzberichts
 */
function einsatzverwaltung_get_einsatzbericht_header($post, $may_contain_links = true) {
    if(get_post_type($post) == "einsatz") {
        $make_links = $may_contain_links;
        
        $alarmzeit = get_post_meta($post->ID, 'einsatz_alarmzeit', true);
        $einsatzende = get_post_meta($post->ID, 'einsatz_einsatzende', true);
        
        $alarmierungsart = get_the_terms( $post->ID, 'alarmierungsart' );
        if ( $alarmierungsart && ! is_wp_error( $alarmierungsart ) ) {
            $alarm_namen = array();
            foreach ( $alarmierungsart as $alarmart ) {
                $alarm_namen[] = $alarmart->name;
            }
            $alarm_string = join( ", ", $alarm_namen );
        } else {
            $alarm_string = '';
        }
        
        $dauerstring = "?";
        if(!empty($alarmzeit) && !empty($einsatzende)) {
            $timestamp1 = strtotime($alarmzeit);
            $timestamp2 = strtotime($einsatzende);
            $differenz = $timestamp2 - $timestamp1;
            $dauer = intval($differenz / 60);
            
            if(empty($dauer) || !is_numeric($dauer)) {
                $dauerstring = '';
            } else {
                if($dauer <= 0) {
                    $dauerstring = '';
                } else if($dauer < 60) {
                    $dauerstring = $dauer." Minuten";
                } else {
                    $dauer_h = intval($dauer / 60);
                    $dauer_m = $dauer % 60;
                    $dauerstring = $dauer_h." Stunde".($dauer_h > 1 ? "n" : "");
                    if($dauer_m > 0) {
                        $dauerstring .= " ".$dauer_m." Minute".($dauer_m > 1 ? "n" : "");
                    }
                }
            }
        } else {
            $dauerstring = '';
        }
        
        $einsatzart = einsatzverwaltung_get_einsatzart($post->ID);
        if($einsatzart) {
            $art = '';
            do {
                if(!empty($art)) {
                    $art = ' > '.$art;
                    $einsatzart = get_term($einsatzart->parent, 'einsatzart');
                }
                
                if($make_links && get_option('einsatzvw_show_einsatzart_archive', EINSATZVERWALTUNG__D__SHOW_EINSATZART_ARCHIVE)) {
                    $art = '&nbsp;<a href="'.get_term_link($einsatzart).'" class="fa fa-filter" style="text-decoration:none;" title="Alle Eins&auml;tze vom Typ '.$einsatzart->name.' anzeigen"></a>' . $art;
                }
                $art = $einsatzart->name . $art;
            } while ($einsatzart->parent != 0);
        } else {
            $art = '';
        }
        
        $fehlalarm = get_post_meta( $post->ID, $key = 'einsatz_fehlalarm', $single = true );
        if(empty($fehlalarm)) {
            $fehlalarm = 0;
        }
        if($fehlalarm == 1) {
            $art = (empty($art) ? 'Fehlalarm' : $art.' (Fehlalarm)');
        }
        
        $einsatzort = get_post_meta( $post->ID, $key = 'einsatz_einsatzort', $single = true );
        
        $einsatzleiter = get_post_meta( $post->ID, $key = 'einsatz_einsatzleiter', $single = true );
        
        $mannschaft = get_post_meta( $post->ID, $key = 'einsatz_mannschaft', $single = true );
        if(empty($mannschaft)) {
            $mannschaft = 0;
        }
        
        $fahrzeuge = get_the_terms( $post->ID, 'fahrzeug' );
        if ( $fahrzeuge && ! is_wp_error( $fahrzeuge ) ) {
            $fzg_namen = array();
            foreach ( $fahrzeuge as $fahrzeug ) {
                $fzg_name = $fahrzeug->name;
                
                if($make_links) {
                    $pageid = einsatzverwaltung_get_term_field($fahrzeug->term_id, 'fahrzeug', 'fahrzeugpid');
                    if($pageid !== false) {
                        $pageurl = get_permalink($pageid);
                        if($pageurl !== false) {
                            $fzg_name = '<a href="'.$pageurl.'" title="Mehr Informationen zu '.$fahrzeug->name.'">'.$fahrzeug->name.'</a>';
                        }
                    }
                }
                
                if($make_links && get_option('einsatzvw_show_fahrzeug_archive', EINSATZVERWALTUNG__D__SHOW_FAHRZEUG_ARCHIVE)) {
                    $fzg_name .= '&nbsp;<a href="'.get_term_link($fahrzeug).'" class="fa fa-filter" style="text-decoration:none;" title="Eins&auml;tze unter Beteiligung von '.$fahrzeug->name.' anzeigen"></a>';
                }
                
                $fzg_namen[] = $fzg_name;
            }
            $fzg_string = join( ", ", $fzg_namen );
        } else {
            $fzg_string = '';
        }
        
        $exteinsatzmittel = get_the_terms( $post->ID, 'exteinsatzmittel' );
        if ( $exteinsatzmittel && ! is_wp_error( $exteinsatzmittel ) ) {
            $ext_namen = array();
            foreach ( $exteinsatzmittel as $ext ) {
                $ext_name = $ext->name;
                
                if($make_links) {
                    $url = einsatzverwaltung_get_term_field($ext->term_id, 'exteinsatzmittel', 'url');
                    if($url !== false) {
                        $ext_name = '<a href="'.$url.'" title="Mehr Informationen zu '.$ext->name.'">'.$ext->name.'</a>';
                    }
                }
                
                if($make_links && get_option('einsatzvw_show_exteinsatzmittel_archive', EINSATZVERWALTUNG__D__SHOW_EXTEINSATZMITTEL_ARCHIVE)) {
                    $ext_name .= '&nbsp;<a href="'.get_term_link($ext).'" class="fa fa-filter" style="text-decoration:none;" title="Eins&auml;tze unter Beteiligung von '.$ext->name.' anzeigen"></a>';
                }
                
                $ext_namen[] = $ext_name;
            }
            $ext_string = join( ", ", $ext_namen );
        } else {
            $ext_string = '';
        }
        
        $alarm_timestamp = strtotime($alarmzeit);
        $datumsformat = get_option('date_format', 'd.m.Y');
        $zeitformat = get_option('time_format', 'H:i');
        $einsatz_datum = ($alarm_timestamp ? date_i18n($datumsformat, $alarm_timestamp) : '-');
        $einsatz_zeit = ($alarm_timestamp ? date_i18n($zeitformat, $alarm_timestamp).' Uhr' : '-');
        
        $headerstring = "<strong>Datum:</strong> ".$einsatz_datum."<br>";
        $headerstring .= "<strong>Alarmzeit:</strong> ".$einsatz_zeit."<br>";
        $headerstring .= einsatzverwaltung_get_detail_string('Alarmierungsart:', $alarm_string);
        $headerstring .= einsatzverwaltung_get_detail_string('Dauer:', $dauerstring);
        $headerstring .= einsatzverwaltung_get_detail_string('Art:', $art);
        $headerstring .= einsatzverwaltung_get_detail_string('Einsatzort:', $einsatzort);
        $headerstring .= einsatzverwaltung_get_detail_string('Einsatzleiter:', $einsatzleiter);
        $headerstring .= einsatzverwaltung_get_numeric_detail_string('Mannschaftsst&auml;rke:', $mannschaft, true);
        $headerstring .= einsatzverwaltung_get_detail_string('Fahrzeuge:', $fzg_string);
        $headerstring .= einsatzverwaltung_get_detail_string('Weitere Kr&auml;fte:', $ext_string);
        
        return $headerstring;
    }
    return "";
}


function einsatzverwaltung_get_detail_string($title, $value, $newline = true)
{
    $hide_empty_details = einsatzverwaltung_get_hide_empty_details();
    
    if(!$hide_empty_details || !empty($value)) {
        return '<strong>'.$title.'</strong> '.$value.($newline ? '<br>' : '');
    }
    return '';
}


function einsatzverwaltung_get_numeric_detail_string($title, $value, $is_zero_empty = true, $newline = true)
{
    $hide_empty_details = einsatzverwaltung_get_hide_empty_details();
    
    if(!($hide_empty_details && $is_zero_empty && $value == 0)) {
        return '<strong>'.$title.'</strong> '.$value.($newline ? '<br>' : '');
    }
    return '';
}


function einsatzverwaltung_get_hide_empty_details() {
    $hide_empty_details = get_option('einsatzvw_einsatz_hideemptydetails');
    if($hide_empty_details === false) {
        return EINSATZVERWALTUNG__D__HIDE_EMPTY_DETAILS;
    } else {
        return ($hide_empty_details == 1 ? true : false);
    }
}


/**
 * Bestimmt die Einsatzart eines bestimmten Einsatzes. Ist nötig, weil die Taxonomie
 * 'einsatzart' mehrere Werte speichern kann, aber nur einer genutzt werden soll
 */
function einsatzverwaltung_get_einsatzart($id) {
    $einsatzarten = get_the_terms( $id, 'einsatzart' );
    if ( $einsatzarten && !is_wp_error($einsatzarten) && !empty($einsatzarten) ) {
        $keys = array_keys($einsatzarten);
        return $einsatzarten[$keys[0]];
    } else {
        return false;
    }
}


/**
 * Beim Aufrufen eines Einsatzberichts vor den Text den Kopf mit den Details einbauen
 */
function einsatzverwaltung_add_einsatz_daten($content) {
    global $post;
    if(get_post_type() == "einsatz") {
        $header = einsatzverwaltung_get_einsatzbericht_header($post);
        $header .= "<hr>";
        
        if(strlen($content) > 0) {
            $header .= "<h3>Einsatzbericht:</h3>";
        } else {
            $header .= "Kein Einsatzbericht vorhanden";
        }
        
        $content = $header.$content;
    }
    
    return $content;
}
add_filter( 'the_content', 'einsatzverwaltung_add_einsatz_daten');


/**
 * Stellt den Auszug (Exzerpt) zur Verfügung, im Fall von Einsatzberichten wird
 * hier der Berichtskopf mit den Details zurückgegeben
 */
function einsatzverwaltung_einsatz_excerpt($excerpt)
{
    global $post;
    if(get_post_type() == "einsatz") {
        return einsatzverwaltung_get_einsatzbericht_header( $post, get_option('einsatzvw_show_links_in_excerpt', EINSATZVERWALTUNG__D__SHOW_LINKS_IN_EXCERPT) );
    }
    else {
        return $excerpt;
    }
}
add_filter( 'the_excerpt', 'einsatzverwaltung_einsatz_excerpt');


/**
 * Gibt den Auszug (Exzerpt) für den Feed zurück
 */
function einsatzverwaltung_einsatz_excerpt_feed($excerpt) {
    global $post;
    if(get_post_type() == "einsatz") {
        // Header ohne Links holen
        $header = einsatzverwaltung_get_einsatzbericht_header($post, false);
        
        // Hervorhebung entfernen
        $header = str_replace("<strong>", "", $header);
        $header = str_replace("</strong>", "", $header);
        
        return $header;
    } else {
        return $excerpt;
    }
}
add_filter( 'the_excerpt_rss', 'einsatzverwaltung_einsatz_excerpt_feed' );


/**
 * Gibt eine Tabelle mit Einsätzen aus dem gegebenen Jahr zurück
 */
function einsatzverwaltung_print_einsatzliste( $einsatzjahre = array(), $desc = true, $echo = true, $splitmonths = false )
{
    if($desc === false) {
        sort($einsatzjahre);
    } else {
        rsort($einsatzjahre);
    }
    
    $string = "";
    foreach($einsatzjahre as $einsatzjahr) {
        $query = new WP_Query(array('year' => $einsatzjahr,
            'post_type' => 'einsatz',
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => ($desc === false ? 'ASC' : 'DESC'),
            'nopaging' => true
        ));
        
        $string .= '<h3>Eins&auml;tze '.$einsatzjahr.'</h3>';
        if ( $query->have_posts() ) {
            if( !$splitmonths ) {
                $string .= "<table class=\"einsatzliste\">";
                $string .= "<thead><tr>";
                $string .= "<th width=\"80\">Nummer</th>";
                $string .= "<th width=\"80\">Datum</th>";
                $string .= "<th width=\"50\">Zeit</th>";
                $string .= "<th>Einsatzmeldung</th>";
                $string .= "</tr></thead>";
                $string .= "<tbody>";
            }
            
            $oldmonth = 0;
            while ( $query->have_posts() ) {
                $query->next_post();
            
                $einsatz_nummer = get_post_field('post_name', $query->post->ID);
                $alarmzeit = get_post_meta($query->post->ID, 'einsatz_alarmzeit', true);
                $einsatz_timestamp = strtotime($alarmzeit);
            
                $einsatz_datum = date("d.m.Y", $einsatz_timestamp);
                $einsatz_zeit = date("H:i", $einsatz_timestamp);
                $month = date("m", $einsatz_timestamp);
                
                if($splitmonths && $month != $oldmonth) {
                    if($oldmonth != 0) {
                        // Nicht im ersten Durchlauf
                        $string .= "</tbody>";
                        $string .= "</table>";
                    }
                    $string .= '<h5>' . date_i18n('F', $einsatz_timestamp) . '</h5>';
                    $string .= "<table class=\"einsatzliste\">";
                    $string .= "<thead><tr>";
                    $string .= "<th width=\"80\">Nummer</th>";
                    $string .= "<th width=\"80\">Datum</th>";
                    $string .= "<th width=\"50\">Zeit</th>";
                    $string .= "<th>Einsatzmeldung</th>";
                    $string .= "</tr></thead>";
                    $string .= "<tbody>";
                }
            
                $string .= "<tr>";
                $string .= "<td width=\"80\">".$einsatz_nummer."</td>";
                $string .= "<td width=\"80\">".$einsatz_datum."</td>";
                $string .= "<td width=\"50\">".$einsatz_zeit."</td>";
                $string .= "<td>";
            
                $post_title = get_the_title($query->post->ID);
                if ( !empty($post_title) ) {
                    $string .= "<a href=\"".get_permalink($query->post->ID)."\" rel=\"bookmark\">".$post_title."</a><br>";
                } else {
                    $string .= "<a href=\"".get_permalink($query->post->ID)."\" rel=\"bookmark\">(kein Titel)</a><br>";
                }
                $string .= "</td>";
                $string .= "</tr>";
                
                $oldmonth = $month;
            }
        
            $string .= "</tbody>";
            $string .= "</table>";
        } else {
            $string .= sprintf("Keine Eins&auml;tze im Jahr %s", $einsatzjahr);
        }
    }
    
    if($echo === true) {
        echo $string;
    } else {
        return $string;
    }
}


/**
 * Legt fest, welche Spalten bei der Übersicht der Einsatzberichte im
 * Adminbereich angezeigt werden
 */
function einsatzverwaltung_edit_einsatz_columns( $columns ) {

    $columns = array(
        'cb' => '<input type="checkbox" />',
        'title' => __( 'Einsatzbericht', 'einsatzverwaltung' ),
        'e_nummer' => __( 'Nummer', 'einsatzverwaltung' ),
        'e_alarmzeit' => __( 'Alarmzeit', 'einsatzverwaltung' ),
        'e_einsatzende' => __( 'Einsatzende', 'einsatzverwaltung' ),
        'e_art' => __( 'Art', 'einsatzverwaltung' ),
        'e_fzg' => __( 'Fahrzeuge', 'einsatzverwaltung' )
    );

    return $columns;
}
add_filter( 'manage_edit-einsatz_columns', 'einsatzverwaltung_edit_einsatz_columns' ) ;


/**
 * Liefert den Inhalt für die jeweiligen Spalten bei der Übersicht der
 * Einsatzberichte im Adminbereich
 */
function einsatzverwaltung_manage_einsatz_columns( $column, $post_id ) {
    global $post;

    switch( $column ) {

        case 'e_nummer' :
            $einsatz_nummer = get_post_field('post_name', $post_id);

            if ( empty( $einsatz_nummer ) )
                echo '-';
            else
                echo $einsatz_nummer;

            break;

        case 'e_einsatzende' :
            $einsatz_einsatzende = get_post_meta( $post_id, 'einsatz_einsatzende', true );

            if ( empty( $einsatz_einsatzende ) ) {
                echo '-';
            } else {
                $timestamp = strtotime($einsatz_einsatzende);
                echo date("d.m.Y", $timestamp)."<br>".date("H:i", $timestamp);
            }

            break;
            
        case 'e_alarmzeit' :
            $einsatz_alarmzeit = get_post_meta( $post_id, 'einsatz_alarmzeit', true );

            if ( empty( $einsatz_alarmzeit ) ) {
                echo '-';
            } else {
                $timestamp = strtotime($einsatz_alarmzeit);
                echo date("d.m.Y", $timestamp)."<br>".date("H:i", $timestamp);
            }

            break;
            
        case 'e_art' :

            $term = einsatzverwaltung_get_einsatzart($post_id);
            if ( $term ) {
                printf( '<a href="%s">%s</a>',
                    esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'einsatzart' => $term->slug ), 'edit.php' ) ),
                    esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'einsatzart', 'display' ) )
                );
            } else {
                echo '-';
            }

            break;

        case 'e_fzg' :

            $terms = get_the_terms( $post_id, 'fahrzeug' );

            if ( !empty( $terms ) ) {
                $out = array();
                foreach ( $terms as $term ) {
                    $out[] = sprintf( '<a href="%s">%s</a>',
                        esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'fahrzeug' => $term->slug ), 'edit.php' ) ),
                        esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'fahrzeug', 'display' ) )
                    );
                }

                echo join( ', ', $out );
            }

            else {
                echo '-';
            }

            break;

        default :
            break;
    }
}
add_action( 'manage_einsatz_posts_custom_column', 'einsatzverwaltung_manage_einsatz_columns', 10, 2 );


/**
 * Gibt ein Array mit Jahreszahlen zurück, in denen Einsätze vorliegen
 */
function einsatzverwaltung_get_jahremiteinsatz()
{
    $jahre = array();
    $query = new WP_Query( '&post_type=einsatz&post_status=publish&nopaging=true' );
    while($query->have_posts()) {
        $p = $query->next_post();
        $timestamp = strtotime($p->post_date);
        $jahre[date("Y", $timestamp)] = 1;
    }
    return array_keys($jahre);
}


/**
 * Zahl der Einsatzberichte im Dashboard anzeigen
 */
function einsatzverwaltung_add_einsatzberichte_to_dashboard($arr) {
    if (post_type_exists('einsatz')) {
        $pt = 'einsatz';
        $pt_info = get_post_type_object($pt); // get a specific CPT's details
        $num_posts = wp_count_posts($pt); // retrieve number of posts associated with this CPT
        $num = number_format_i18n($num_posts->publish); // number of published posts for this CPT
        $text = _n( $pt_info->labels->singular_name, $pt_info->labels->name, intval($num_posts->publish) ); // singular/plural text label for CPT
        echo '<li class="'.$pt_info->name.'-count page-count">';
        echo (current_user_can('edit_einsatzberichte') ? '<a href="edit.php?post_type='.$pt.'">'.$num.' '.$text.'</a>' : '<span>'.$num.' '.$text.'</span>' ).'</li>';
    }
}
add_action('dashboard_glance_items', 'einsatzverwaltung_add_einsatzberichte_to_dashboard'); // since WP 3.8


/**
 * Zahl der Einsatzberichte im Dashboard anzeigen (für WordPress 3.7 und älter)
 */
function einsatzverwaltung_add_einsatzberichte_to_dashboard_legacy() {
    if (post_type_exists('einsatz')) {
        $pt = 'einsatz';
        $pt_info = get_post_type_object($pt); // get a specific CPT's details
        $num_posts = wp_count_posts($pt); // retrieve number of posts associated with this CPT
        $num = number_format_i18n($num_posts->publish); // number of published posts for this CPT
        $text = _n( $pt_info->labels->singular_name, $pt_info->labels->name, intval($num_posts->publish) ); // singular/plural text label for CPT
        echo '<tr><td class="first b">';
        echo (current_user_can('edit_einsatzberichte') ? '<a href="edit.php?post_type='.$pt.'">'.$num.'</a>' : $num);
        echo '</td><td class="t">';
        echo (current_user_can('edit_einsatzberichte') ? '<a href="edit.php?post_type='.$pt.'">'.$text.'</a>' : $text);
        echo '</td></tr>';
    }
}
add_action('right_now_content_table_end', 'einsatzverwaltung_add_einsatzberichte_to_dashboard_legacy'); // before WP 3.8


/**
 * Reparaturen oder Anpassungen der Datenbank nach einem Update
 */
function einsatzverwaltung_update_db_check() {
    global $evw_db_version;
    $evw_installed_version = get_site_option( EINSATZVERWALTUNG__DBVERSION_OPTION );
    
    if($evw_installed_version === false) {
        $evw_installed_version = 0;
    } else if(is_numeric($evw_installed_version)) { 
        $evw_installed_version = intval($evw_installed_version);
    } else {
        $evw_installed_version = 0;
    }
    
    if ($evw_installed_version < $evw_db_version) {
        global $wpdb;
        
        if($evw_installed_version == 0) {
            $berichte = einsatzverwaltung_get_einsatzberichte('');

            // unhook this function so it doesn't loop infinitely
            remove_action('save_post', 'einsatzverwaltung_save_postdata');
            
            foreach($berichte as $bericht) {
                $post_id = $bericht->ID;
                if ( ! wp_is_post_revision( $post_id ) ) {
                    $gmtdate = get_gmt_from_date( $bericht->post_date );
                    $wpdb->query( $wpdb->prepare("UPDATE $wpdb->posts SET post_date_gmt = %s WHERE ID = %d", $gmtdate, $post_id) );
                }
            }
            
            // re-hook this function
            add_action('save_post', 'einsatzverwaltung_save_postdata');
            
            $evw_installed_version = 1;
            update_site_option( EINSATZVERWALTUNG__DBVERSION_OPTION, $evw_installed_version );
        }
        
        if($evw_installed_version == 1) {
            global $evw_caps;
            update_option('einsatzvw_cap_roles_administrator', 1);
            $role_obj = get_role('administrator');
            foreach($evw_caps as $cap) {
                $role_obj->add_cap( $cap );
            } 
            
            $evw_installed_version = 2;
            update_site_option( EINSATZVERWALTUNG__DBVERSION_OPTION, $evw_installed_version );
        }
        
    }
}
add_action( 'plugins_loaded', 'einsatzverwaltung_update_db_check' );


/**
 * Check the version of PHP running on the server
 */
function check_php_version($ver) {
    $php_version = phpversion();
    if (version_compare($php_version, $ver) < 0) {
        wp_die("Das Plugin Einsatzverwaltung ben&ouml;tigt PHP Version $ver oder neuer. Bitte aktualisieren Sie PHP auf Ihrem Server!", 'Veraltete PHP-Version!', array('back_link' => true));
    }
}

function einsatzverwaltung_is_min_wp_version($ver) {
    $currentversionparts = explode(".", get_bloginfo('version'));
    if(count($currentversionparts) < 3) {
        $currentversionparts[2] = "0";
    }
    
    $neededversionparts = explode(".", $ver);
    if(count($neededversionparts) < 3) {
        $neededversionparts[2] = "0";
    }
    
    if(intval($neededversionparts[0]) > intval($currentversionparts[0])) {
        return false;
    } else if(intval($neededversionparts[0]) == intval($currentversionparts[0]) &&
                intval($neededversionparts[1]) > intval($currentversionparts[1])) {
        return false;
    } else if(intval($neededversionparts[0]) == intval($currentversionparts[0]) &&
                intval($neededversionparts[1]) == intval($currentversionparts[1]) &&
                intval($neededversionparts[2]) > intval($currentversionparts[2])) {
        return false;
    }
    
    return true;
}


function einsatzverwaltung_print_error($message) {
    echo '<p class="evw_error"><i class="fa fa-exclamation-circle"></i>&nbsp;' . $message . '</p>';
}

function einsatzverwaltung_print_warning($message) {
    echo '<p class="evw_warning"><i class="fa fa-exclamation-triangle"></i>&nbsp;' . $message . '</p>';
}

function einsatzverwaltung_print_success($message) {
    echo '<p class="evw_success"><i class="fa fa-check-circle"></i>&nbsp;' . $message . '</p>';
}

function einsatzverwaltung_print_info($message) {
    echo '<p class="evw_info"><i class="fa fa-info-circle"></i>&nbsp;' . $message . '</p>';
}

?>