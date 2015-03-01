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
            'font-awesome',
            Core::$pluginUrl . 'font-awesome/css/font-awesome.min.css',
            false,
            '4.3.0'
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

            $alarmierungsarten = Data::getAlarmierungsart($post->ID);
            $alarm_string = self::getAlarmierungsartString($alarmierungsarten);

            $duration = Data::getDauer($post->ID);
            $dauerstring = ($duration === false ? '' : Utilities::getDurationString($duration));

            $einsatzart = Data::getEinsatzart($post->ID);
            $showEinsatzartArchiveLink = $showArchiveLinks && Options::isShowEinsatzartArchive();
            $art = self::getEinsatzartString($einsatzart, $make_links, $showEinsatzartArchiveLink);

            $fehlalarm = Data::getFehlalarm($post->ID);
            if (empty($fehlalarm)) {
                $fehlalarm = 0;
            }
            if ($fehlalarm == 1) {
                $art = (empty($art) ? 'Fehlalarm' : $art.' (Fehlalarm)');
            }

            $einsatzort = Data::getEinsatzort($post->ID);
            $einsatzleiter = Data::getEinsatzleiter($post->ID);
            $mannschaft = Data::getMannschaftsstaerke($post->ID);

            $fahrzeuge = Data::getFahrzeuge($post->ID);
            $fzg_string = self::getFahrzeugeString($fahrzeuge, $make_links, $showArchiveLinks);

            $exteinsatzmittel = Data::getWeitereKraefte($post->ID);
            $ext_string = self::getWeitereKraefteString($exteinsatzmittel, $make_links, $showArchiveLinks);

            $alarmzeit = Data::getAlarmzeit($post->ID);
            $alarm_timestamp = strtotime($alarmzeit);
            $datumsformat = Options::getDateFormat();
            $zeitformat = Options::getTimeFormat();
            $einsatz_datum = ($alarm_timestamp ? date_i18n($datumsformat, $alarm_timestamp) : '-');
            $einsatz_zeit = ($alarm_timestamp ? date_i18n($zeitformat, $alarm_timestamp).' Uhr' : '-');

            $headerstring = "<strong>Datum:</strong> ".$einsatz_datum."<br>";
            $headerstring .= "<strong>Alarmzeit:</strong> ".$einsatz_zeit."<br>";
            $headerstring .= $this->getDetailString('Alarmierungsart:', $alarm_string);
            $headerstring .= $this->getDetailString('Dauer:', $dauerstring);
            $headerstring .= $this->getDetailString('Art:', $art);
            $headerstring .= $this->getDetailString('Einsatzort:', $einsatzort);
            $headerstring .= $this->getDetailString('Einsatzleiter:', $einsatzleiter);
            $headerstring .= $this->getDetailString('Mannschaftsst&auml;rke:', $mannschaft);
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
        if (Options::isHideEmptyDetails() && (!isset($value) || $value === '')) {
            return '';
        }

        return '<strong>'.$title.'</strong> '.$value.($newline ? '<br>' : '');
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

        $excerptType = Options::getExcerptType();
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

        $excerptType = Options::getExcerptTypeFeed();
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
        if (Options::isShowEinsatzberichteInMainloop() &&
            $query->is_main_query() &&
            is_home() &&
            empty($query->query_vars['suppress_filters'])
        ) {
            if (isset($query->query_vars['post_type'])) {
                $post_types = (array) $query->query_vars['post_type'];
            } else {
                $post_types = array('post');
            }
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
                $lfd = ($desc ? $query->found_posts : 1);

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
                                $string .= Data::getEinsatznummer($query->post->ID);
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
                                $url = get_permalink($query->post->ID);
                                $string .= '<a href="' . $url . '" rel="bookmark">' . $post_title . '</a>';
                                break;
                            case 'incidentCommander':
                                $string .= Data::getEinsatzleiter($query->post->ID);
                                break;
                            case 'location':
                                $string .= Data::getEinsatzort($query->post->ID);
                                break;
                            case 'workforce':
                                $string .= Data::getMannschaftsstaerke($query->post->ID);
                                break;
                            case 'duration':
                                $minutes = Data::getDauer($query->post->ID);
                                $string .= Utilities::getDurationString($minutes, true);
                                break;
                            case 'vehicles':
                                $vehicles = Data::getFahrzeuge($query->post->ID);
                                $makeFahrzeugLinks = Options::getBoolOption('einsatzvw_list_fahrzeuge_link');
                                $string .= self::getFahrzeugeString($vehicles, $makeFahrzeugLinks, false);
                                break;
                            case 'alarmType':
                                $alarmierungsarten = Data::getAlarmierungsart($query->post->ID);
                                $string .= self::getAlarmierungsartString($alarmierungsarten);
                                break;
                            case 'additionalForces':
                                $exteinsatzmittel = Data::getWeitereKraefte($query->post->ID);
                                $makeLinks = Options::getBoolOption('einsatzvw_list_ext_link');
                                $string .= self::getWeitereKraefteString($exteinsatzmittel, $makeLinks, false);
                                break;
                            case 'incidentType':
                                $einsatzart = Data::getEinsatzart($query->post->ID);
                                $showHierarchy = Options::getBoolOption('einsatzvw_list_art_hierarchy');
                                $string .= self::getEinsatzartString($einsatzart, false, false, $showHierarchy);
                                break;
                            case 'seqNum':
                                $string .= $lfd;
                                break;
                            default:
                                $string .= '&nbsp;';
                        }
                        $string .= '</td>';
                    }

                    $string .= '</tr>';
                    $oldmonth = $month;
                    $lfd += ($desc ? -1 : 1);
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

    /**
     * Gibt die Alarmierungsarten als kommaseparierten String zurück
     *
     * @param array $alarmierungsarten
     *
     * @return string
     */
    public function getAlarmierungsartString($alarmierungsarten)
    {
        if ($alarmierungsarten === false || is_wp_error($alarmierungsarten) || !is_array($alarmierungsarten)) {
            return '';
        }

        $alarmNamen = array();
        foreach ($alarmierungsarten as $alarmart) {
            $alarmNamen[] = $alarmart->name;
        }
        return join(", ", $alarmNamen);
    }

    /**
     * Gibt die Einsatzart als String zurück, wenn vorhanden auch mit den übergeordneten Einsatzarten
     *
     * @param object $einsatzart
     * @param bool $make_links
     * @param bool $show_archive_links
     * @param bool $showHierarchy
     *
     * @return string
     */
    public static function getEinsatzartString($einsatzart, $make_links, $show_archive_links, $showHierarchy = true)
    {
        if ($einsatzart === false || is_wp_error($einsatzart) || empty($einsatzart)) {
            return '';
        }

        $str = '';
        do {
            if (!empty($str)) {
                $str = ' &gt; '.$str;
                $einsatzart = get_term($einsatzart->parent, 'einsatzart');
            }

            if ($make_links && $show_archive_links) {
                $title = 'Alle Eins&auml;tze vom Typ '. $einsatzart->name . ' anzeigen';
                $url = get_term_link($einsatzart);
                $link = '<a href="'.$url.'" class="fa fa-filter" style="text-decoration:none;" title="'.$title.'"></a>';
                $str = '&nbsp;' . $link . $str;
            }
            $str = $einsatzart->name . $str;
        } while ($showHierarchy && $einsatzart->parent != 0);
        return $str;
    }

    /**
     * @param array $fahrzeuge
     * @param bool $makeLinks Fahrzeugname als Link zur Fahrzeugseite angeben, wenn diese eingetragen wurde
     * @param bool $showArchiveLinks Generiere zusätzlichen Link zur Archivseite des Fahrzeugs
     *
     * @return string
     */
    public static function getFahrzeugeString($fahrzeuge, $makeLinks, $showArchiveLinks)
    {
        if ($fahrzeuge === false || is_wp_error($fahrzeuge) || !is_array($fahrzeuge)) {
            return '';
        }

        $fzg_namen = array();
        foreach ($fahrzeuge as $fahrzeug) {
            $fzg_name = $fahrzeug->name;

            if ($makeLinks) {
                $pageid = Taxonomies::getTermField($fahrzeug->term_id, 'fahrzeug', 'fahrzeugpid');
                if ($pageid !== false) {
                    $pageurl = get_permalink($pageid);
                    if ($pageurl !== false) {
                        $fzg_name = '<a href="'.$pageurl.'" title="Mehr Informationen zu '.$fahrzeug->name.'">'.$fahrzeug->name.'</a>';
                    }
                }
            }

            if ($makeLinks && $showArchiveLinks && Options::isShowFahrzeugArchive()) {
                $fzg_name .= '&nbsp;<a href="'.get_term_link($fahrzeug).'" class="fa fa-filter" style="text-decoration:none;" title="Eins&auml;tze unter Beteiligung von '.$fahrzeug->name.' anzeigen"></a>';
            }

            $fzg_namen[] = $fzg_name;
        }
        return join(", ", $fzg_namen);
    }

    /**
     * @param $exteinsatzmittel
     * @param $makeLinks
     * @param $showArchiveLinks
     *
     * @return string
     */
    public static function getWeitereKraefteString($exteinsatzmittel, $makeLinks, $showArchiveLinks)
    {
        if ($exteinsatzmittel === false || is_wp_error($exteinsatzmittel) || !is_array($exteinsatzmittel)) {
            return '';
        }

        $ext_namen = array();
        foreach ($exteinsatzmittel as $ext) {
            $ext_name = $ext->name;

            if ($makeLinks) {
                $url = Taxonomies::getTermField($ext->term_id, 'exteinsatzmittel', 'url');
                if ($url !== false) {
                    $open_in_new_window = Options::isOpenExtEinsatzmittelNewWindow();
                    $ext_name = '<a href="'.$url.'" title="Mehr Informationen zu '.$ext->name.'"';
                    $ext_name .= ($open_in_new_window ? ' target="_blank"' : '') . '>'.$ext->name.'</a>';
                }
            }

            if ($makeLinks && $showArchiveLinks && Options::isShowExtEinsatzmittelArchive()) {
                $title = 'Eins&auml;tze unter Beteiligung von ' . $ext->name . ' anzeigen';
                $ext_name .= '&nbsp;<a href="'.get_term_link($ext).'" class="fa fa-filter" ';
                $ext_name .= 'style="text-decoration:none;" title="' . $title . '"></a>';
            }

            $ext_namen[] = $ext_name;
        }
        return join(", ", $ext_namen);
    }
}
