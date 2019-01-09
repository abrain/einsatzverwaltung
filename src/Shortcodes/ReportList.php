<?php

namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Frontend\ReportListParameters;
use abrain\Einsatzverwaltung\ReportQuery;

/**
 * Renders the list of reports for the shortcode [einsatzliste]
 */
class ReportList
{
    /**
     * @var array
     */
    private $defaultAttributes;

    /**
     * @var \abrain\Einsatzverwaltung\Frontend\ReportList
     */
    private $reportList;

    /**
     * ReportList constructor.
     *
     * @param \abrain\Einsatzverwaltung\Frontend\ReportList $reportList
     */
    public function __construct(\abrain\Einsatzverwaltung\Frontend\ReportList $reportList)
    {
        $this->reportList = $reportList;

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
        $filteredOptions = $this->extractOptions($attributes);

        $reportQuery = new ReportQuery();
        $this->configureReportQuery($reportQuery, $attributes, $filteredOptions);
        $reports = $reportQuery->getReports();

        $parameters = new ReportListParameters();
        $this->configureListParameters($parameters, $attributes, $filteredOptions);

        return $this->reportList->getList($reports, $parameters);
    }

    /**
     * @param array $attributes
     *
     * @return array
     */
    public function extractOptions($attributes)
    {
        $rawOptions = array_map('trim', explode(',', $attributes['options']));
        $possibleOptions = array('special', 'noLinkWithoutContent', 'noHeading', 'compact');
        return array_intersect($possibleOptions, $rawOptions);
    }

    /**
     * @param ReportListParameters $parameters
     * @param array $attributes
     * @param array $filteredOptions
     */
    public function configureListParameters(ReportListParameters &$parameters, $attributes, $filteredOptions)
    {
        $parameters->setSplitMonths($attributes['monatetrennen'] == 'ja');

        $columnsWithLink = explode(',', $attributes['link']);
        if (in_array('none', $columnsWithLink)) {
            $columnsWithLink = array();
        }
        $parameters->setColumnsLinkingReport($columnsWithLink);

        $parameters->linkEmptyReports = (!in_array('noLinkWithoutContent', $filteredOptions));
        $parameters->showHeading = (!in_array('noHeading', $filteredOptions));
        $parameters->compact = in_array('compact', $filteredOptions);
    }

    /**
     * @param ReportQuery $reportQuery
     * @param array $attributes
     * @param array $filteredOptions
     */
    public function configureReportQuery(ReportQuery &$reportQuery, array $attributes, array $filteredOptions)
    {
        $limit = $attributes['limit'];
        if (is_numeric($limit) && $limit > 0) {
            $reportQuery->setLimit(intval($limit));
        }

        $reportQuery->setOnlySpecialReports(in_array('special', $filteredOptions));
        $reportQuery->setOrderAsc($attributes['sort'] == 'auf');

        if (is_numeric($attributes['jahr'])) {
            $reportQuery->setYear(intval($attributes['jahr']));
        }
    }
}
