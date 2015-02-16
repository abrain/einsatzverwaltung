<?php

/**
 * Bindet CSS für das Frontend ein
 */
function einsatzverwaltung_enqueue_frontend_style()
{
    wp_enqueue_style(
        'einsatzverwaltung-fontawesome',
        EINSATZVERWALTUNG__PLUGIN_URL . 'font-awesome/css/font-awesome.min.css'
    );
}
add_action('wp_enqueue_scripts', 'einsatzverwaltung_enqueue_frontend_style');


/**
 * Zeigt Dropdown mit Hierarchie für die Einsatzart
 *
 * @param string $selected Slug der ausgewählten Einsatzart
 */
function einsatzverwaltung_dropdown_einsatzart($selected)
{
    wp_dropdown_categories(array(
        'show_option_all'    => '',
        'show_option_none'   => '- keine -',
        'orderby'            => 'NAME',
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
 *
 * @param WP_Post $post Das Post-Objekt
 * @param bool $may_contain_links True, wenn Links generiert werden dürfen
 * @param bool $showArchiveLinks Bestimmt, ob Links zu Archivseiten generiert werden dürfen
 *
 * @return string Auflistung der Einsatzdetails
 */
function einsatzverwaltung_get_einsatzbericht_header($post, $may_contain_links = true, $showArchiveLinks = true)
{
    if (get_post_type($post) == "einsatz") {
        $make_links = $may_contain_links;

        $alarmzeit = get_post_meta($post->ID, 'einsatz_alarmzeit', true);
        $einsatzende = get_post_meta($post->ID, 'einsatz_einsatzende', true);

        $alarmierungsart = get_the_terms($post->ID, 'alarmierungsart');
        if ($alarmierungsart && ! is_wp_error($alarmierungsart)) {
            $alarm_namen = array();
            foreach ($alarmierungsart as $alarmart) {
                $alarm_namen[] = $alarmart->name;
            }
            $alarm_string = join(", ", $alarm_namen);
        } else {
            $alarm_string = '';
        }

        $dauerstring = '';
        if (!empty($alarmzeit) && !empty($einsatzende)) {
            $timestamp1 = strtotime($alarmzeit);
            $timestamp2 = strtotime($einsatzende);
            $differenz = $timestamp2 - $timestamp1;
            $dauer = intval($differenz / 60);

            if (empty($dauer) || !is_numeric($dauer)) {
                $dauerstring = '';
            } else {
                if ($dauer <= 0) {
                    $dauerstring = '';
                } elseif ($dauer < 60) {
                    $dauerstring = $dauer." Minuten";
                } else {
                    $dauer_h = intval($dauer / 60);
                    $dauer_m = $dauer % 60;
                    $dauerstring = $dauer_h." Stunde".($dauer_h > 1 ? "n" : "");
                    if ($dauer_m > 0) {
                        $dauerstring .= " ".$dauer_m." Minute".($dauer_m > 1 ? "n" : "");
                    }
                }
            }
        }

        $einsatzart = einsatzverwaltung_get_einsatzart($post->ID);
        if ($einsatzart) {
            $showEinsatzartArchiveLink = $showArchiveLinks && get_option(
                'einsatzvw_show_einsatzart_archive',
                EINSATZVERWALTUNG__D__SHOW_EINSATZART_ARCHIVE
            );
            $art = einsatzverwaltung_get_einsatzart_string(
                $einsatzart,
                $make_links,
                $showEinsatzartArchiveLink
            );
        } else {
            $art = '';
        }

        $fehlalarm = get_post_meta($post->ID, $key = 'einsatz_fehlalarm', $single = true);
        if (empty($fehlalarm)) {
            $fehlalarm = 0;
        }
        if ($fehlalarm == 1) {
            $art = (empty($art) ? 'Fehlalarm' : $art.' (Fehlalarm)');
        }

        $einsatzort = get_post_meta($post->ID, $key = 'einsatz_einsatzort', $single = true);

        $einsatzleiter = get_post_meta($post->ID, $key = 'einsatz_einsatzleiter', $single = true);

        $mannschaft = get_post_meta($post->ID, $key = 'einsatz_mannschaft', $single = true);
        if (empty($mannschaft)) {
            $mannschaft = 0;
        }

        $fahrzeuge = get_the_terms($post->ID, 'fahrzeug');
        if ($fahrzeuge && ! is_wp_error($fahrzeuge)) {
            $fzg_namen = array();
            foreach ($fahrzeuge as $fahrzeug) {
                $fzg_name = $fahrzeug->name;

                if ($make_links) {
                    $pageid = einsatzverwaltung_get_term_field($fahrzeug->term_id, 'fahrzeug', 'fahrzeugpid');
                    if ($pageid !== false) {
                        $pageurl = get_permalink($pageid);
                        if ($pageurl !== false) {
                            $fzg_name = '<a href="'.$pageurl.'" title="Mehr Informationen zu '.$fahrzeug->name.'">'.$fahrzeug->name.'</a>';
                        }
                    }
                }

                if ($make_links && $showArchiveLinks && get_option('einsatzvw_show_fahrzeug_archive', EINSATZVERWALTUNG__D__SHOW_FAHRZEUG_ARCHIVE)) {
                    $fzg_name .= '&nbsp;<a href="'.get_term_link($fahrzeug).'" class="fa fa-filter" style="text-decoration:none;" title="Eins&auml;tze unter Beteiligung von '.$fahrzeug->name.' anzeigen"></a>';
                }

                $fzg_namen[] = $fzg_name;
            }
            $fzg_string = join(", ", $fzg_namen);
        } else {
            $fzg_string = '';
        }

        $exteinsatzmittel = get_the_terms($post->ID, 'exteinsatzmittel');
        if ($exteinsatzmittel && ! is_wp_error($exteinsatzmittel)) {
            $ext_namen = array();
            foreach ($exteinsatzmittel as $ext) {
                $ext_name = $ext->name;

                if ($make_links) {
                    $url = einsatzverwaltung_get_term_field($ext->term_id, 'exteinsatzmittel', 'url');
                    if ($url !== false) {
                        $open_in_new_window = get_option('einsatzvw_open_ext_in_new', EINSATZVERWALTUNG__D__OPEN_EXTEINSATZMITTEL_NEWWINDOW);
                        $ext_name = '<a href="'.$url.'" title="Mehr Informationen zu '.$ext->name.'"' . ($open_in_new_window ? ' target="_blank"' : '') . '>'.$ext->name.'</a>';
                    }
                }

                if ($make_links && $showArchiveLinks && get_option('einsatzvw_show_exteinsatzmittel_archive', EINSATZVERWALTUNG__D__SHOW_EXTEINSATZMITTEL_ARCHIVE)) {
                    $ext_name .= '&nbsp;<a href="'.get_term_link($ext).'" class="fa fa-filter" style="text-decoration:none;" title="Eins&auml;tze unter Beteiligung von '.$ext->name.' anzeigen"></a>';
                }

                $ext_namen[] = $ext_name;
            }
            $ext_string = join(", ", $ext_namen);
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

        return "<p>$headerstring</p>";
    }
    return "";
}


/**
 * Erzeugt eine Zeile für die Einsatzdetails
 *
 * @param string $title Bezeichnung des Einsatzdetails
 * @param string $value Wert des Einsatzdetails
 * @param bool $newline Zeilenumbruch hinzufügen
 *
 * @return string Formatiertes Einsatzdetail
 */
function einsatzverwaltung_get_detail_string($title, $value, $newline = true)
{
    $hide_empty_details = einsatzverwaltung_get_hide_empty_details();

    if (!$hide_empty_details || !empty($value)) {
        return '<strong>'.$title.'</strong> '.$value.($newline ? '<br>' : '');
    }
    return '';
}


/**
 * Erzeugt eine Zeile für die Einsatzdetails, speziell für numerische Angaben
 *
 * @param string $title Bezeichnung des Einsatzdetails
 * @param string $value Wert des Einsatzdetails
 * @param bool $is_zero_empty Ob die 0 als leer gewertet wird
 * @param bool $newline Zeilenumbruch hinzufügen
 *
 * @return string Formatiertes Einsatzdetail
 */
function einsatzverwaltung_get_numeric_detail_string($title, $value, $is_zero_empty = true, $newline = true)
{
    $hide_empty_details = einsatzverwaltung_get_hide_empty_details();

    if (!($hide_empty_details && $is_zero_empty && $value == 0)) {
        return '<strong>'.$title.'</strong> '.$value.($newline ? '<br>' : '');
    }
    return '';
}


/**
 * Beim Aufrufen eines Einsatzberichts vor den Text den Kopf mit den Details einbauen
 *
 * @param string $content Der Beitragstext des Einsatzberichts
 *
 * @return string Mit Einsatzdetails angereicherter Beitragstext
 */
function einsatzverwaltung_the_content($content)
{
    global $post;
    if (get_post_type() !== "einsatz") {
        return $content;
    }

    $header = einsatzverwaltung_get_einsatzbericht_header($post, true, true);
    $content = einsatzverwaltung_prepare_content($content);

    return $header . '<hr>' . $content;
}
add_filter('the_content', 'einsatzverwaltung_the_content');


/**
 * Bereitet den Beitragstext auf
 *
 * @param string $content Der Beitragstext des Einsatzberichts
 *
 * @return string Der Beitragstext mit einer vorangestellten Überschrift. Wenn der Beitragstext leer ist, wird ein
 * Ersatztext zurückgegeben
 */
function einsatzverwaltung_prepare_content($content)
{
    return empty($content) ? '<p>Kein Einsatzbericht vorhanden</p>' : '<h3>Einsatzbericht:</h3>' . $content;
}


/**
 * Stellt die Kurzfassung (Exzerpt) zur Verfügung, im Fall von Einsatzberichten wird
 * hier wahlweise der Berichtstext, Einsatzdetails oder beides zurückgegeben
 *
 * @param string $excerpt Filterparameter, wird bei Einsatzberichten nicht beachtet, bei anderen Beitragstypen
 * unverändert verwendet
 *
 * @return string Die Kurzfassung
 */
function einsatzverwaltung_einsatz_excerpt($excerpt)
{
    global $post;
    if (get_post_type() !== 'einsatz') {
        return $excerpt;
    }

    $excerptType = get_option('einsatzvw_excerpt_type', EINSATZVERWALTUNG__D__EXCERPT_TYPE);
    return einsatzverwaltung_einsatz_get_excerpt($post, $excerptType, true, true);
}
add_filter('the_excerpt', 'einsatzverwaltung_einsatz_excerpt');


/**
 * Gibt die Kurzfassung (Exzerpt) für den Feed zurück
 *
 * @param string $excerpt Filterparameter, wird bei Einsatzberichten nicht beachtet, bei anderen Beitragstypen
 * unverändert verwendet
 *
 * @return string Die Kurzfassung
 */
function einsatzverwaltung_einsatz_excerpt_feed($excerpt)
{
    global $post;
    if (get_post_type() !== 'einsatz') {
        return $excerpt;
    }

    $excerptType = get_option('einsatzvw_excerpt_type_feed', EINSATZVERWALTUNG__D__EXCERPT_TYPE);
    $get_excerpt = einsatzverwaltung_einsatz_get_excerpt($post, $excerptType, true, false);
    $get_excerpt = str_replace('<strong>', '', $get_excerpt);
    $get_excerpt = str_replace('</strong>', '', $get_excerpt);
    return $get_excerpt;
}
add_filter('the_excerpt_rss', 'einsatzverwaltung_einsatz_excerpt_feed');


/**
 * @param WP_Post $post
 * @param string $excerptType
 * @param bool $excerptMayContainLinks
 * @param bool $showArchiveLinks
 *
 * @return mixed|string|void
 */
function einsatzverwaltung_einsatz_get_excerpt($post, $excerptType, $excerptMayContainLinks, $showArchiveLinks)
{
    switch ($excerptType) {
        case 'details':
            return einsatzverwaltung_get_einsatzbericht_header($post, $excerptMayContainLinks, $showArchiveLinks);
        case 'text':
            return einsatzverwaltung_prepare_content(get_the_content());
        case 'none':
            return '';
        default:
            return einsatzverwaltung_get_einsatzbericht_header($post, $excerptMayContainLinks, $showArchiveLinks);
    }
}


/**
 * Gibt Einsatzberichte ggf. auch zwischen den 'normalen' Blogbeiträgen aus
 *
 * @param WP_Query $query
 */
function einsatzverwaltung_add_einsatzberichte_to_mainloop($query)
{
    if (
        get_option('einsatzvw_show_einsatzberichte_mainloop', EINSATZVERWALTUNG__D__SHOW_EINSATZBERICHTE_MAINLOOP) &&
        $query->is_main_query() &&
        is_home() &&
        empty($query->query_vars['suppress_filters'])
    ) {
        $post_types = isset($query->query_vars['post_type']) ? (array) $query->query_vars['post_type'] : array('post');
        $post_types[] = 'einsatz';
        $query->set('post_type', $post_types);
    }
}
add_action('pre_get_posts', 'einsatzverwaltung_add_einsatzberichte_to_mainloop');


/**
 * Gibt eine Tabelle mit Einsätzen aus dem gegebenen Jahr zurück
 *
 * @param array $einsatzjahre
 * @param bool $desc
 * @param bool $splitmonths
 *
 * @return string
 */
function einsatzverwaltung_print_einsatzliste($einsatzjahre = array(), $desc = true, $splitmonths = false)
{
    if ($desc === false) {
        sort($einsatzjahre);
    } else {
        rsort($einsatzjahre);
    }

    $string = "";
    foreach ($einsatzjahre as $einsatzjahr) {
        $query = new WP_Query(array('year' => $einsatzjahr,
            'post_type' => 'einsatz',
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => ($desc === false ? 'ASC' : 'DESC'),
            'nopaging' => true
        ));

        $string .= '<h3>Eins&auml;tze '.$einsatzjahr.'</h3>';
        if ($query->have_posts()) {
            if (!$splitmonths) {
                $string .= "<table class=\"einsatzliste\">";
                $string .= einsatzverwaltung_get_einsatzliste_header();
                $string .= "<tbody>";
            }

            $oldmonth = 0;
            while ($query->have_posts()) {
                $query->next_post();

                $einsatz_nummer = get_post_field('post_name', $query->post->ID);
                $alarmzeit = get_post_meta($query->post->ID, 'einsatz_alarmzeit', true);
                $einsatz_timestamp = strtotime($alarmzeit);

                $einsatz_datum = date("d.m.Y", $einsatz_timestamp);
                $einsatz_zeit = date("H:i", $einsatz_timestamp);
                $month = date("m", $einsatz_timestamp);

                if ($splitmonths && $month != $oldmonth) {
                    if ($oldmonth != 0) {
                        // Nicht im ersten Durchlauf
                        $string .= "</tbody>";
                        $string .= "</table>";
                    }
                    $string .= '<h5>' . date_i18n('F', $einsatz_timestamp) . '</h5>';
                    $string .= "<table class=\"einsatzliste\">";
                    $string .= einsatzverwaltung_get_einsatzliste_header();
                    $string .= "<tbody>";
                }

                $string .= "<tr>";
                $string .= "<td>".$einsatz_nummer."</td>";
                $string .= "<td>".$einsatz_datum."</td>";
                $string .= "<td>".$einsatz_zeit."</td>";
                $string .= "<td>";

                $post_title = get_the_title($query->post->ID);
                if (!empty($post_title)) {
                    $string .= "<a href=\"".get_permalink($query->post->ID)."\" rel=\"bookmark\">".$post_title."</a>";
                } else {
                    $string .= "<a href=\"".get_permalink($query->post->ID)."\" rel=\"bookmark\">(kein Titel)</a>";
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

    return $string;
}


/**
 * Gibt die Kopfzeile der Tabelle für die Einsatzübersicht zurück
 */
function einsatzverwaltung_get_einsatzliste_header()
{
    $string = "<thead><tr>";
    $string .= "<th>Nummer</th>";
    $string .= "<th>Datum</th>";
    $string .= "<th>Zeit</th>";
    $string .= "<th>Einsatzmeldung</th>";
    $string .= "</tr></thead>";
    return $string;
}
