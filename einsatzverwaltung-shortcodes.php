<?php

/**
 * Gibt eine Tabelle mit Einsätzen aus dem gegebenen Jahr zurück
 */
function einsatzverwaltung_print_einsatzliste( $atts )
{
    extract( shortcode_atts( array('jahr' => date('Y'), 'sort' => 'ab' ), $atts ) );
    
    $einsatzjahre = array();
    if ($jahr == '*') {
        $einsatzjahre = einsatzverwaltung_get_jahremiteinsatz();
    } else if (empty($jahr) || strlen($jahr)!=4 || !is_numeric($jahr)) {
        $aktuelles_jahr = date('Y');
        $string .= '<p>' . sprintf('INFO: Jahreszahl %s ung&uuml;ltig, verwende %s', $jahr, $aktuelles_jahr) . '</p>';
        $einsatzjahre = array($aktuelles_jahr);
    } else {
        $einsatzjahre = array($jahr);
    }
    
    if($sort == 'auf') {
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
            'order' => ($sort == 'auf' ? 'ASC' : 'DESC'),
            'nopaging' => true
        ));
        
        $string .= '<h3>Eins&auml;tze '.$einsatzjahr.'</h3>';
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
            $string .= sprintf("Keine Eins&auml;tze im Jahr %s", $einsatzjahr);
        }
    }
    
    return $string;
}
add_shortcode( 'einsatzliste', 'einsatzverwaltung_print_einsatzliste' );


/**
 * Gibt Links zu den Archivseiten der Jahre, in denen Einsatzberichte existieren, zurück
 */
function einsatzverwaltung_print_einsatzjahre( $atts )
{
    global $year, $wp_rewrite;
    $jahre = einsatzverwaltung_get_jahremiteinsatz();
    $permalink_structure = get_option( 'permalink_structure' );
    
    $string = "";
    foreach ($jahre as $jahr) {
        if(!empty($string)) {
            $string .= " | ";
        }
        
        $link = get_post_type_archive_link('einsatz') . ( empty( $permalink_structure ) ? '&year='.$jahr : $jahr );
        $string .= '<a href="' . $link . '">';
        
        if($year == $jahr || empty($year) && $jahr == date("Y")) {
            $string .= "<strong>".$jahr."</strong>";
        } else {
            $string .= $jahr;
        }
        
        $string .= "</a>";
    }
    
    return $string;
}
add_shortcode( 'einsatzjahre', 'einsatzverwaltung_print_einsatzjahre' );

?>