<?php

namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Frontend\ReportListParameters;
use abrain\Einsatzverwaltung\ReportQuery;
use abrain\Einsatzverwaltung\Util\Formatter;

/**
 * Renders the list of reports for the shortcode [einsatzliste]
 */
class ReportList
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * ReportList constructor.
     *
     * @param Formatter $formatter
     */
    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Gibt eine Tabelle mit Einsätzen aus dem gegebenen Jahr zurück
     *
     * @param array $atts Parameter des Shortcodes
     *
     * @return string
     */
    public function render($atts)
    {
        $currentYear = date('Y');

        // Shortcodeparameter auslesen
        $shortcodeParams = shortcode_atts(array(
            'jahr' => $currentYear,
            'sort' => 'ab',
            'monatetrennen' => 'nein',
            'link' => 'title',
            'limit' => -1,
            'options' => ''
        ), $atts);
        $limit = $shortcodeParams['limit'];

        // Optionen auswerten
        $rawOptions = array_map('trim', explode(',', $shortcodeParams['options']));
        $possibleOptions = array('special', 'noLinkWithoutContent', 'noHeading', 'compact');
        $filteredOptions = array_intersect($possibleOptions, $rawOptions);
        $showOnlySpecialReports = in_array('special', $filteredOptions);
        $columnsWithLink = explode(',', $shortcodeParams['link']);
        if (in_array('none', $columnsWithLink)) {
            $columnsWithLink = array();
        }

        // Berichte abfragen
        $reportQuery = new ReportQuery();
        if (is_numeric($limit) && $limit > 0) {
            $reportQuery->setLimit(intval($limit));
        }
        $reportQuery->setOnlySpecialReports($showOnlySpecialReports);
        $reportQuery->setOrderAsc($shortcodeParams['sort'] == 'auf');

        if (is_numeric($shortcodeParams['jahr'])) {
            $reportQuery->setYear($shortcodeParams['jahr']);
        }

        $reports = $reportQuery->getReports();

        $reportList = new \abrain\Einsatzverwaltung\Frontend\ReportList($this->formatter);
        $parameters = new ReportListParameters();
        $parameters->setSplitMonths($shortcodeParams['monatetrennen'] == 'ja');
        $parameters->setColumnsLinkingReport($columnsWithLink);
        $parameters->linkEmptyReports = (!in_array('noLinkWithoutContent', $filteredOptions));
        $parameters->showHeading = (!in_array('noHeading', $filteredOptions));
        $parameters->compact = in_array('compact', $filteredOptions);

        return $reportList->getList($reports, $parameters);
    }
}
