<?php

namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Data;

/**
 * Renders links to yearly archives for the shortcode [einsatzjahre]
 */
class ReportArchives
{
    /**
     * @var Core
     */
    private $core;

    /**
     * ReportArchives constructor.
     *
     * @param Core $core
     */
    public function __construct(Core $core)
    {
        $this->core = $core;
    }

    /**
     * Gibt Links zu den Archivseiten der Jahre, in denen Einsatzberichte existieren, zurück
     *
     * @param array $atts Parameter des Shortcodes
     *
     * @return string
     */
    public function render($atts)
    {
        global $year;
        $thisYear = intval(date('Y'));
        $queriedYear = empty($year) ? $thisYear : $year;
        $yearsWithReports = Data::getYearsWithReports();

        $shortcodeParams = shortcode_atts(array(
            'add_queried_year' => 'yes',
            'force_current_year' => 'no',
            'limit' => 0,
            'sort' => 'DESC',
        ), $atts);

        if ($shortcodeParams['add_queried_year'] !== 'no' && !in_array($queriedYear, $yearsWithReports)) {
            $yearsWithReports[] = $queriedYear;
        }

        if ($shortcodeParams['force_current_year'] === 'yes' && !in_array($thisYear, $yearsWithReports)) {
            $yearsWithReports[] = $thisYear;
        }

        rsort($yearsWithReports);

        if (is_numeric($shortcodeParams['limit']) && $shortcodeParams['limit'] > 0) {
            $yearsWithReports = array_slice($yearsWithReports, 0, $shortcodeParams['limit']);
        }

        if ($shortcodeParams['sort'] === 'ASC') {
            sort($yearsWithReports);
        }

        $links = array();
        foreach ($yearsWithReports as $currentYear) {
            $format = $currentYear === $queriedYear ? '<strong>%d</strong>' : '%d';
            $text = sprintf($format, $currentYear);

            $links[] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($this->core->getYearArchiveLink($currentYear)),
                $text
            );
        }

        return join(' | ', $links);
    }
}
