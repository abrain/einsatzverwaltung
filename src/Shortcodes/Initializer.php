<?php
namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\PermalinkController;
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
    public function __construct($data, $formatter, $permalinkController)
    {
        $reportListRenderer = new \abrain\Einsatzverwaltung\Frontend\ReportList($formatter);
        $reportList = new ReportList($reportListRenderer);
        add_shortcode('einsatzliste', array($reportList, 'render'));

        $reportArchives = new ReportArchives($data, $permalinkController);
        add_shortcode('einsatzjahre', array($reportArchives, 'render'));
    }
}
