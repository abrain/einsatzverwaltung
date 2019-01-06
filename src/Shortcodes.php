<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Shortcodes\ReportArchives;
use abrain\Einsatzverwaltung\Shortcodes\ReportList;
use abrain\Einsatzverwaltung\Util\Formatter;

/**
 * Registers shortcodes and sets up the classes rendering them
 */
class Shortcodes
{
    /**
     * Constructor
     *
     * @param Core $core
     * @param Formatter $formatter
     */
    public function __construct($core, $formatter)
    {
        $reportList = new ReportList($formatter);
        add_shortcode('einsatzliste', array($reportList, 'render'));

        $reportArchives = new ReportArchives($core);
        add_shortcode('einsatzjahre', array($reportArchives, 'render'));
    }
}
