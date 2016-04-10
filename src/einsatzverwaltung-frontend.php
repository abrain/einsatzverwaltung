<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Frontend\ReportList;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Util\Formatter;
use WP_Post;
use WP_Query;

/**
 * Generiert alle Inhalte für das Frontend, mit Ausnahme der Shortcodes und des Widgets
 */
class Frontend
{
    /**
     * @var Core
     */
    private $core;

    /**
     * @var Options
     */
    private $options;
    
    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * Constructor
     *
     * @param Core $core
     * @param Options $options
     * @param Utilities $utilities
     */
    public function __construct($core, $options, $utilities)
    {
        $this->core = $core;
        $this->options = $options;
        $this->utilities = $utilities;
        $this->addHooks();
    }

    private function addHooks()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueueStyleAndScripts'));
        add_filter('the_content', array($this, 'renderContent'));
        add_filter('the_excerpt', array($this, 'filterEinsatzExcerpt'));
        add_filter('the_excerpt_rss', array($this, 'filterEinsatzExcerptFeed'));
        add_action('pre_get_posts', array($this, 'addReportsToQuery'));
    }

    /**
     * Bindet CSS für das Frontend ein
     */
    public function enqueueStyleAndScripts()
    {
        wp_enqueue_style(
            'font-awesome',
            $this->core->pluginUrl . 'font-awesome/css/font-awesome.min.css',
            false,
            '4.4.0'
        );
        wp_enqueue_style(
            'einsatzverwaltung-frontend',
            $this->core->styleUrl . 'style-frontend.css',
            array(),
            Core::VERSION
        );
        wp_add_inline_style('einsatzverwaltung-frontend', ReportList::getDynamicCss());
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
     * @param bool $mayContainLinks True, wenn Links generiert werden dürfen
     * @param bool $showArchiveLinks Bestimmt, ob Links zu Archivseiten generiert werden dürfen
     *
     * @return string Auflistung der Einsatzdetails
     */
    public function getEinsatzberichtHeader($post, $mayContainLinks = true, $showArchiveLinks = true)
    {
        if (get_post_type($post) == "einsatz") {
            $report = new IncidentReport($post);
            $formatter = new Formatter($this->options, $this->utilities);

            $typesOfAlerting = $formatter->getTypesOfAlerting($report);

            $duration = Data::getDauer($report);
            $durationString = ($duration === false ? '' : $this->utilities->getDurationString($duration));

            $showEinsatzartArchiveLink = $showArchiveLinks && $this->options->isShowEinsatzartArchive();
            $art = $formatter->getTypeOfIncident($report, $mayContainLinks, $showEinsatzartArchiveLink);

            if ($report->isFalseAlarm()) {
                $art = (empty($art) ? 'Fehlalarm' : $art.' (Fehlalarm)');
            }

            $einsatzort = $report->getLocation();
            $einsatzleiter = $report->getIncidentCommander();
            $mannschaft = $report->getWorkforce();

            $vehicles = $formatter->getVehicles($report, $mayContainLinks, $showArchiveLinks);
            $additionalForces = $formatter->getAdditionalForces($report, $mayContainLinks, $showArchiveLinks);

            $timeOfAlerting = $report->getTimeOfAlerting();
            $datumsformat = $this->options->getDateFormat();
            $zeitformat = $this->options->getTimeFormat();
            $einsatz_datum = ($timeOfAlerting ? date_i18n($datumsformat, $timeOfAlerting->getTimestamp()) : '-');
            $einsatz_zeit = ($timeOfAlerting ? date_i18n($zeitformat, $timeOfAlerting->getTimestamp()).' Uhr' : '-');

            $headerstring = "<strong>Datum:</strong> ".$einsatz_datum."&nbsp;<br>";
            $headerstring .= "<strong>Alarmzeit:</strong> ".$einsatz_zeit."&nbsp;<br>";
            $headerstring .= $this->getDetailString('Alarmierungsart:', $typesOfAlerting);
            $headerstring .= $this->getDetailString('Dauer:', $durationString);
            $headerstring .= $this->getDetailString('Art:', $art);
            $headerstring .= $this->getDetailString('Einsatzort:', $einsatzort);
            $headerstring .= $this->getDetailString('Einsatzleiter:', $einsatzleiter);
            $headerstring .= $this->getDetailString('Mannschaftsst&auml;rke:', $mannschaft);
            $headerstring .= $this->getDetailString('Fahrzeuge:', $vehicles);
            $headerstring .= $this->getDetailString('Weitere Kr&auml;fte:', $additionalForces);

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
        if ($this->options->isHideEmptyDetails() && (!isset($value) || $value === '')) {
            return '';
        }

        return '<strong>'.$title.'</strong> '.$value.($newline ? '&nbsp;<br>' : '&nbsp;');
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

        // Wenn Beiträge durch ein Passwort geschützt sind, werden auch keine Einsatzdetails preisgegeben
        if (post_password_required()) {
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

        $excerptType = $this->options->getExcerptType();

        // Kein Eingriff in das normale Verhalten von WordPress
        if ('default' == $excerptType) {
            return $excerpt;
        }

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

        $excerptType = $this->options->getExcerptTypeFeed();

        // Kein Eingriff in das normale Verhalten von WordPress
        if ('default' == $excerptType) {
            return $excerpt;
        }

        $getExcerpt = $this->getEinsatzExcerpt($post, $excerptType, true, false);
        $getExcerpt = str_replace('<strong>', '', $getExcerpt);
        $getExcerpt = str_replace('</strong>', '', $getExcerpt);
        return $getExcerpt;
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
    public function addReportsToQuery($query)
    {
        // Nur, wenn Filter erlaubt sind, soll weitergemacht werden
        if (!empty($query->query_vars['suppress_filters'])) {
            return;
        }

        // Im Adminbereich wird nicht herumgepfuscht!
        if (is_admin()) {
            return;
        }

        // Bei Abfragen einzelner Posts gibt es auch nichts zu ändern
        if ($query->is_singular()) {
            return;
        }

        $categoryId = $this->options->getEinsatzberichteCategory();
        if ($this->options->isShowReportsInLoop() || $query->is_category($categoryId)) {
            // Einsatzberichte mit abfragen
            if (isset($query->query_vars['post_type'])) {
                $postTypes = (array) $query->query_vars['post_type'];
            } else {
                $postTypes = array('post');
            }
            
            // Einsatzberichte nur zusammen mit Beiträgen abfragen
            if (!in_array('post', $postTypes)) {
                return;
            }

            $postTypes[] = 'einsatz';
            $query->set('post_type', $postTypes);

            if ($this->options->isOnlySpecialInLoop()) {
                // Nur als besonders markierte Einsatzberichte abfragen
                $metaQuery = $query->get('meta_query');
                if (empty($metaQuery)) {
                    $metaQuery = array();
                }
                $metaQuery['relation'] = 'OR';
                $metaQuery[] = array(
                    'key' => 'einsatz_special',
                    'value' => '1'
                );
                $metaQuery[] = array(
                    'key' => 'einsatz_special',
                    'value' => '1',
                    'compare' => 'NOT EXISTS'
                );
                $query->set('meta_query', $metaQuery);
            }
        }
    }
}
