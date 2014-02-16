<?
/*
Plugin Name: Einsatzverwaltung
Plugin URI: https://github.com/abrain/einsatzverwaltung
Description: Verwaltung von Feuerwehreins&auml;tzen
Version: 0.1.1
Author: Andreas Brain
Author URI: http://www.abrain.de
License: GPLv2
Text Domain: einsatzverwaltung
*/

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
       'show_in_nav_menus' => false);
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


add_action( 'admin_init', 'einsatzverwaltung_admin' );

function einsatzverwaltung_admin() {
	add_meta_box( 'einsatzverwaltung_meta_box',
		'Einsatzdetails',
		'einsatzverwaltung_display_meta_box',
		'einsatz', 'normal', 'high'
	);
}


/* Prints the box content */
function einsatzverwaltung_display_meta_box( $post ) {
    // Use nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'einsatzverwaltung_nonce' );

    // The actual fields for data entry
    // Use get_post_meta to retrieve an existing value from the database and use the value for the form
    $nummer = get_post_meta( $post->ID, $key = 'einsatz_nummer', $single = true );
    $alarmzeit = get_post_meta( $post->ID, $key = 'einsatz_alarmzeit', $single = true );
    $einsatzende = get_post_meta( $post->ID, $key = 'einsatz_einsatzende', $single = true );
    
    if(empty($nummer)) {
        
        $year = date('Y');
        $query = new WP_Query( 'year=' . $year .'&post_type=einsatz&post_status=publish&nopaging=true' );
        
        $nummer = $year.str_pad(($query->found_posts + 1), 3, "0", STR_PAD_LEFT);
    }


    echo '<table><tbody>';

    echo '<tr><td><label for="einsatzverwaltung_nummer">' . __("Einsatznummer", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_nummer" name="einsatzverwaltung_nummer" value="'.esc_attr($nummer).'" size="10" /></td></tr>';
    
    echo '<tr><td><label for="einsatzverwaltung_alarmzeit">'. __("Alarmzeit", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_alarmzeit" name="einsatzverwaltung_alarmzeit" value="'.esc_attr($alarmzeit).'" size="20" /> (YYYY-MM-DD hh:mm)</td></tr>';

    echo '<tr><td><label for="einsatzverwaltung_einsatzende">'. __("Einsatzende", 'einsatzverwaltung' ) . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_einsatzende" name="einsatzverwaltung_einsatzende" value="'.esc_attr($einsatzende).'" size="20" /> (YYYY-MM-DD hh:mm)</td></tr>';
    
    echo '</tbody></table>';
}

/* When the post is saved, saves our custom data */
function einsatzverwaltung_save_postdata( $post_id ) {

    // verify if this is an auto save routine. 
    // If it is our form has not been submitted, so we dont want to do anything
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
        return;

    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if ( !isset( $_POST['einsatzverwaltung_nonce'] ) || !wp_verify_nonce( $_POST['einsatzverwaltung_nonce'], plugin_basename( __FILE__ ) ) )
        return;

  
    // Check permissions
    if ( 'einsatz' == $_POST['post_type'] ) 
    {
        if ( !current_user_can( 'edit_post', $post_id ) )
            return;
    }

    // OK, we're authenticated: we need to find and save the data
    
    //if saving in a custom table, get post_ID
    $post_ID = $_POST['post_ID'];
    //sanitize user input
    $data_nummer = sanitize_text_field( $_POST['einsatzverwaltung_nummer'] );
    $data_alarmzeit = sanitize_text_field( $_POST['einsatzverwaltung_alarmzeit'] );
    $data_einsatzende = sanitize_text_field( $_POST['einsatzverwaltung_einsatzende'] );
    
    add_post_meta($post_ID, 'einsatz_nummer', $data_nummer, true) or
    update_post_meta($post_ID, 'einsatz_nummer', $data_nummer);

    add_post_meta($post_ID, 'einsatz_alarmzeit', $data_alarmzeit, true) or
    update_post_meta($post_ID, 'einsatz_alarmzeit', $data_alarmzeit);
    
    add_post_meta($post_ID, 'einsatz_einsatzende', $data_einsatzende, true) or
    update_post_meta($post_ID, 'einsatz_einsatzende', $data_einsatzende);
    
    
    
    $my_args = array();
    $my_args['ID'] = $post_id;
    
    
    $alarmzeit_timestamp = strtotime($data_alarmzeit);
    if($alarmzeit_timestamp) {
        $my_args['post_date'] = date("Y-m-d H:i:s", $alarmzeit_timestamp);
    }
    
    if(!empty($data_nummer) && is_numeric($data_nummer)) {
        $my_args['post_name'] = $data_nummer;
    }
        
    if(array_key_exists('post_date', $my_args) || array_key_exists('post_name', $my_args)) {
        
        if ( ! wp_is_post_revision( $post_id ) ) {
    	
    		// unhook this function so it doesn't loop infinitely
    		remove_action('save_post', 'einsatzverwaltung_save_postdata');
    	   
    		// update the post, which calls save_post again
    		wp_update_post( $my_args );
    
    		// re-hook this function
    		add_action('save_post', 'einsatzverwaltung_save_postdata');
    	}
    }
}

add_action( 'save_post', 'einsatzverwaltung_save_postdata' );

function einsatzverwaltung_get_einsatzbericht_header($post) {
    if(get_post_type($post) == "einsatz") {
        $alarmzeit = get_post_meta($post->ID, 'einsatz_alarmzeit', true);
        
        $einsatzende = get_post_meta($post->ID, 'einsatz_einsatzende', true);
        if(!empty($alarmzeit) && !empty($einsatzende)) {
            $datetime1 = date_create($alarmzeit);
            $datetime2 = date_create($einsatzende);
            $interval = date_diff($datetime1, $datetime2);
            
            // nur weitermachen, wenn die Differenz nicht negativ ist
            if($interval->format('%r') === "") {
                $dauer_h = intval($interval->format('%h'));
                $dauer_m = intval($interval->format('%i'));
                if($dauer_h == 0 && $dauer_m > 0) {
                    $dauerstring = $dauer_m." Minute".($dauer_m > 1 ? "n" : "");
                } else if ($dauer_h > 0) {
                    $dauerstring = $dauer_h." Stunde".($dauer_h > 1 ? "n" : "");
                    if($dauer_m > 0) {
                        $dauerstring .= " ".$dauer_m." Minute".($dauer_m > 1 ? "n" : "");
                    }
                }
            } else {
                $dauerstring = "-";
            }
        } else {
            $dauerstring = "-";
        }
        
        $einsatzarten = get_the_terms( $post->ID, 'einsatzart' );
        if ( $einsatzarten && ! is_wp_error( $einsatzarten ) ) {
            $arten_namen = array();
            foreach ( $einsatzarten as $einsatzart ) {
                $arten_namen[] = $einsatzart->name;
            }
            $art = join( ", ", $arten_namen );
        } else {
            $art = "-";
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
        
        $alarm_timestamp = strtotime($alarmzeit);
        $einsatz_datum = ($alarm_timestamp ? date("d.m.Y", $alarm_timestamp) : "-");
        $einsatz_zeit = ($alarm_timestamp ? date("H:i", $alarm_timestamp)." Uhr" : "-");
        
        $headerstring = "<strong>Datum:</strong> ".$einsatz_datum."<br>";
        $headerstring .= "<strong>Alarmzeit:</strong> ".$einsatz_zeit."<br>";
        $headerstring .= "<strong>Dauer:</strong> ".$dauerstring."<br>";
        $headerstring .= "<strong>Art:</strong> ".$art."<br>";
        $headerstring .= "<strong>Fahrzeuge:</strong> ".$fzg_string."<br>";
        
        return $headerstring;
    }
    return "";
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

			$terms = get_the_terms( $post_id, 'einsatzart' );

			if ( !empty( $terms ) ) {
				$out = array();
				foreach ( $terms as $term ) {
					$out[] = sprintf( '<a href="%s">%s</a>',
						esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'einsatzart' => $term->slug ), 'edit.php' ) ),
						esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'einsatzart', 'display' ) )
					);
				}

				echo join( ', ', $out );
			}

			else {
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
        $string .= "<a href=\"../../einsaetze/".$jahr."\">";
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


##############################
#           WIDGET           #
##############################

class Einsatzverwaltung_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
	 		'einsatzverwaltung_widget', // Base ID
			'Letzte Eins&auml;tze', // Name
			array( 'description' => __( 'Zeigt die neuesten Eins&auml;tze an', 'einsatzverwaltung'), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$anzahl = $instance['anzahl'];
		$zeigeDatum = $instance['zeigeDatum'];
		$zeigeZeit = $instance['zeigeZeit'];
		
		if ( empty( $title ) ) {
		  $title = "Letzte Eins&auml;tze";
		}
		
		if ( !isset($anzahl) || empty ($anzahl) || !is_numeric($anzahl) || $anzahl < 1) {
		  $anzahl = 3;
		}

        $letzteEinsaetze = "";
		$query = new WP_Query( '&post_type=einsatz&post_status=publish&posts_per_page='.$anzahl );
        while($query->have_posts()) {
            $p = $query->next_post();
            $letzteEinsaetze .= "<li>";
            
            $letzteEinsaetze .= "<a href=\"".get_permalink($p->ID)."\" rel=\"bookmark\" class=\"einsatzmeldung\">";
            $meldung = get_the_title($p->ID);
            if ( !empty($meldung) ) {
                $letzteEinsaetze .= $meldung;
            } else {
                $letzteEinsaetze .= "(kein Titel)";
            }
            $letzteEinsaetze .= "</a>";
            
            if($zeigeDatum) {
                setlocale(LC_TIME, "de_DE");
                $timestamp = strtotime($p->post_date);
                $letzteEinsaetze .= "<br>";
                $letzteEinsaetze .= "<span class=\"einsatzdatum\">".strftime("%d. %b %Y", $timestamp)."</span>";
                if($zeigeZeit) {
                    $letzteEinsaetze .= " | <span class=\"einsatzzeit\">".date("H:i", $timestamp)." Uhr</span>";
                }
            }
            $letzteEinsaetze .= "</li>";
        }

		echo $before_widget;
		echo $before_title . $title . $after_title;
        echo ( empty($letzteEinsaetze) ? "Keine Eins&auml;tze" : "<ul>".$letzteEinsaetze."</ul>");
		echo $after_widget;
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		
		$anzahl = $new_instance['anzahl'];
        if ( empty ($anzahl) || !is_numeric($anzahl) || $anzahl < 1) {
            $instance['anzahl'] = $old_instance['anzahl'];
        } else {
            $instance['anzahl'] = $new_instance['anzahl'];
        }
        
        $instance['zeigeDatum'] = $new_instance['zeigeDatum'];
        $instance['zeigeZeit'] = $new_instance['zeigeZeit'];

		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Letzte Eins&auml;tze', 'einsatzverwaltung');
		}
		
		if ( isset( $instance[ 'anzahl' ] ) ) {
			$anzahl = $instance[ 'anzahl' ];
		}
		else {
			$anzahl = 3;
		}
		
		$zeigeDatum = $instance[ 'zeigeDatum' ];
		$zeigeZeit = $instance[ 'zeigeZeit' ];
		
		echo '<p><label for="'.$this->get_field_id( 'title' ).'">' . __( 'Titel:' , 'einsatzverwaltung') . '</label>';
		echo '<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $title ).'" /></p>';
		
		echo '<p><label for="'.$this->get_field_id( 'anzahl' ).'">' . __( 'Anzahl:' , 'einsatzverwaltung') . '</label>';
		echo '<input id="'.$this->get_field_id( 'anzahl' ).'" name="'.$this->get_field_name( 'anzahl' ).'" type="text" value="'.$anzahl.'" size="3" /></p>';

		echo '<p><input id="'.$this->get_field_id( 'zeigeDatum' ).'" name="'.$this->get_field_name( 'zeigeDatum' ).'" type="checkbox" '.($zeigeDatum ? 'checked="checked" ' : '').'/>';
		echo '&nbsp;<label for="'.$this->get_field_id( 'zeigeDatum' ).'">' . __( 'Datum anzeigen' , 'einsatzverwaltung') . '</label></p>';

		echo '<p style="text-indent:1em;"><input id="'.$this->get_field_id( 'zeigeZeit' ).'" name="'.$this->get_field_name( 'zeigeZeit' ).'" type="checkbox" '.($zeigeZeit ? 'checked="checked" ' : '').'/>';
		echo '&nbsp;<label for="'.$this->get_field_id( 'zeigeZeit' ).'">' . __('Zeit anzeigen (nur in Kombination mit Datum)' , 'einsatzverwaltung') . '</label></p>';
	}
}

// register Einsatz_Widget widget
add_action( 'widgets_init', create_function( '', 'register_widget( "einsatzverwaltung_widget" );' ) );


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