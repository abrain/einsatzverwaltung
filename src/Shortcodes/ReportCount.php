<?php
namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ReportQuery;
use abrain\Einsatzverwaltung\ReportStatus;
use function array_key_exists;
use function array_reduce;
use function in_array;
use function is_numeric;

/**
 * Shows a number of incident reports for the shortcode [reportcount]
 * @package abrain\Einsatzverwaltung\Shortcodes
 */
class ReportCount extends AbstractShortcode
{
    /**
     * @var ReportQuery
     */
    private $reportQuery;

    /**
     * ReportCount constructor.
     *
     * @param ReportQuery $reportQuery
     */
    public function __construct(ReportQuery $reportQuery)
    {
        parent::__construct([
            'status' => '',
            'icategories' => '',
            'ignore_weights' => 'no',
            'alertingmethods' => '',
            'units' => '',
            'year' => ''
        ]);

        $this->reportQuery = $reportQuery;
    }

    /**
     * @inheritDoc
     */
    public function render($attributes): string
    {
        $attributes = $this->getAttributes($attributes);
        $year = $this->getYear($attributes['year']);

        $this->reportQuery->resetQueryVars();
        if (is_int($year)) {
            $this->reportQuery->setYear(intval($year));
        }

        $alertingMethodIds = $this->getIntegerList($attributes['alertingmethods']);
        if (!empty($alertingMethodIds)) {
            $this->reportQuery->setAlertingMethodIds($alertingMethodIds);
        }

        $incidentCategoryIds = $this->getIntegerList($attributes['icategories']);
        if (!empty($incidentCategoryIds)) {
            $this->reportQuery->setIncidentTypeIds($incidentCategoryIds);
        }

        $status = $this->getStringList($attributes['status'], ['actual', 'falseAlarm']);
        if (!empty($status)) {
            $reportStatus = [];
            if (in_array('actual', $status)) {
                $reportStatus[] = ReportStatus::ACTUAL;
            }
            if (in_array('falseAlarm', $status)) {
                $reportStatus[] = ReportStatus::FALSE_ALARM;
            }
            $this->reportQuery->setOnlyReportStatus($reportStatus);
        }

        $units = $this->getIntegerList($attributes['units']);
        if (!empty($units)) {
            $this->reportQuery->setUnits($this->translateOldUnitIds($units));
        }

        $incidentReports = $this->reportQuery->getReports();
        if ($attributes['ignore_weights'] === 'yes') {
            $reportCount = count($incidentReports);
        } else {
            $reportCount = array_reduce($incidentReports, function ($sum, IncidentReport $report) {
                return $sum + $report->getWeight();
            }, 0);
        }

        return sprintf('%d', $reportCount);
    }

    /**
     * @inheritDoc
     */
    protected function fixOutdatedAttributes(array $attributes): array
    {
        // 'einsatzart' has been renamed to 'icategories'
        if (array_key_exists('einsatzart', $attributes) && !array_key_exists('icategories', $attributes) &&
            is_numeric($attributes['einsatzart'])) {
            $attributes['icategories'] = $attributes['einsatzart'];
        }

        return $attributes;
    }

    /**
     * Converts the value of the shortcode's year attribute to a number that can be used in a query for posts.
     *
     * @param string $value Value of the year attribute
     *
     * @return int|string A numeric year or an empty string in case of an empty or erroneous attribute
     */
    private function getYear(string $value)
    {
        $currentYear = intval(date('Y'));
        if ($value === 'current') {
            return $currentYear;
        }

        if ($value === '' || !is_numeric($value)) {
            return '';
        }

        $number = intval($value);
        if ($number < 0) {
            return $currentYear + $number;
        }

        return $number;
    }
}
