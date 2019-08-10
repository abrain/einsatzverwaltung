<?php

namespace abrain\Einsatzverwaltung\Widgets;

use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\Util\Formatter;

/**
 * Sets up and registers the widgets.
 *
 * @package abrain\Einsatzverwaltung\Widgets
 */
class Initializer
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Options
     */
    private $options;

    /**
     * Initializer constructor.
     *
     * @param Formatter $formatter
     * @param Options $options
     */
    public function __construct(Formatter $formatter, Options $options)
    {
        $this->formatter = $formatter;
        $this->options = $options;

        add_action('widgets_init', array($this, 'registerWidgets'));
    }

    public function registerWidgets()
    {
        register_widget(new RecentIncidents($this->options, $this->formatter));
        register_widget(new RecentIncidentsFormatted($this->formatter));
    }
}
