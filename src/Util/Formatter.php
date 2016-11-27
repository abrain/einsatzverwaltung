<?php
namespace abrain\Einsatzverwaltung\Util;

use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\Frontend;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\Taxonomies;
use abrain\Einsatzverwaltung\Utilities;
use WP_Post;

/**
 * Formatierungen aller Art
 *
 * @author Andreas Brain
 */
class Formatter
{
    private $tagsNotNeedingPost = array('%feedUrl%');

    /**
     * @var Options
     */
    private $options;

    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * Formatter constructor.
     * @param Options $options
     * @param Utilities $utilities
     */
    public function __construct($options, $utilities)
    {
        $this->options = $options;
        $this->utilities = $utilities;
    }


    /**
     * @param string $pattern
     * @param array $allowedTags
     * @param WP_Post $post
     *
     * @return mixed
     */
    public function formatIncidentData($pattern, $allowedTags = array(), $post = null)
    {
        if (empty($allowedTags)) {
            $allowedTags = array_keys($this->getTags());
        }

        $formattedString = $pattern;
        foreach ($allowedTags as $tag) {
            $formattedString = $this->format($post, $formattedString, $tag);
        }
        return $formattedString;
    }

    /**
     * @param WP_Post $post
     * @param string $pattern
     * @param string $tag
     * @return mixed|string
     */
    private function format($post, $pattern, $tag)
    {
        if ($post == null && !in_array($tag, $this->tagsNotNeedingPost)) {
            $message = 'Alle Tags außer ' . implode(',', $this->tagsNotNeedingPost) . ' brauchen ein Post-Objekt';
            _doing_it_wrong(__FUNCTION__, $message, null);
            return '';
        }

        $incidentReport = new IncidentReport($post);
        $timeOfAlerting = $incidentReport->getTimeOfAlerting();

        switch ($tag) {
            case '%title%':
                $replace = get_the_title($post);
                break;
            case '%date%':
                $replace = date_i18n($this->options->getDateFormat(), $timeOfAlerting->getTimestamp());
                break;
            case '%time%':
                $replace = date_i18n($this->options->getTimeFormat(), $timeOfAlerting->getTimestamp());
                break;
            case '%duration%':
                $replace = $this->utilities->getDurationString(Data::getDauer($incidentReport));
                break;
            case '%incidentType%':
                $replace = $this->getTypeOfIncident($incidentReport, false, false, false);
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
            default:
                return $pattern;
        }

        return str_replace($tag, $replace, $pattern);
    }

    /**
     * @return array Ersetzbare Tags und ihre Beschreibungen
     */
    public function getTags()
    {
        return array(
            '%title%' => 'Titel des Einsatzberichts',
            '%date%' => 'Datum der Alarmierung',
            '%time%' => 'Zeitpunkt der Alarmierung',
            '%duration%' => 'Dauer des Einsatzes',
            '%incidentType%' => 'Art des Einsatzes',
            '%url%' => 'URL zum Einsatzbericht',
            '%location%' => 'Ort des Einsatzes',
            '%feedUrl%' => 'URL zum Feed',
            '%number%' => 'Einsatznummer',
            '%seqNum%' => 'Laufende Nummer',
        );
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
                $title = 'Alle Eins&auml;tze vom Typ '. $typeOfIncident->name . ' anzeigen';
                $url = get_term_link($typeOfIncident);
                $link = '<a href="'.$url.'" class="fa fa-filter" style="text-decoration:none;" title="'.$title.'"></a>';
                $string = '&nbsp;' . $link . $string;
            }
            $string = $typeOfIncident->name . $string;
        } while ($showHierarchy && $typeOfIncident->parent != 0);
        return $string;
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
                $pageid = Taxonomies::getTermField($vehicle->term_id, 'fahrzeug', 'fahrzeugpid');
                if (!empty($pageid)) {
                    $pageurl = get_permalink($pageid);
                    if ($pageurl !== false) {
                        $name = '<a href="'.$pageurl.'" title="Mehr Informationen zu '.$vehicle->name.'">'.$vehicle->name.'</a>';
                    }
                }
            }

            if ($makeLinks && $showArchiveLinks && $this->options->isShowFahrzeugArchive()) {
                $name .= '&nbsp;<a href="'.get_term_link($vehicle).'" class="fa fa-filter" style="text-decoration:none;" title="Eins&auml;tze unter Beteiligung von '.$vehicle->name.' anzeigen"></a>';
            }

            $names[] = $name;
        }
        return join(", ", $names);
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
                $url = Taxonomies::getTermField($force->term_id, 'exteinsatzmittel', 'url');
                if (!empty($url)) {
                    $openInNewWindow = $this->options->isOpenExtEinsatzmittelNewWindow();
                    $name = '<a href="'.$url.'" title="Mehr Informationen zu '.$force->name.'"';
                    $name .= ($openInNewWindow ? ' target="_blank"' : '') . '>'.$force->name.'</a>';
                }
            }

            if ($makeLinks && $showArchiveLinks && $this->options->isShowExtEinsatzmittelArchive()) {
                $title = 'Eins&auml;tze unter Beteiligung von ' . $force->name . ' anzeigen';
                $name .= '&nbsp;<a href="'.get_term_link($force).'" class="fa fa-filter" ';
                $name .= 'style="text-decoration:none;" title="' . $title . '"></a>';
            }

            $names[] = $name;
        }
        return join(", ", $names);
    }
}
