<?php
namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\Util\Formatter;

/**
 * Registers shortcodes and sets up the classes rendering them
 */
class Initializer
{
    /**
     * Constructor
     *
     * @param Core $core
     * @param Data $data
     * @param Formatter $formatter
     */
    public function __construct($core, $data, $formatter)
    {
        $reportList = new ReportList($formatter);
        add_shortcode('einsatzliste', array($reportList, 'render'));

        $reportArchives = new ReportArchives($core, $data);
        add_shortcode('einsatzjahre', array($reportArchives, 'render'));
    }
}
