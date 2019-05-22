<?php

namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\ReportQuery;

/**
 * Shows a number of incident reports for the shortcode [reportcount]
 * @package abrain\Einsatzverwaltung\Shortcodes
 */
class ReportCount
{
    /**
     * @param array|string $atts Attributes of the shortcode
     *
     * @return string
     */
    public function render($atts)
    {
        $reportQuery = new ReportQuery();
        $incidentReports = $reportQuery->getReports();
        return sprintf('%d', count($incidentReports));
    }
}
