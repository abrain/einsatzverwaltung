<?php

namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Frontend\ReportList\Parameters as ReportListParameters;
use abrain\Einsatzverwaltung\Frontend\ReportList\Renderer as ReportListRenderer;
use abrain\Einsatzverwaltung\Frontend\ReportList\SplitType;
use abrain\Einsatzverwaltung\ReportQuery;
use function array_map;

/**
 * Renders the list of reports for the shortcode [einsatzliste]
 */
class ReportList extends AbstractShortcode
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
            'split' => 'no',
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
     * @param array|string $atts Parameter des Shortcodes
     *
     * @return string
     */
    public function render($atts)
    {
        $attributes = $this->getAttributes($atts);
        $filteredOptions = $this->extractOptions($attributes);

        $reportQuery = new ReportQuery();
        $this->configureReportQuery($reportQuery, $attributes, $filteredOptions);
        $reports = $reportQuery->getReports();

        $parameters = new ReportListParameters();
        $this->configureListParameters($parameters, $attributes, $filteredOptions);

        return $this->reportList->getList($reports, $parameters);
    }

    /**
     * @param array|string $attributes
     *
     * @return array
     */
    private function getAttributes($attributes)
    {
        // See https://core.trac.wordpress.org/ticket/45929
        if ($attributes === '') {
            $attributes = array();
        }

        // Ensure backwards compatibility
        if (array_key_exists('monatetrennen', $attributes) && !array_key_exists('split', $attributes) &&
            $attributes['monatetrennen'] == 'ja'
        ) {
            $attributes['split'] = 'monthly';
        }

        return shortcode_atts($this->defaultAttributes, $attributes);
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
        switch ($attributes['split']) {
            case 'monthly':
                $parameters->setSplitType(SplitType::MONTHLY);
                break;
            case 'quarterly':
                $parameters->setSplitType(SplitType::QUARTERLY);
                break;
            default:
                $parameters->setSplitType(SplitType::NONE);
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

        $units = $this->getIntegerList($attributes, 'units');
        if (!empty($units)) {
            $reportQuery->setUnits($this->translateOldUnitIds($units));
        }
    }
}
