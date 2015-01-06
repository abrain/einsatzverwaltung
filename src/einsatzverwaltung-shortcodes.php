<?php

/**
 * Gibt eine Tabelle mit Einsätzen aus dem gegebenen Jahr zurück
 */
function einsatzverwaltung_shortcode_einsatzliste( $atts )
{
    extract( shortcode_atts( array('jahr' => date('Y'), 'sort' => 'ab', 'monatetrennen' => 'nein' ), $atts ) );
    $aktuelles_jahr = date('Y');
    
    $einsatzjahre = array();
    if ($jahr == '*') {
        $einsatzjahre = einsatzverwaltung_get_jahremiteinsatz();
    } else if (is_numeric($jahr) && $jahr < 0) {
        for($i=0; $i < abs(intval($jahr)) && $i < $aktuelles_jahr; $i++) {
            $einsatzjahre[] = $aktuelles_jahr - $i;
        }
    } else if (empty($jahr) || strlen($jahr)!=4 || !is_numeric($jahr)) {
        echo '<p>' . sprintf('INFO: Jahreszahl %s ung&uuml;ltig, verwende %s', $jahr, $aktuelles_jahr) . '</p>';
        $einsatzjahre = array($aktuelles_jahr);
    } else {
        $einsatzjahre = array($jahr);
    }
    
    return einsatzverwaltung_print_einsatzliste($einsatzjahre, ($sort == 'auf' ? false : true ), false, ($monatetrennen == 'ja'));
}
add_shortcode( 'einsatzliste', 'einsatzverwaltung_shortcode_einsatzliste' );


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