<?php
namespace abrain\Einsatzverwaltung;

use WP_Post;
use WP_Query;

/**
 * Generiert alle Inhalte für das Frontend, mit Ausnahme der Shortcodes und des Widgets
 */
class Frontend
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->addHooks();
    }

    private function addHooks()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueueStyleAndScripts'));
        add_filter('the_content', array($this, 'renderContent'));
        add_filter('the_excerpt', array($this, 'filterEinsatzExcerpt'));
        add_filter('the_excerpt_rss', array($this, 'filterEinsatzExcerptFeed'));
        add_action('pre_get_posts', array($this, 'addEinsatzberichteToMainloop'));
    }

    /**
     * Bindet CSS für das Frontend ein
     */
    public function enqueueStyleAndScripts()
    {
        wp_enqueue_style(
            'einsatzverwaltung-fontawesome',
            Core::$pluginUrl . 'font-awesome/css/font-awesome.min.css'
        );
        wp_enqueue_style(
            'einsatzverwaltung-frontend',
            Core::$styleUrl . 'style-frontend.css'
        );
    }

    /**
     * Zeigt Dropdown mit Hierarchie für die Einsatzart
     *
     * @param string $selected Slug der ausgewählten Einsatzart
     */
    public static function dropdownEinsatzart($selected)
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
    public function getEinsatzberichtHeader($post, $may_contain_links = true, $showArchiveLinks = true)
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
                        $dauerstring = $dauer." Minute".($dauer > 1 ? "n" : "");
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

            $einsatzart = Core::getEinsatzart($post->ID);
            if ($einsatzart) {
                $showEinsatzartArchiveLink = $showArchiveLinks && get_option(
                    'einsatzvw_show_einsatzart_archive',
                    EINSATZVERWALTUNG__D__SHOW_EINSATZART_ARCHIVE
                );
                $art = Core::getEinsatzartString(
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
                        $pageid = Taxonomies::getTermField($fahrzeug->term_id, 'fahrzeug', 'fahrzeugpid');
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
                        $url = Taxonomies::getTermField($ext->term_id, 'exteinsatzmittel', 'url');
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
            $headerstring .= $this->getDetailString('Alarmierungsart:', $alarm_string);
            $headerstring .= $this->getDetailString('Dauer:', $dauerstring);
            $headerstring .= $this->getDetailString('Art:', $art);
            $headerstring .= $this->getDetailString('Einsatzort:', $einsatzort);
            $headerstring .= $this->getDetailString('Einsatzleiter:', $einsatzleiter);
            $headerstring .= $this->getNumericDetailString('Mannschaftsst&auml;rke:', $mannschaft, true);
            $headerstring .= $this->getDetailString('Fahrzeuge:', $fzg_string);
            $headerstring .= $this->getDetailString('Weitere Kr&auml;fte:', $ext_string);

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
    private function getDetailString($title, $value, $newline = true)
    {
        $hide_empty_details = Options::isHideEmptyDetails();

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
    private function getNumericDetailString($title, $value, $is_zero_empty = true, $newline = true)
    {
        $hide_empty_details = Options::isHideEmptyDetails();

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
    public function renderContent($content)
    {
        global $post;
        if (get_post_type() !== "einsatz") {
            return $content;
        }

        $header = $this->getEinsatzberichtHeader($post, true, true);
        $content = $this->prepareContent($content);

        return $header . '<hr>' . $content;
    }


    /**
     * Bereitet den Beitragstext auf
     *
     * @param string $content Der Beitragstext des Einsatzberichts
     *
     * @return string Der Beitragstext mit einer vorangestellten Überschrift. Wenn der Beitragstext leer ist, wird ein
     * Ersatztext zurückgegeben
     */
    private function prepareContent($content)
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
    public function filterEinsatzExcerpt($excerpt)
    {
        global $post;
        if (get_post_type() !== 'einsatz') {
            return $excerpt;
        }

        $excerptType = get_option('einsatzvw_excerpt_type', EINSATZVERWALTUNG__D__EXCERPT_TYPE);
        return $this->getEinsatzExcerpt($post, $excerptType, true, true);
    }


    /**
     * Gibt die Kurzfassung (Exzerpt) für den Feed zurück
     *
     * @param string $excerpt Filterparameter, wird bei Einsatzberichten nicht beachtet, bei anderen Beitragstypen
     * unverändert verwendet
     *
     * @return string Die Kurzfassung
     */
    public function filterEinsatzExcerptFeed($excerpt)
    {
        global $post;
        if (get_post_type() !== 'einsatz') {
            return $excerpt;
        }

        $excerptType = get_option('einsatzvw_excerpt_type_feed', EINSATZVERWALTUNG__D__EXCERPT_TYPE);
        $get_excerpt = $this->getEinsatzExcerpt($post, $excerptType, true, false);
        $get_excerpt = str_replace('<strong>', '', $get_excerpt);
        $get_excerpt = str_replace('</strong>', '', $get_excerpt);
        return $get_excerpt;
    }

    /**
     * @param WP_Post $post
     * @param string $excerptType
     * @param bool $excerptMayContainLinks
     * @param bool $showArchiveLinks
     *
     * @return mixed|string|void
     */
    private function getEinsatzExcerpt($post, $excerptType, $excerptMayContainLinks, $showArchiveLinks)
    {
        switch ($excerptType) {
            case 'details':
                return $this->getEinsatzberichtHeader($post, $excerptMayContainLinks, $showArchiveLinks);
            case 'text':
                return $this->prepareContent(get_the_content());
            case 'none':
                return '';
            default:
                return $this->getEinsatzberichtHeader($post, $excerptMayContainLinks, $showArchiveLinks);
        }
    }


    /**
     * Gibt Einsatzberichte ggf. auch zwischen den 'normalen' Blogbeiträgen aus
     *
     * @param WP_Query $query
     */
    public function addEinsatzberichteToMainloop($query)
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


    /**
     * Gibt eine Tabelle mit Einsätzen aus dem gegebenen Jahr zurück
     *
     * @param array $einsatzjahre
     * @param bool $desc
     * @param bool $splitmonths
     *
     * @return string
     */
    public function printEinsatzliste($einsatzjahre = array(), $desc = true, $splitmonths = false)
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
                    $string .= '<table class="einsatzliste">';
                    $string .= $this->getEinsatzlisteHeader();
                    $string .= '<tbody>';
                }

                $oldmonth = 0;
                while ($query->have_posts()) {
                    $query->next_post();

                    $alarmzeit = get_post_meta($query->post->ID, 'einsatz_alarmzeit', true);
                    $einsatz_timestamp = strtotime($alarmzeit);
                    $month = date('m', $einsatz_timestamp);

                    if ($splitmonths && $month != $oldmonth) {
                        if ($oldmonth != 0) {
                            // Nicht im ersten Durchlauf
                            $string .= '</tbody></table>';
                        }
                        $string .= '<h5>' . date_i18n('F', $einsatz_timestamp) . '</h5>';
                        $string .= '<table class="einsatzliste">';
                        $string .= $this->getEinsatzlisteHeader();
                        $string .= '<tbody>';
                    }

                    $string .= '<tr>';

                    $columns = Core::getListColumns();
                    $enabledColumns = Options::getEinsatzlisteEnabledColumns();
                    foreach ($enabledColumns as $colId) {
                        if (!array_key_exists($colId, $columns)) {
                            continue;
                        }

                        $string .= '<td>';
                        switch ($colId) {
                            case 'number':
                                $string .= get_post_field('post_name', $query->post->ID);
                                break;
                            case 'date':
                                $string .= date('d.m.Y', $einsatz_timestamp);
                                break;
                            case 'time':
                                $string .= date('H:i', $einsatz_timestamp);
                                break;
                            case 'title':
                                $post_title = get_the_title($query->post->ID);
                                if (empty($post_title)) {
                                    $post_title = '(kein Titel)';
                                }
                                $string .= '<a href="' . get_permalink($query->post->ID) . '" rel="bookmark">' . $post_title . '</a>';
                                break;
                            default:
                                $string .= '&nbsp;';
                        }
                        $string .= '</td>';
                    }

                    $string .= '</tr>';
                    $oldmonth = $month;
                }
                $string .= '</tbody></table>';
            } else {
                $string .= sprintf('Keine Eins&auml;tze im Jahr %s', $einsatzjahr);
            }
        }

        return $string;
    }


    /**
     * Gibt die Kopfzeile der Tabelle für die Einsatzübersicht zurück
     */
    private function getEinsatzlisteHeader()
    {
        $columns = Core::getListColumns();
        $enabledColumns = Options::getEinsatzlisteEnabledColumns();

        $string = "<thead><tr>";
        foreach ($enabledColumns as $colId) {
            if (!array_key_exists($colId, $columns)) {
                continue;
            }

            $colInfo = $columns[$colId];
            $string .= '<th>' . $colInfo['name'] . '</th>';
        }
        $string .= "</tr></thead>";

        return $string;
    }
}
