<?php
namespace abrain\Einsatzverwaltung\Frontend;

use abrain\Einsatzverwaltung\Model\IncidentReport;

/**
 * Darstellung von Vermerken als Icons
 *
 * @package abrain\Einsatzverwaltung\Frontend
 */
class AnnotationIconBar
{
    /**
     * Generiert HTML-Code, der die Vermerke eines Einsatzberichts je nach Zustand des Vermerks als helle oder dunkle
     * Icons anzeigt
     *
     * @param IncidentReport $report Der Einsatzbericht, dessen Vermerke angezeigt werden sollen
     *
     * @return string Der generierte HTML-Code
     */
    public function render($report)
    {
        return $this->getAnnotationIcon(
            'camera',
            array('Einsatzbericht enthält keine Bilder', 'Einsatzbericht enthält Bilder'),
            $report->hasImages()
        );
    }

    /**
     * @param $icon
     * @param $titles
     * @param $state
     *
     * @return string
     */
    private function getAnnotationIcon($icon, $titles, $state)
    {
        $title = $titles[$state ? 1 : 0];
        $style = $state ? '' : 'color: #bbb;';
        return '<i class="fa fa-' . $icon . '" aria-hidden="true" title="' . $title . '" style="' . $style . '"></i>';
    }
}
