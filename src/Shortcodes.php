<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Frontend\ReportList;
use abrain\Einsatzverwaltung\Frontend\ReportListParameters;
use abrain\Einsatzverwaltung\Util\Formatter;

/**
 * Ersetzt die Shortcodes durch Inhalte
 */
class Shortcodes
{

    /**
     * @var Core
     */
    private $core;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * Constructor
     *
     * @param Core $core
     * @param Formatter $formatter
     */
    public function __construct($core, $formatter)
    {
        $this->addHooks();
        $this->core = $core;
        $this->formatter = $formatter;
    }

    private function addHooks()
    {
        add_shortcode('einsatzliste', array($this, 'einsatzliste'));
        add_shortcode('einsatzjahre', array($this, 'einsatzjahre'));
    }

    /**
     * Gibt eine Tabelle mit Einsätzen aus dem gegebenen Jahr zurück
     *
     * @param array $atts Parameter des Shortcodes
     *
     * @return string
     */
    public function einsatzliste($atts)
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

        $reportList = new ReportList($this->formatter);
        $parameters = new ReportListParameters();
        $parameters->setSplitMonths($shortcodeParams['monatetrennen'] == 'ja');
        $parameters->setColumnsLinkingReport($columnsWithLink);
        $parameters->linkEmptyReports = (!in_array('noLinkWithoutContent', $filteredOptions));
        $parameters->showHeading = (!in_array('noHeading', $filteredOptions));
        $parameters->compact = in_array('compact', $filteredOptions);

        return $reportList->getList($reports, $parameters);
    }

    /**
     * Gibt Links zu den Archivseiten der Jahre, in denen Einsatzberichte existieren, zurück
     *
     * @return string
     */
    public function einsatzjahre()
    {
        global $year;
        $thisYear = intval(date('Y'));
        $yearsWithReports = Data::getYearsWithReports();



        $links = array();
        foreach ($yearsWithReports as $currentYear) {
            $format = $year === $currentYear || empty($year) && $currentYear === $thisYear ? '<strong>%d</strong>' : '%d';
            $text = sprintf($format, $currentYear);

            $links[] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($this->core->getYearArchiveLink($currentYear)),
                $text
            );
        }

        return join(' | ', $links);
    }
}
