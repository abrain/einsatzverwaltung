<?php

/**
 * Gibt eine Tabelle mit Einsätzen aus dem gegebenen Jahr zurück
 */
function einsatzverwaltung_print_einsatzliste( $atts )
{
    extract( shortcode_atts( array('jahr' => date('Y'), 'sort' => 'ab' ), $atts ) );
    
    if (empty($jahr) || strlen($jahr)!=4 || !is_numeric($jahr)) {
        $aktuelles_jahr = date('Y');
        $string .= '<p>' . sprintf('INFO: Jahreszahl %s ung&uuml;ltig, verwende %s', $jahr, $aktuelles_jahr) . '</p>';
        $jahr = $aktuelles_jahr;
    }

    $query = new WP_Query(array('year' => $jahr,
        'post_type' => 'einsatz',
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => ($sort == 'auf' ? 'ASC' : 'DESC'),
        'nopaging' => true
    ));

    $string = "";
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
            
            $einsatz_nummer = get_post_field('post_name', $query->post->ID);
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


/**
 * Gibt Links zu den Archivseiten der Jahre, in denen Einsatzberichte existieren, zurück
 */
function einsatzverwaltung_print_einsatzjahre( $atts )
{
    global $year;
    $jahre = einsatzverwaltung_get_jahremiteinsatz();
    
    $string = "";
    foreach ($jahre as $jahr) {
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

?>