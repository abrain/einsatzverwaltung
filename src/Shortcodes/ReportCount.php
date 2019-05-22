<?php

namespace abrain\Einsatzverwaltung\Shortcodes;

/**
 * Shows a number of incident reports for the shortcode [reportcount]
 * @package abrain\Einsatzverwaltung\Shortcodes
 */
class ReportCount
{
    /**
     * @param array|string $atts Attributes of the shortcode
     *
     * @return string
     */
    public function render($atts)
    {
        return '42';
    }
}
