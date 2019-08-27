<?php
namespace abrain\Einsatzverwaltung\Util;

use abrain\Einsatzverwaltung\Frontend\AnnotationIconBar;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\PermalinkController;
use WP_Post;
use WP_Term;

/**
 * Formatierungen aller Art
 *
 * @author Andreas Brain
 */
class Formatter
{
    private $tagsNotNeedingPost = array('%feedUrl%');

    /**
     * @var array Ersetzbare Tags und ihre Beschreibungen
     */
    private $availableTags = array(
        '%title%' => 'Titel des Einsatzberichts',
        '%date%' => 'Datum der Alarmierung',
        '%time%' => 'Zeitpunkt der Alarmierung',
        '%duration%' => 'Dauer des Einsatzes',
        '%incidentCommander%' => 'Einsatzleiter',
        '%incidentType%' => 'Art des Einsatzes',
        '%incidentTypeHierarchical%' =>'Art des Einsatzes inkl. übergeordneten Einsatzarten',
        '%incidentTypeColor%' => 'Farbe der Art des Einsatzes',
        '%url%' => 'URL zum Einsatzbericht',
        '%location%' => 'Ort des Einsatzes',
        '%feedUrl%' => 'URL zum Feed',
        '%number%' => 'Einsatznummer',
        '%seqNum%' => 'Laufende Nummer',
        '%annotations%' => 'Vermerke',
        '%vehicles%' => 'Fahrzeuge',
        '%additionalForces%' => 'Weitere Kr&auml;fte',
        '%typesOfAlerting%' => 'Alarmierungsarten',
        '%content%' => 'Berichtstext',
        '%featuredImage%' => 'Beitragsbild',
        '%yearArchive%' => 'Link zum Jahresarchiv',
        '%workforce%' => 'Mannschaftsstärke',
        '%units%' => 'Einheiten',
    );

    /**
     * @var AnnotationIconBar
     */
    private $annotationIconBar;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var PermalinkController
     */
    private $permalinkController;

    /**
     * Formatter constructor.
     *
     * @param Options $options
     * @param PermalinkController $permalinkController
     */
    public function __construct(Options $options, PermalinkController $permalinkController)
    {
        $this->options = $options;
        $this->permalinkController = $permalinkController;
        $this->annotationIconBar = AnnotationIconBar::getInstance();
    }


    /**
     * @param string $pattern
     * @param array $allowedTags
     * @param WP_Post $post
     * @param string $context
     *
     * @return mixed
     */
    public function formatIncidentData($pattern, $allowedTags = array(), $post = null, $context = 'post')
    {
        if (empty($allowedTags)) {
            $allowedTags = array_keys($this->availableTags);
        }

        // Content should be handled separately, so we will ignore it
        $allowedTags = array_filter($allowedTags, function ($tag) {
            return $tag !== '%content%';
        });

        $formattedString = $pattern;
        foreach ($allowedTags as $tag) {
            $formattedString = $this->format($post, $formattedString, $tag, $context);
        }
        return $formattedString;
    }

    /**
     * @param WP_Post $post
     * @param string $pattern
     * @param string $tag
     * @param string $context
     * @return mixed|string
     */
    private function format($post, $pattern, $tag, $context = 'post')
    {
        if ($post == null && !in_array($tag, $this->tagsNotNeedingPost)) {
            return $pattern;
        }

        $incidentReport = new IncidentReport($post);
        $timeOfAlerting = $incidentReport->getTimeOfAlerting();

        switch ($tag) {
            case '%title%':
                $replace = get_the_title($post);
                if (empty($replace)) {
                    $replace = '(kein Titel)';
                }
                break;
            case '%date%':
                $replace = date_i18n(get_option('date_format', 'd.m.Y'), $timeOfAlerting->getTimestamp());
                break;
            case '%time%':
                $replace = date_i18n(get_option('time_format', 'H:i'), $timeOfAlerting->getTimestamp());
                break;
            case '%duration%':
                $replace = $this->getDurationString($incidentReport->getDuration());
                break;
            case '%incidentCommander%':
                $replace = $incidentReport->getIncidentCommander();
                break;
            case '%incidentType%':
                $replace = $this->getTypeOfIncidentString($incidentReport, $context, false);
                break;
            case '%incidentTypeHierarchical%':
                $replace = $this->getTypeOfIncidentString($incidentReport, $context, true);
                break;
            case '%incidentTypeColor%':
                $replace = $this->getColorOfTypeOfIncident($incidentReport->getTypeOfIncident());
                break;
            case '%url%':
                $replace = get_permalink($post->ID);
                break;
            case '%location%':
                $replace = $incidentReport->getLocation();
                break;
            case '%feedUrl%':
                $replace = get_post_type_archive_feed_link('einsatz');
                break;
            case '%number%':
                $replace = $incidentReport->getNumber();
                break;
            case '%seqNum%':
                $replace = $incidentReport->getSequentialNumber();
                break;
            case '%annotations%':
                $replace = $this->annotationIconBar->render($incidentReport);
                break;
            case '%vehicles%':
                $replace = $this->getVehicles($incidentReport, ($context === 'post'), ($context === 'post'));
                break;
            case '%additionalForces%':
                $replace = $this->getAdditionalForces($incidentReport, ($context === 'post'), ($context === 'post'));
                break;
            case '%typesOfAlerting%':
                $replace = $this->getTypesOfAlerting($incidentReport);
                break;
            case '%content%':
                $replace = $post->post_content;
                break;
            case '%featuredImage%':
                $replace = current_theme_supports('post-thumbnails') ? get_the_post_thumbnail($post->ID) : '';
                break;
            case '%yearArchive%':
                $year = $timeOfAlerting->format('Y');
                $replace = $this->permalinkController->getYearArchiveLink($year);
                break;
            case '%workforce%':
                $replace = $incidentReport->getWorkforce();
                break;
            case '%units%':
                $replace = $this->getUnits($incidentReport);
                break;
            default:
                return $pattern;
        }

        return str_replace($tag, $replace, $pattern);
    }

    /**
     * @param WP_Term|false $typeOfIncident
     * @return string
     */
    public function getColorOfTypeOfIncident($typeOfIncident)
    {
        if (empty($typeOfIncident)) {
            return 'inherit';
        }

        $color = get_term_meta($typeOfIncident->term_id, 'typecolor', true);
        while (empty($color) && $typeOfIncident->parent !== 0) {
            $typeOfIncident = WP_Term::get_instance($typeOfIncident->parent);
            $color = get_term_meta($typeOfIncident->term_id, 'typecolor', true);
        }

        if (empty($color)) {
            return 'inherit';
        }

        return $color;
    }

    /**
     * Gibt die Alarmierungsarten als kommaseparierten String zurück
     *
     * @param IncidentReport $report
     *
     * @return string
     */
    public function getTypesOfAlerting($report)
    {
        if (empty($report)) {
            return '';
        }

        $typesOfAlerting = $report->getTypesOfAlerting();

        if (empty($typesOfAlerting)) {
            return '';
        }

        $names = array();
        foreach ($typesOfAlerting as $type) {
            $names[] = $type->name;
        }
        return join(", ", $names);
    }

    /**
     * Gibt die Einsatzart als String zurück, wenn vorhanden auch mit den übergeordneten Einsatzarten
     *
     * @param IncidentReport $report
     * @param bool $makeLinks
     * @param bool $showArchiveLinks
     * @param bool $showHierarchy
     *
     * @return string
     */
    public static function getTypeOfIncident($report, $makeLinks, $showArchiveLinks, $showHierarchy = true)
    {
        if (empty($report)) {
            return '';
        }

        $typeOfIncident = $report->getTypeOfIncident();

        if (empty($typeOfIncident)) {
            return '';
        }

        $string = '';
        do {
            if (!empty($string)) {
                $string = ' &gt; '.$string;
                $typeOfIncident = get_term($typeOfIncident->parent, 'einsatzart');
            }

            if ($makeLinks && $showArchiveLinks) {
                $link = sprintf(
                    '<a href="%s" class="fa fa-filter" style="text-decoration:none;" title="%s"></a>',
                    esc_url(get_term_link($typeOfIncident)),
                    esc_attr(sprintf('Alle Eins&auml;tze vom Typ %s anzeigen', $typeOfIncident->name))
                );
                $string = '&nbsp;' . $link . $string;
            }
            $string = $typeOfIncident->name . $string;
        } while ($showHierarchy && $typeOfIncident->parent != 0);
        return $string;
    }

    /**
     * @param IncidentReport $incidentReport
     * @param string $context
     * @param bool $showHierarchy
     *
     * @return string
     */
    private function getTypeOfIncidentString(IncidentReport $incidentReport, $context, $showHierarchy = false)
    {
        $showTypeArchive = get_option('einsatzvw_show_einsatzart_archive') === '1';
        return $this->getTypeOfIncident($incidentReport, ($context === 'post'), $showTypeArchive, $showHierarchy);
    }

    /**
     * @param IncidentReport $report
     *
     * @return string
     */
    public function getUnits(IncidentReport $report)
    {
        $units = $report->getUnits();
        $unitNames = array_map(function (WP_Post $unit) {
            return sanitize_post_field('post_title', $unit->post_title, $unit->ID);
        }, $units);

        return join(', ', $unitNames);
    }

    /**
     * @param IncidentReport $report
     * @param bool $makeLinks Fahrzeugname als Link zur Fahrzeugseite angeben, wenn diese eingetragen wurde
     * @param bool $showArchiveLinks Generiere zusätzlichen Link zur Archivseite des Fahrzeugs
     *
     * @return string
     */
    public function getVehicles($report, $makeLinks, $showArchiveLinks)
    {
        if (empty($report)) {
            return '';
        }

        $vehicles = $report->getVehicles();

        if (empty($vehicles)) {
            return '';
        }

        $names = array();
        foreach ($vehicles as $vehicle) {
            $name = $vehicle->name;

            if ($makeLinks) {
                $name = $this->addVehicleLink($vehicle);
            }

            if ($makeLinks && $showArchiveLinks && $this->options->isShowFahrzeugArchive()) {
                $name .= '&nbsp;' . $this->getFilterLink($vehicle);
            }

            $names[] = $name;
        }
        return join(", ", $names);
    }

    /**
     * @param WP_Term $vehicle
     * @return string A link to the page associated with the vehicle (if any), otherwise the name without a link
     */
    private function addVehicleLink($vehicle)
    {
        $pageid = get_term_meta($vehicle->term_id, 'fahrzeugpid', true);
        if (empty($pageid)) {
            return $vehicle->name;
        }

        $pageurl = get_permalink($pageid);
        if ($pageurl === false) {
            return $vehicle->name;
        }

        return sprintf(
            '<a href="%s" title="Mehr Informationen zu %s">%s</a>',
            esc_url($pageurl),
            esc_attr($vehicle->name),
            esc_html($vehicle->name)
        );
    }

    /**
     * @param IncidentReport $report
     * @param bool $makeLinks
     * @param bool $showArchiveLinks
     *
     * @return string
     */
    public function getAdditionalForces($report, $makeLinks, $showArchiveLinks)
    {
        if (empty($report)) {
            return '';
        }

        $additionalForces = $report->getAdditionalForces();

        if (empty($additionalForces)) {
            return '';
        }

        $names = array();
        foreach ($additionalForces as $force) {
            $name = $force->name;

            if ($makeLinks) {
                $name = $this->getAdditionalForceLink($force);
            }

            if ($makeLinks && $showArchiveLinks && $this->options->isShowExtEinsatzmittelArchive()) {
                $name .= '&nbsp;' . $this->getFilterLink($force);
            }

            $names[] = $name;
        }
        return join(", ", $names);
    }

    /**
     * @param WP_Term $additionalForce
     * @return string
     */
    private function getAdditionalForceLink($additionalForce)
    {
        $url = get_term_meta($additionalForce->term_id, 'url', true);
        if (empty($url)) {
            return $additionalForce->name;
        }

        $openInNewWindow = $this->options->isOpenExtEinsatzmittelNewWindow();
        return sprintf(
            '<a href="%s" title="%s" target="%s">%s</a>',
            esc_url($url),
            esc_attr(sprintf('Mehr Informationen zu %s', $additionalForce->name)),
            esc_attr($openInNewWindow ? '_blank' : '_self'),
            esc_html($additionalForce->name)
        );
    }

    /**
     * @param WP_Term $term
     * @return string
     */
    private function getFilterLink(WP_Term $term)
    {
        return sprintf(
            '<a href="%s" class="fa fa-filter" style="text-decoration: none;" title="%s"></a>',
            get_term_link($term),
            sprintf('Eins&auml;tze unter Beteiligung von %s anzeigen', $term->name)
        );
    }

    /**
     * Gibt eine lesbare Angabe einer Dauer zurück (z.B. 2 Stunden 12 Minuten)
     *
     * @param int $minutes Dauer in Minuten
     * @param bool $abbreviated
     *
     * @return string
     */
    public static function getDurationString($minutes, $abbreviated = false)
    {
        if (!is_numeric($minutes) || $minutes < 0) {
            return '';
        }

        if ($minutes < 60) {
            $unit = $abbreviated ? 'min' : _n('minute', 'minutes', $minutes, 'einsatzverwaltung');
            return sprintf('%d %s', $minutes, $unit);
        }

        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;
        $dauerstring = $hours . ' ' . ($abbreviated ? 'h' : _n('hour', 'hours', $hours, 'einsatzverwaltung'));
        if ($remainingMinutes > 0) {
            $unit = $abbreviated ? 'min' : _n('minute', 'minutes', $remainingMinutes, 'einsatzverwaltung');
            $dauerstring .= sprintf(' %d %s', $remainingMinutes, $unit);
        }

        return $dauerstring;
    }

    /**
     * @param string $tag
     * @return string
     */
    public function getLabelForTag($tag)
    {
        if (!array_key_exists($tag, $this->availableTags)) {
            return '';
        }

        return $this->availableTags[$tag];
    }
}
