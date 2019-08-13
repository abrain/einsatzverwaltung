<?php

namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\PermalinkController;
use function esc_url;
use function shortcode_atts;

/**
 * Renders links to yearly archives for the shortcode [einsatzjahre]
 */
class ReportArchives extends AbstractShortcode
{
    /**
     * @var Data
     */
    private $data;

    /**
     * @var PermalinkController
     */
    private $permalinkController;

    private $defaultAttributes = array(
        'add_queried_year' => 'yes',
        'force_current_year' => 'no',
        'limit' => 0,
        'sort' => 'DESC',
    );

    /**
     * ReportArchives constructor.
     *
     * @param Data $data
     * @param PermalinkController $permalinkController
     */
    public function __construct(Data $data, PermalinkController $permalinkController)
    {
        $this->data = $data;
        $this->permalinkController = $permalinkController;
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
        $yearsWithReports = $this->data->getYearsWithReports();

        $attributes = shortcode_atts($this->defaultAttributes, $atts);

        if ($attributes['add_queried_year'] !== 'no' && !in_array($queriedYear, $yearsWithReports)) {
            $yearsWithReports[] = $queriedYear;
        }

        if ($attributes['force_current_year'] === 'yes' && !in_array($thisYear, $yearsWithReports)) {
            $yearsWithReports[] = $thisYear;
        }

        $yearsWithReports = $this->sortAndLimit($yearsWithReports, $attributes['sort'], $attributes['limit']);

        return join(' | ', $this->getAnchorsForYears($yearsWithReports, $queriedYear));
    }

    /**
     * @param int[] $yearsWithReports
     * @param int $queriedYear
     *
     * @return string[]
     */
    private function getAnchorsForYears($yearsWithReports, $queriedYear)
    {
        $anchors = array();
        foreach ($yearsWithReports as $currentYear) {
            $format = $currentYear === $queriedYear ? '<strong>%d</strong>' : '%d';
            $text = sprintf($format, $currentYear);

            $anchors[] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($this->permalinkController->getYearArchiveLink($currentYear)),
                $text
            );
        }
        return $anchors;
    }

    /**
     * @param array $yearsWithReports
     * @param string $sort
     * @param string $limit
     *
     * @return array
     */
    private function sortAndLimit($yearsWithReports, $sort, $limit)
    {
        // Always sort in descending order so that the most current year is at index 0
        rsort($yearsWithReports);

        // In case we need to limit, the most current years will remain
        if (is_numeric($limit) && $limit > 0) {
            $yearsWithReports = array_slice($yearsWithReports, 0, $limit);
        }

        // If desired we now can sort in ascending order
        if ($sort === 'ASC') {
            sort($yearsWithReports);
        }

        return $yearsWithReports;
    }
}
