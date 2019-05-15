<?php

namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Frontend\ReportList\Parameters as ReportListParameters;
use abrain\Einsatzverwaltung\Frontend\ReportList\Renderer as ReportListRenderer;
use abrain\Einsatzverwaltung\Frontend\ReportList\SplitType;
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
     * @var ReportListRenderer
     */
    private $reportList;

    /**
     * ReportList constructor.
     *
     * @param ReportListRenderer $reportList
     */
    public function __construct(ReportListRenderer $reportList)
    {
        $this->reportList = $reportList;

        // Shortcodeparameter auslesen
        $this->defaultAttributes = array(
            'jahr' => date('Y'),
            'sort' => 'ab',
            'monatetrennen' => 'nein',
            'link' => 'title',
            'limit' => -1,
            'einsatzart' => 0,
            'units' => '',
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
        if ($attributes['monatetrennen'] == 'ja') {
            $parameters->setSplitType(SplitType::MONTHLY);
        }

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

        if (array_key_exists('einsatzart', $attributes) && is_numeric($attributes['einsatzart'])) {
            $reportQuery->setIncidentTypeId(intval($attributes['einsatzart']));
        }

        if (array_key_exists('units', $attributes)) {
            $unitIds = explode(',', $attributes['units']);
            $unitIds = array_map('trim', $unitIds);
            $unitIds = array_filter($unitIds, 'is_numeric');
            $reportQuery->setUnits(array_map('intval', $unitIds));
        }
    }
}
