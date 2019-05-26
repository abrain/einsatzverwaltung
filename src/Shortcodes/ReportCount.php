<?php

namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\ReportQuery;

/**
 * Shows a number of incident reports for the shortcode [reportcount]
 * @package abrain\Einsatzverwaltung\Shortcodes
 */
class ReportCount extends AbstractShortcode
{
    /**
     * @var array
     */
    private $defaultAttributes = array(
        'einsatzart' => '',
        'units' => '',
        'year' => ''
    );

    /**
     * @param array|string $attributes Attributes of the shortcode
     *
     * @return string
     */
    public function render($attributes)
    {
        // See https://core.trac.wordpress.org/ticket/45929
        if ($attributes === '') {
            $attributes = array();
        }

        $attributes = shortcode_atts($this->defaultAttributes, $attributes);
        $year = $this->getYear($attributes['year']);

        $reportQuery = new ReportQuery();
        if (is_int($year)) {
            $reportQuery->setYear(intval($year));
        }

        if (array_key_exists('einsatzart', $attributes) && is_numeric($attributes['einsatzart'])) {
            $reportQuery->setIncidentTypeId(intval($attributes['einsatzart']));
        }

        $reportQuery->setUnits($this->getIntegerList($attributes, 'units'));

        $incidentReports = $reportQuery->getReports();
        return sprintf('%d', count($incidentReports));
    }

    /**
     * Converts the value of the shortcode's year attribute to a number that can be used in a query for posts.
     *
     * @param string $value Value of the year attribute
     *
     * @return int|string A numeric year or an empty string in case of an empty or erroneous attribute
     */
    private function getYear($value)
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
