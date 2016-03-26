<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Frontend\ReportList;

/**
 * Ersetzt die Shortcodes durch Inhalte
 */
class Shortcodes
{
    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * @var Core
     */
    private $core;

    /**
     * @var Options
     */
    private $options;

    /**
     * Constructor
     *
     * @param Utilities $utilities
     * @param Core $core
     * @param Options $options
     */
    public function __construct($utilities, $core, $options)
    {
        $this->addHooks();
        $this->utilities = $utilities;
        $this->core = $core;
        $this->options = $options;
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
        $possibleOptions = array('special', 'noLinkWithoutContent', 'noHeading');
        $filteredOptions = array_intersect($possibleOptions, $rawOptions);
        $showOnlySpecialReports = in_array('special', $filteredOptions);
        $linkEmptyReports = !in_array('noLinkWithoutContent', $filteredOptions);
        $showHeading = !in_array('noHeading', $filteredOptions);

        $columnsWithLink = explode(',', $shortcodeParams['link']);
        if (in_array('none', $columnsWithLink)) {
            $columnsWithLink = false;
        }
        if ($columnsWithLink !== false) {
            $columnsWithLink = $this->utilities->sanitizeColumnsArray($columnsWithLink);
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

        $reportList = new ReportList($this->utilities, $this->core, $this->options);
        return $reportList->getList(
            $reports,
            array(
                'splitMonths' => ($shortcodeParams['monatetrennen'] == 'ja'),
                'columns' => $this->options->getEinsatzlisteEnabledColumns(),
                'columnsWithLink' => $columnsWithLink,
                'linkEmptyReports' => $linkEmptyReports,
                'showHeading' => $showHeading,
            )
        );
    }

    /**
     * Gibt Links zu den Archivseiten der Jahre, in denen Einsatzberichte existieren, zurück
     *
     * @return string
     */
    public function einsatzjahre()
    {
        global $year, $wp_rewrite;
        $jahre = Data::getJahreMitEinsatz();

        $string = '';
        foreach ($jahre as $jahr) {
            if (!empty($string)) {
                $string .= ' | ';
            }

            $link = get_post_type_archive_link('einsatz');
            $link = ($wp_rewrite->using_permalinks() ? trailingslashit($link) : $link . '&year=') . $jahr;
            $string .= '<a href="' . user_trailingslashit($link) . '">';

            if ($year == $jahr || empty($year) && $jahr == date('Y')) {
                $string .= "<strong>$jahr</strong>";
            } else {
                $string .= $jahr;
            }

            $string .= '</a>';
        }

        return $string;
    }
}
