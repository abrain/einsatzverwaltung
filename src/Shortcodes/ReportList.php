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
     * @var array
     */
    private $defaultAttributes;

    /**
     * ReportList constructor.
     *
     * @param Formatter $formatter
     */
    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;

        // Shortcodeparameter auslesen
        $this->defaultAttributes = array(
            'jahr' => date('Y'),
            'sort' => 'ab',
            'monatetrennen' => 'nein',
            'link' => 'title',
            'limit' => -1,
            'options' => ''
        );
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
        $attributes = shortcode_atts($this->defaultAttributes, $atts);
        $limit = $attributes['limit'];

        // Optionen auswerten
        $rawOptions = array_map('trim', explode(',', $attributes['options']));
        $possibleOptions = array('special', 'noLinkWithoutContent', 'noHeading', 'compact');
        $filteredOptions = array_intersect($possibleOptions, $rawOptions);
        $onlySpecialReports = in_array('special', $filteredOptions);
        $columnsWithLink = explode(',', $attributes['link']);
        if (in_array('none', $columnsWithLink)) {
            $columnsWithLink = array();
        }

        // Berichte abfragen
        $reportQuery = new ReportQuery();
        if (is_numeric($limit) && $limit > 0) {
            $reportQuery->setLimit(intval($limit));
        }
        $reportQuery->setOnlySpecialReports($onlySpecialReports);
        $reportQuery->setOrderAsc($attributes['sort'] == 'auf');

        if (is_numeric($attributes['jahr'])) {
            $reportQuery->setYear($attributes['jahr']);
        }

        $reports = $reportQuery->getReports();

        $reportList = new \abrain\Einsatzverwaltung\Frontend\ReportList($this->formatter);
        $parameters = new ReportListParameters();
        $parameters->setSplitMonths($attributes['monatetrennen'] == 'ja');
        $parameters->setColumnsLinkingReport($columnsWithLink);
        $parameters->linkEmptyReports = (!in_array('noLinkWithoutContent', $filteredOptions));
        $parameters->showHeading = (!in_array('noHeading', $filteredOptions));
        $parameters->compact = in_array('compact', $filteredOptions);

        return $reportList->getList($reports, $parameters);
    }
}
