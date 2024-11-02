<?php
namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Frontend\ReportList\Parameters;
use abrain\Einsatzverwaltung\Frontend\ReportList\Renderer;
use abrain\Einsatzverwaltung\Frontend\ReportList\SplitType;
use abrain\Einsatzverwaltung\ReportQuery;
use function array_key_exists;
use function array_map;
use function date;
use function is_numeric;

/**
 * Renders the list of reports for the shortcode [einsatzliste]
 */
class ReportList extends AbstractShortcode
{
    /**
     * @var Renderer
     */
    private $reportList;

    /**
     * @var ReportQuery
     */
    private $reportQuery;

    /**
     * @var Parameters
     */
    private $parameters;

    /**
     * ReportList constructor.
     *
     * @param ReportQuery $reportQuery
     * @param Renderer $reportList
     * @param Parameters $parameters
     */
    public function __construct(ReportQuery $reportQuery, Renderer $reportList, Parameters $parameters)
    {
        parent::__construct([
            'jahr' => date('Y'),
            'sort' => 'ab',
            'split' => 'no',
            'link' => 'title',
            'limit' => -1,
            'icategories' => '',
            'units' => '',
            'options' => ''
        ]);

        $this->reportQuery = $reportQuery;
        $this->reportList = $reportList;
        $this->parameters = $parameters;
    }

    /**
     * @inheritDoc
     */
    public function render($attributes): string
    {
        $attributes = $this->getAttributes($attributes);
        $options = $this->getStringList(
            $attributes['options'],
            ['special', 'noLinkWithoutContent', 'noHeading', 'compact']
        );

        $this->reportQuery->resetQueryVars();
        $this->configureReportQuery($attributes, $options);
        $reports = $this->reportQuery->getReports();

        $this->configureListParameters($attributes, $options);

        return $this->reportList->getList($reports, $this->parameters);
    }

    /**
     * @inheritDoc
     */
    protected function fixOutdatedAttributes(array $attributes): array
    {
        // 'monatetrennen' has been changed to 'split'
        if (array_key_exists('monatetrennen', $attributes) && !array_key_exists('split', $attributes) &&
            $attributes['monatetrennen'] == 'ja'
        ) {
            $attributes['split'] = 'monthly';
        }

        // 'einsatzart' has been renamed to 'icategories'
        if (array_key_exists('einsatzart', $attributes) && !array_key_exists('icategories', $attributes) &&
            is_numeric($attributes['einsatzart'])) {
            $attributes['icategories'] = $attributes['einsatzart'];
        }

        return $attributes;
    }


    /**
     * @param array $attributes
     * @param array $filteredOptions
     */
    private function configureListParameters(array $attributes, array $filteredOptions)
    {
        switch ($attributes['split']) {
            case 'monthly':
                $this->parameters->setSplitType(SplitType::MONTHLY);
                break;
            case 'quarterly':
                $this->parameters->setSplitType(SplitType::QUARTERLY);
                break;
            default:
                $this->parameters->setSplitType(SplitType::NONE);
        }

        $columnsWithLink = array_map('trim', explode(',', $attributes['link']));
        if (in_array('none', $columnsWithLink)) {
            $columnsWithLink = array();
        }
        $this->parameters->setColumnsLinkingReport($columnsWithLink);

        $this->parameters->linkEmptyReports = (!in_array('noLinkWithoutContent', $filteredOptions));
        $this->parameters->showHeading = (!in_array('noHeading', $filteredOptions));
        $this->parameters->compact = in_array('compact', $filteredOptions);
    }

    /**
     * @param array $attributes
     * @param array $filteredOptions
     */
    private function configureReportQuery(array $attributes, array $filteredOptions)
    {
        $limit = $attributes['limit'];
        if (is_numeric($limit) && $limit > 0) {
            $this->reportQuery->setLimit(intval($limit));
        }

        $this->reportQuery->setOnlySpecialReports(in_array('special', $filteredOptions));
        $this->reportQuery->setOrderAsc($attributes['sort'] == 'auf');

        if (is_numeric($attributes['jahr'])) {
            $this->reportQuery->setYear(intval($attributes['jahr']));
        }

        $incidentCategoryIds = $this->getIntegerList($attributes['icategories']);
        if (!empty($incidentCategoryIds)) {
            $this->reportQuery->setIncidentTypeIds($incidentCategoryIds);
        }

        $units = $this->getIntegerList($attributes['units']);
        if (!empty($units)) {
            $this->reportQuery->setUnits($this->translateOldUnitIds($units));
        }
    }
}
