<?
/*
Plugin Name: Einsatzverwaltung
Plugin URI: https://github.com/abrain/einsatzverwaltung
Description: Verwaltung von Feuerwehreins&auml;tzen
Version: 0.2.0
Author: Andreas Brain
Author URI: http://www.abrain.de
License: GPLv2
Text Domain: einsatzverwaltung
*/

define( 'EINSATZVERWALTUNG__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EINSATZVERWALTUNG__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EINSATZVERWALTUNG__SCRIPT_URL', EINSATZVERWALTUNG__PLUGIN_URL . 'js/' );
define( 'EINSATZVERWALTUNG__STYLE_URL', EINSATZVERWALTUNG__PLUGIN_URL . 'css/' );

require_once( EINSATZVERWALTUNG__PLUGIN_DIR . 'class.widget.php' );

add_action( 'init', 'einsatzverwaltung_create_post_type' );

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
        'supports' => array('title', 'editor', 'thumbnail'),
        'show_in_nav_menus' => false,
        'menu_position' => 5);
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
        'meta_box_cb' => 'einsatzverwaltung_display_einsatzart_metabox');
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
        'show_in_nav_menus' => false);
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
        'show_in_nav_menus' => false);
    register_taxonomy( 'exteinsatzmittel', 'einsatz', $args_exteinsatzmittel );
    
    // more rewrite rules
    add_rewrite_rule('einsaetze/([0-9]{4})/?$', 'index.php?post_type=einsatz&year=$matches[1]', 'top');
}


function einsatzverwaltung_rewrite_flush() {
    // First, we "add" the custom post type via the above written function.
    // Note: "add" is written with quotes, as CPTs don't get added to the DB,
    // They are only referenced in the post_type column with a post entry, 
    // when you add a post of this CPT.
    einsatzverwaltung_create_post_type();

    // ATTENTION: This is *only* done during plugin activation hook in this example!
    // You should *NEVER EVER* do this on every page load!!
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'einsatzverwaltung_rewrite_flush' );


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


function einsatzverwaltung_enqueue_admin_scripts($hook) {
    if( 'post.php' != $hook ) {
        return;
    }
    wp_enqueue_script('einsatzverwaltung-edit-script', EINSATZVERWALTUNG__SCRIPT_URL . 'einsatzverwaltung-edit.js', array('jquery'));
    wp_enqueue_style('einsatzverwaltung-edit', EINSATZVERWALTUNG__STYLE_URL . 'style-edit.css');
}
add_action( 'admin_enqueue_scripts', 'einsatzverwaltung_enqueue_admin_scripts' );


/* Prints the box content */
function einsatzverwaltung_display_meta_box( $post ) {
    // Use nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'einsatzverwaltung_nonce' );

    // The actual fields for data entry
    // Use get_post_meta to retrieve an existing value from the database and use the value for the form
    $nummer = get_post_meta( $post->ID, $key = 'einsatz_nummer', $single = true );
    $alarmzeit = get_post_meta( $post->ID, $key = 'einsatz_alarmzeit', $single = true );
    $einsatzende = get_post_meta( $post->ID, $key = 'einsatz_einsatzende', $single = true );
    $fehlalarm = get_post_meta( $post->ID, $key = 'einsatz_fehlalarm', $single = true );

    echo '<table><tbody>';

    echo '<tr><td><label for="einsatzverwaltung_nummer">' . __("Einsatznummer", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_nummer" name="einsatzverwaltung_nummer" value="'.esc_attr($nummer).'" size="10" placeholder="'.einsatzverwaltung_get_next_einsatznummer(date('Y')).'" /></td></tr>';
    
    echo '<tr><td><label for="einsatzverwaltung_alarmzeit">'. __("Alarmzeit", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_alarmzeit" name="einsatzverwaltung_alarmzeit" value="'.esc_attr($alarmzeit).'" size="20" placeholder="JJJJ-MM-TT hh:mm" />&nbsp;<span class="einsatzverwaltung_hint" id="einsatzverwaltung_alarmzeit_hint"></span></td></tr>';

    echo '<tr><td><label for="einsatzverwaltung_einsatzende">'. __("Einsatzende", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_einsatzende" name="einsatzverwaltung_einsatzende" value="'.esc_attr($einsatzende).'" size="20" placeholder="JJJJ-MM-TT hh:mm" />&nbsp;<span class="einsatzverwaltung_hint" id="einsatzverwaltung_einsatzende_hint"></span></td></tr>';
    
    echo '<tr><td><label for="einsatzverwaltung_fehlalarm">'. __("Fehlalarm", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="checkbox" id="einsatzverwaltung_fehlalarm" name="einsatzverwaltung_fehlalarm"' . ($fehlalarm == "on" ? 'checked="checked" ' : ' ') . '/></td></tr>';
    
    echo '</tbody></table>';
}
/**
 * Berechnet die nächste freie Einsatznummer für das gegebene Jahr
 */
function einsatzverwaltung_get_next_einsatznummer($jahr) {
    $query = new WP_Query( 'year=' . $jahr .'&post_type=einsatz&post_status=publish&nopaging=true' );
    return $jahr.str_pad(($query->found_posts + 1), 3, "0", STR_PAD_LEFT);
}

/* When the post is saved, saves our custom data */
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
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        $update_args = array();
        
        // Alarmzeit validieren
        $input_alarmzeit = sanitize_text_field( $_POST['einsatzverwaltung_alarmzeit'] );
        if(!empty($input_alarmzeit)) {
            $alarmzeit = date_create($input_alarmzeit);
        }
        if(empty($alarmzeit)) {
            $alarmzeit = date_create(get_post_field( 'post_date', $post_id, 'raw' ));
        } else {
            $update_args['post_date'] = date_format($alarmzeit, 'Y-m-d H:i:s');
        }

        // Einsatznummer validieren
        $einsatznummer_fallback = einsatzverwaltung_get_next_einsatznummer(date_format($alarmzeit, 'Y'));
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
        
        // Fehlalarm validieren
        $fehlalarm = (isset($_POST['einsatzverwaltung_fehlalarm']) ? "on" : "off");
        $mist;
        
        // Metadaten schreiben
        update_post_meta($post_id, 'einsatz_nummer', $einsatznummer);
        update_post_meta($post_id, 'einsatz_alarmzeit', date_format($alarmzeit, 'Y-m-d H:i'));
        update_post_meta($post_id, 'einsatz_einsatzende', $einsatzende);
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
 * Zeigt die Metabox für die Einsatzart
 */
function einsatzverwaltung_display_einsatzart_metabox( $post ) {
    $einsatzart = einsatzverwaltung_get_einsatzart($post->ID);
    echo '<select name="tax_input[einsatzart]">';
    echo '<option value="">' . __('- keine -', 'einsatzverwaltung') . '</option>';
    $terms = get_terms('einsatzart', array('hide_empty' => false));
    foreach($terms as $term) {
        echo '<option';
        if($einsatzart && $einsatzart->term_id == $term->term_id) {
            echo ' selected';
        }
        echo '>' . $term->name . '</option>';
    }
    echo '</select>';
}

function einsatzverwaltung_get_einsatzbericht_header($post) {
    if(get_post_type($post) == "einsatz") {
        $alarmzeit = get_post_meta($post->ID, 'einsatz_alarmzeit', true);
        $einsatzende = get_post_meta($post->ID, 'einsatz_einsatzende', true);
        
        $dauerstring = "?";
        if(!empty($alarmzeit) && !empty($einsatzende)) {
            $timestamp1 = strtotime($alarmzeit);
            $timestamp2 = strtotime($einsatzende);
            $differenz = $timestamp2 - $timestamp1;
            $dauer = intval($differenz / 60);
            
            if(empty($dauer) || !is_numeric($dauer)) {
                $dauerstring = "-";
            } else {
                if($dauer <= 0) {
                    $dauerstring = "-";
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
            $dauerstring = "-";
        }
        
        $einsatzart = einsatzverwaltung_get_einsatzart($post->ID);
        $art = ($einsatzart ? $einsatzart->name : "-");
        
        $fehlalarm = get_post_meta( $post->ID, $key = 'einsatz_fehlalarm', $single = true );
        if($fehlalarm == "on") {
            $art .= " (Fehlalarm)";
        }
        
        $fahrzeuge = get_the_terms( $post->ID, 'fahrzeug' );
        if ( $fahrzeuge && ! is_wp_error( $fahrzeuge ) ) {
            $fzg_namen = array();
            foreach ( $fahrzeuge as $fahrzeug ) {
                $fzg_namen[] = $fahrzeug->name;
            }
            $fzg_string = join( ", ", $fzg_namen );
        } else {
            $fzg_string = "-";
        }
        
        $exteinsatzmittel = get_the_terms( $post->ID, 'exteinsatzmittel' );
        if ( $exteinsatzmittel && ! is_wp_error( $exteinsatzmittel ) ) {
            $ext_namen = array();
            foreach ( $exteinsatzmittel as $ext ) {
                $ext_namen[] = $ext->name;
            }
            $ext_string = join( ", ", $ext_namen );
        } else {
            $ext_string = "-";
        }
        
        $alarm_timestamp = strtotime($alarmzeit);
        $einsatz_datum = ($alarm_timestamp ? date("d.m.Y", $alarm_timestamp) : "-");
        $einsatz_zeit = ($alarm_timestamp ? date("H:i", $alarm_timestamp)." Uhr" : "-");
        
        $headerstring = "<strong>Datum:</strong> ".$einsatz_datum."<br>";
        $headerstring .= "<strong>Alarmzeit:</strong> ".$einsatz_zeit."<br>";
        $headerstring .= "<strong>Dauer:</strong> ".$dauerstring."<br>";
        $headerstring .= "<strong>Art:</strong> ".$art."<br>";
        $headerstring .= "<strong>Fahrzeuge:</strong> ".$fzg_string."<br>";
        $headerstring .= "<strong>Weitere Kr&auml;fte:</strong> ".$ext_string."<br>";
        
        return $headerstring;
    }
    return "";
}

function einsatzverwaltung_get_einsatzart($id) {
    $einsatzarten = get_the_terms( $id, 'einsatzart' );
    if ( $einsatzarten && !is_wp_error($einsatzarten) && !empty($einsatzarten) ) {
        return $einsatzarten[array_keys($einsatzarten)[0]];
    } else {
        return false;
    }
}

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


function einsatzverwaltung_einsatz_excerpt($excerpt)
{
    global $post;
    if(get_post_type() == "einsatz") {
        return einsatzverwaltung_get_einsatzbericht_header($post);
    }
    else {
        return $excerpt;
    }
}
add_filter( 'the_excerpt', 'einsatzverwaltung_einsatz_excerpt');



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


function einsatzverwaltung_manage_einsatz_columns( $column, $post_id ) {
    global $post;

    switch( $column ) {

        case 'e_nummer' :
            $einsatz_nummer = get_post_meta( $post_id, 'einsatz_nummer', true );

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


function einsatzverwaltung_print_einsatzliste( $atts )
{
    extract( shortcode_atts( array('jahr' => date('Y') ), $atts ) );

    $string = "";
    
    if (strlen($jahr)!=4 || !is_numeric($jahr)) {
        $aktuelles_jahr = date('Y');
        $string .= '<p>' . sprintf('INFO: Jahreszahl %s ung&uuml;ltig, verwende %s', $jahr, $aktuelles_jahr) . '</p>';
        $jahr = $aktuelles_jahr;
    }

    $query = new WP_Query( 'year=' . $jahr .'&post_type=einsatz&post_status=publish&nopaging=true' );

    if ( $query->have_posts() ) {
        $string .= "<table class=\"einsatzliste\">";
        $string .= "<thead><tr>";
        $string .= "<th>Nummer</th>";
        $string .= "<th>Datum</th>";
        $string .= "<th>Zeit</th>";
        $string .= "<th>Einsatzmeldung</th>";
        $string .= "</tr></thead>";
        $string .= "<tbody>";
        while ( $query->have_posts() ) {
            $query->next_post();
            
            $einsatz_nummer = get_post_meta($query->post->ID, 'einsatz_nummer', true);
            $alarmzeit = get_post_meta($query->post->ID, 'einsatz_alarmzeit', true);
            $einsatz_timestamp = strtotime($alarmzeit);
            
            $einsatz_datum = date("d.m.Y", $einsatz_timestamp);
            $einsatz_zeit = date("H:i", $einsatz_timestamp);
            
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
        }
        
        $string .= "</tbody>";
        $string .= "</table>";
    } else {
        $string .= sprintf("Keine Eins&auml;tze im Jahr %s", $jahr);
    }
    
    return $string;
}
add_shortcode( 'einsatzliste', 'einsatzverwaltung_print_einsatzliste' );

function einsatzverwaltung_print_einsatzjahre( $atts )
{
    global $year;
    $jahre = array();
    $query = new WP_Query( '&post_type=einsatz&post_status=publish&nopaging=true' );
    while($query->have_posts()) {
        $p = $query->next_post();
        $timestamp = strtotime($p->post_date);
        $jahre[date("Y", $timestamp)] = 1;
    }
    
    $string = "";
    foreach (array_keys($jahre) as $jahr) {
        if(!empty($string)) {
            $string .= " | ";
        }
        $string .= '<a href="' . home_url('einsaetze/' . $jahr) . '">';
        if($year == $jahr || empty($year) && $jahr == date("Y")) {
            $string .= "<strong>";
        }
        $string .= $jahr;
        if($year == $jahr || empty($year) && $jahr == date("Y")) {
            $string .= "</strong>";
        }
        $string .= "</a>";
    }
    
    return $string;
}
add_shortcode( 'einsatzjahre', 'einsatzverwaltung_print_einsatzjahre' );


/*
 * Einsatzberichte-Menü vor Nicht-Administratoren verstecken
 */
function einsatzverwaltung_remove_einsatz_menu( ) {
    if ( !current_user_can( 'manage_options' ) ) {
        remove_menu_page( 'edit.php?post_type=einsatz' );
    }
}
add_action( 'admin_menu', 'einsatzverwaltung_remove_einsatz_menu', 999 );

?>