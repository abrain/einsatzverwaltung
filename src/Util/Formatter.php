<?php
namespace abrain\Einsatzverwaltung\Util;

use abrain\Einsatzverwaltung\Frontend\AnnotationIconBar;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\PermalinkController;
use abrain\Einsatzverwaltung\ReportNumberController;
use abrain\Einsatzverwaltung\Types\AlertingMethod;
use abrain\Einsatzverwaltung\Types\Unit;
use DateTime;
use WP_Post;
use WP_Term;
use function array_map;
use function current_theme_supports;
use function date;
use function date_i18n;
use function esc_attr;
use function esc_html;
use function esc_url;
use function get_permalink;
use function get_term;
use function get_term_link;
use function get_term_meta;
use function get_the_post_thumbnail;
use function has_post_thumbnail;
use function intval;
use function join;
use function sprintf;

/**
 * Formatierungen aller Art
 *
 * @author Andreas Brain
 */
class Formatter
{
    private $tagsNotNeedingPost = array('%feedUrl%', '%yearArchive%');

    /**
     * @var array Ersetzbare Tags und ihre Beschreibungen
     */
    private $availableTags = array(
        '%title%' => 'Titel des Einsatzberichts',
        '%date%' => 'Datum der Alarmierung',
        '%time%' => 'Zeitpunkt der Alarmierung',
        '%endTime%' => 'Datum und Uhrzeit des Einsatzendes',
        '%duration%' => 'Dauer des Einsatzes',
        '%incidentCommander%' => 'Einsatzleiter',
        '%incidentType%' => 'Art des Einsatzes',
        '%incidentTypeHierarchical%' =>'Art des Einsatzes inkl. übergeordneten Einsatzarten',
        '%incidentTypeColor%' => 'Farbe der Art des Einsatzes',
        '%url%' => 'URL zum Einsatzbericht',
        '%location%' => 'Ort des Einsatzes',
        '%feedUrl%' => 'URL zum Feed',
        '%number%' => 'Einsatznummer',
        '%numberRange%' => 'Einsatznummer, ggf. als Intervall',
        '%seqNum%' => 'Laufende Nummer',
        '%annotations%' => 'Vermerke',
        '%vehicles%' => 'Fahrzeuge',
        '%vehiclesByUnit%' => 'Fahrzeuge, gruppiert nach Einheiten',
        '%additionalForces%' => 'Weitere Kr&auml;fte',
        '%typesOfAlerting%' => 'Alarmierungsarten',
        '%content%' => 'Berichtstext',
        '%featuredImage%' => 'Beitragsbild',
        '%featuredImageThumbnail%' => 'Beitragsvorschaubild',
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
     * @var ReportNumberController
     */
    private $reportNumberController;

    /**
     * Formatter constructor.
     *
     * @param Options $options
     * @param PermalinkController $permalinkController
     * @param ReportNumberController $reportNumberController
     */
    public function __construct(Options $options, PermalinkController $permalinkController, ReportNumberController $reportNumberController)
    {
        $this->options = $options;
        $this->permalinkController = $permalinkController;
        $this->annotationIconBar = AnnotationIconBar::getInstance();
        $this->reportNumberController = $reportNumberController;
    }


    /**
     * @param string $pattern
     * @param array $allowedTags
     * @param WP_Post|null $post
     * @param string $context
     *
     * @return mixed
     */
    public function formatIncidentData(string $pattern, $allowedTags = array(), ?WP_Post $post = null, $context = 'post'): string
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
     * @param WP_Post|null $post
     * @param string $pattern
     * @param string $tag
     * @param string $context
     *
     * @return mixed|string
     */
    private function format(?WP_Post $post, string $pattern, string $tag, $context = 'post'): string
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
            case '%endTime%':
                $endTime = $incidentReport->getTimeOfEnding();
                if (empty($endTime)) {
                    $replace = '';
                    break;
                }

                $dateFormat = get_option('date_format', 'd.m.Y');
                $timeFormat = get_option('time_format', 'H:i');
                $endDateTime = DateTime::createFromFormat('Y-m-d H:i', $endTime);
                $replace = date_i18n("$dateFormat $timeFormat", $endDateTime->getTimestamp());
                break;
            case '%incidentCommander%':
                $replace = $incidentReport->getIncidentCommander();
                break;
            case '%incidentType%':
                $replace = $this->getTypeOfIncidentString($incidentReport, $context);
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
            case '%numberRange%':
                if ($incidentReport->getWeight() > 1 && ReportNumberController::isAutoIncidentNumbers()) {
                    $replace = $this->getReportNumberRange($incidentReport);
                } else {
                    $replace = $incidentReport->getNumber();
                }
                break;
            case '%seqNum%':
                $replace = $incidentReport->getSequentialNumber();
                if ($incidentReport->getWeight() > 1) {
                    $firstNumber = intval($replace);
                    $lastNumber = $firstNumber + $incidentReport->getWeight() - 1;
                    $replace = sprintf('%1$d&nbsp;- %2$d', $firstNumber, $lastNumber);
                }
                break;
            case '%annotations%':
                $replace = $this->annotationIconBar->render($incidentReport->getPostId());
                break;
            case '%vehicles%':
                $replace = $this->getVehicleString($incidentReport->getVehicles(), ($context === 'post'), ($context === 'post'));
                break;
            case '%vehiclesByUnit%':
                $replace = $this->getVehiclesByUnitString($incidentReport->getVehiclesByUnit());
                break;
            case '%additionalForces%':
                $replace = $this->getAdditionalForces($incidentReport, ($context === 'post'), ($context === 'post'));
                break;
            case '%typesOfAlerting%':
                $replace = $this->getTypesOfAlerting($incidentReport, ($context === 'post'));
                break;
            case '%content%':
                $replace = $post->post_content;
                break;
            case '%featuredImage%':
                $replace = current_theme_supports('post-thumbnails') ? get_the_post_thumbnail($post->ID) : '';
                break;
            case '%featuredImageThumbnail%':
                $replace = has_post_thumbnail($post->ID) ? get_the_post_thumbnail($post->ID, 'thumbnail') : '';
                break;
            case '%yearArchive%':
                // Take the year of the report, or the current year if used outside a specific report
                $year = $timeOfAlerting ? $timeOfAlerting->format('Y') : date('Y');
                $replace = $this->permalinkController->getYearArchiveLink($year);
                break;
            case '%workforce%':
                $replace = $incidentReport->getWorkforce();
                break;
            case '%units%':
                $replace = $this->getUnits($incidentReport, $context === 'post');
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
    public function getColorOfTypeOfIncident($typeOfIncident): string
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
     * Returns the alerting methods as comma-separated string
     *
     * @param IncidentReport $report
     *
     * @return string
     */
    public function getTypesOfAlerting(IncidentReport $report, bool $makeLinks): string
    {
        if (empty($report)) {
            return '';
        }

        $alertingMethods = $report->getTypesOfAlerting();

        if (empty($alertingMethods)) {
            return '';
        }

        $names = array();
        foreach ($alertingMethods as $alertingMethod) {
            if ($makeLinks === true) {
                $name = $alertingMethod->name;
                $infoUrl = AlertingMethod::getInfoUrl($alertingMethod);
                if (empty($infoUrl)) {
                    $names[] = esc_html($name);
                    continue;
                }

                $names[] = sprintf(
                    '<a href="%s" title="Mehr Informationen über die Alarmierungsart %s">%s</a>',
                    esc_url($infoUrl),
                    esc_attr($name),
                    esc_html($name)
                );
            } else {
                $names[] = esc_html($alertingMethod->name);
            }
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
    public static function getTypeOfIncident(IncidentReport $report, bool $makeLinks, bool $showArchiveLinks, $showHierarchy = true): string
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
                    '<a href="%s" class="fa-solid fa-filter" style="text-decoration:none;" title="%s"></a>',
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
    private function getTypeOfIncidentString(IncidentReport $incidentReport, string $context, $showHierarchy = false): string
    {
        $showTypeArchive = get_option('einsatzvw_show_einsatzart_archive') === '1';
        return $this->getTypeOfIncident($incidentReport, ($context === 'post'), $showTypeArchive, $showHierarchy);
    }

    /**
     * @param IncidentReport $report
     * @param bool $addLinks
     *
     * @return string
     */
    public function getUnits(IncidentReport $report, $addLinks = false): string
    {
        $units = $report->getUnits();

        if ($addLinks) {
            $linkedUnitNames = array_map([$this, 'getUnitNameWithLink'], $units);
            return join(', ', $linkedUnitNames);
        }

        // Only return the names
        $unitNames = array_map(function (WP_Term $unit) {
            return esc_html($unit->name);
        }, $units);

        return join(', ', $unitNames);
    }

    /**
     * Returns the name of a Unit, linked to the respective info page if URL has been set.
     *
     * @param WP_Term $unit
     *
     * @return string
     */
    private function getUnitNameWithLink(WP_Term $unit): string
    {
        $name = $unit->name;

        $infoUrl = Unit::getInfoUrl($unit);
        if (empty($infoUrl)) {
            return esc_html($name);
        }

        return sprintf(
            '<a href="%s" title="Mehr Informationen zu %s">%s</a>',
            esc_url($infoUrl),
            esc_attr($name),
            esc_html($name)
        );
    }

    /**
     * @param WP_Term[] $vehicles
     * @param bool $makeLinks Fahrzeugname als Link zur Fahrzeugseite angeben, wenn diese eingetragen wurde
     * @param bool $showArchiveLinks Generiere zusätzlichen Link zur Archivseite des Fahrzeugs
     *
     * @return string
     */
    public function getVehicleString(array $vehicles, bool $makeLinks, bool $showArchiveLinks): string
    {
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

    public function getVehiclesByUnitString(array $vehiclesByUnitId): string
    {
        if (empty($vehiclesByUnitId)) {
            return '';
        }

        $string = '<ul>';
        foreach ($vehiclesByUnitId as $unitId => $vehicles) {
            if ($unitId === -1) {
                $string .= sprintf(
                    '<li>%s</li>',
                    $this->getVehicleString($vehicles, true, true)
                );
                continue;
            }

            $unit = get_term($unitId, Unit::getSlug());
            if (empty($vehicles)) {
                $string .= sprintf(
                    '<li><b>%s</b></li>',
                    $this->getUnitNameWithLink($unit)
                );
            } else {
                $string .= sprintf(
                    '<li><b>%s:</b> %s</li>',
                    $this->getUnitNameWithLink($unit),
                    $this->getVehicleString($vehicles, true, true)
                );
            }
        }
        $string .= '</ul>';
        return $string;
    }

    /**
     * @param WP_Term $vehicle
     * @return string A link to the page associated with the vehicle (if any), otherwise the name without a link
     */
    private function addVehicleLink(WP_Term $vehicle): string
    {
        $url = $this->getUrlForVehicle($vehicle);
        if (empty($url)) {
            return $vehicle->name;
        }

        return sprintf(
            '<a href="%s" title="Mehr Informationen zu %s">%s</a>',
            esc_url($url),
            esc_attr($vehicle->name),
            esc_html($vehicle->name)
        );
    }

    /**
     * @param WP_Term $vehicle
     *
     * @return string
     */
    private function getUrlForVehicle(WP_Term $vehicle): string
    {
        // The external URL takes precedence over an internal page
        $extUrl = get_term_meta($vehicle->term_id, 'vehicle_exturl', true);
        if (!empty($extUrl)) {
            return $extUrl;
        }

        // Figure out if an internal page has been assigned
        $pageid = get_term_meta($vehicle->term_id, 'fahrzeugpid', true);
        if (empty($pageid)) {
            return '';
        }

        // Try to get the permalink of this page
        $pageUrl = get_permalink($pageid);
        if ($pageUrl === false) {
            return '';
        }

        return $pageUrl;
    }

    /**
     * @param IncidentReport $report
     * @param bool $makeLinks
     * @param bool $showArchiveLinks
     *
     * @return string
     */
    public function getAdditionalForces(IncidentReport $report, bool $makeLinks, bool $showArchiveLinks): string
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
     *
     * @return string
     */
    private function getAdditionalForceLink(WP_Term $additionalForce): string
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
     *
     * @return string
     */
    private function getFilterLink(WP_Term $term): string
    {
        return sprintf(
            '<a href="%s" class="fa-solid fa-filter" style="text-decoration: none;" title="%s"></a>',
            esc_url(get_term_link($term)),
            esc_attr(sprintf('Eins&auml;tze unter Beteiligung von %s anzeigen', $term->name))
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
    public static function getDurationString(int $minutes, $abbreviated = false): string
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
     *
     * @return string
     */
    public function getLabelForTag(string $tag): string
    {
        if (!array_key_exists($tag, $this->availableTags)) {
            return '';
        }

        return $this->availableTags[$tag];
    }

    public function getReportNumberRange(IncidentReport $report): string
    {
        if ($report->getWeight() === 1 || ReportNumberController::isAutoIncidentNumbers() === false) {
            return $report->getNumber();
        }

        $year = intval($report->getTimeOfAlerting()->format('Y'));
        return $this->reportNumberController->formatNumberRange($year, intval($report->getSequentialNumber()), $report->getWeight());
    }
}
