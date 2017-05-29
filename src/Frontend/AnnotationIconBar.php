<?php
namespace abrain\Einsatzverwaltung\Frontend;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Model\ReportAnnotation;
use abrain\Einsatzverwaltung\ReportAnnotationRepository;

/**
 * Darstellung von Vermerken als Icons
 *
 * @package abrain\Einsatzverwaltung\Frontend
 */
class AnnotationIconBar
{
    /**
     * @var ReportAnnotationRepository
     */
    private $annotationRepository;

    /**
     * AnnotationIconBar constructor.
     *
     * @param Core $core
     */
    public function __construct(Core $core)
    {
        $this->annotationRepository = $core->getAnnotationRepository();
    }

    /**
     * Generiert HTML-Code, der die Vermerke eines Einsatzberichts je nach Zustand des Vermerks als helle oder dunkle
     * Icons anzeigt
     *
     * @param IncidentReport $report Der Einsatzbericht, dessen Vermerke angezeigt werden sollen
     * @param array $annotationIds Liste von Bezeichnern von Vermerken, die in dieser Reihenfolge gerendert werden
     * sollen. Bei einer leeren Liste werden alle bekannten Vermerke ausgegeben.
     *
     * @return string Der generierte HTML-Code
     */
    public function render($report, $annotationIds = array())
    {
        $string = '';
        $annotations = array();

        // Wenn eine Auswahl von Vermerken vorgegeben ist, diese in dieser Reihenfolge holen
        if (!empty($annotationIds)) {
            foreach ($annotationIds as $annotationId) {
                $reportAnnotation = $this->annotationRepository->getAnnotationById($annotationId);
                if (false !== $reportAnnotation) {
                    $annotations[] = $reportAnnotation;
                }
            }
        }

        // Keine Vermerke vorgegeben oder alle angegebenen waren ungültig
        if (empty($annotations)) {
            $annotations = $this->annotationRepository->getAnnotations();
        }

        /** @var ReportAnnotation $annotation */
        foreach ($annotations as $annotation) {
            $icon = $annotation->getIcon();
            if (empty($icon)) {
                continue;
            }

            if (!empty($string)) {
                $string .= '&nbsp;';
            }

            $string .= $this->getAnnotationIcon(
                $icon,
                array($annotation->getLabelWhenInactive(), $annotation->getLabelWhenActive()),
                $annotation->getStateForReport($report)
            );
        }
        return $string;
        /*return $this->getAnnotationIcon(
            'camera',
            array('Einsatzbericht enthält keine Bilder', 'Einsatzbericht enthält Bilder'),
            $report->hasImages()
        );*/
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
