<?php
namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\PermalinkController;
use function esc_url;

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

    /**
     * ReportArchives constructor.
     *
     * @param Data $data
     * @param PermalinkController $permalinkController
     */
    public function __construct(Data $data, PermalinkController $permalinkController)
    {
        parent::__construct([
            'add_queried_year' => 'yes',
            'force_current_year' => 'no',
            'limit' => 0,
            'sort' => 'DESC',
        ]);

        $this->data = $data;
        $this->permalinkController = $permalinkController;
    }

    /**
     * @inheritDoc
     */
    public function render($attributes): string
    {
        global $year;
        $thisYear = intval(date('Y'));
        $queriedYear = empty($year) ? $thisYear : $year;
        $yearsWithReports = $this->data->getYearsWithReports();

        $attributes = $this->getAttributes($attributes);

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
    private function getAnchorsForYears(array $yearsWithReports, int $queriedYear): array
    {
        $anchors = array();
        foreach ($yearsWithReports as $currentYear) {
            if ($currentYear === $queriedYear) {
                $textFormat = '<strong>%d</strong>';
                $anchorFormat = '<a href="%s" aria-current="page">%s</a>';
            } else {
                $textFormat = '%d';
                $anchorFormat = '<a href="%s">%s</a>';
            }

            $text = sprintf($textFormat, $currentYear);
            $anchors[] = sprintf(
                $anchorFormat,
                esc_url($this->permalinkController->getYearArchiveLink($currentYear)),
                $text
            );
        }
        return $anchors;
    }

    /**
     * @param int[] $yearsWithReports
     * @param string $sort
     * @param string $limit
     *
     * @return int[]
     */
    private function sortAndLimit(array $yearsWithReports, string $sort, string $limit): array
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
