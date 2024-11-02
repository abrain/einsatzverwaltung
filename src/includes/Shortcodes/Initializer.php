<?php
namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\Frontend\ReportList\Parameters;
use abrain\Einsatzverwaltung\Frontend\ReportList\Renderer as ReportListRenderer;
use abrain\Einsatzverwaltung\PermalinkController;
use abrain\Einsatzverwaltung\ReportQuery;
use abrain\Einsatzverwaltung\Util\Formatter;

/**
 * Registers shortcodes and sets up the classes rendering them
 */
class Initializer
{
    /**
     * Constructor
     *
     * @param Data $data
     * @param Formatter $formatter
     * @param PermalinkController $permalinkController
     */
    public function __construct(Data $data, Formatter $formatter, PermalinkController $permalinkController)
    {
        $reportListRenderer = new ReportListRenderer($formatter);
        $reportList = new ReportList(new ReportQuery(), $reportListRenderer, new Parameters());
        add_shortcode('einsatzliste', array($reportList, 'render'));

        $reportArchives = new ReportArchives($data, $permalinkController);
        add_shortcode('einsatzjahre', array($reportArchives, 'render'));

        $reportStatistics = new ReportCount(new ReportQuery());
        add_shortcode('reportcount', array($reportStatistics, 'render'));
    }
}
