<?php
namespace abrain\Einsatzverwaltung\Widgets;

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
     * Initializer constructor.
     *
     * @param Formatter $formatter
     */
    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function registerWidgets()
    {
        register_widget(new RecentIncidents($this->formatter));
        register_widget(new RecentIncidentsFormatted($this->formatter));
    }
}
