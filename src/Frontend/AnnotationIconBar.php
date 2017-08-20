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
    const DEFAULT_COLOR_OFF = '#bbb';

    /**
     * @var Core
     */
    private $core;

    /**
     * AnnotationIconBar constructor.
     *
     * @param Core $core
     */
    public function __construct(Core $core)
    {
        $this->core = $core;
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
        $annotationRepository = $this->core->getAnnotationRepository();
        $string = '';
        $annotations = array();

        // Wenn eine Auswahl von Vermerken vorgegeben ist, diese in dieser Reihenfolge holen
        if (!empty($annotationIds)) {
            foreach ($annotationIds as $annotationId) {
                $reportAnnotation = $annotationRepository->getAnnotationById($annotationId);
                if (false !== $reportAnnotation) {
                    $annotations[] = $reportAnnotation;
                }
            }
        }

        // Keine Vermerke vorgegeben oder alle angegebenen waren ungültig
        if (empty($annotations)) {
            $annotations = $annotationRepository->getAnnotations();
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
        if (is_admin()) {
            $colorOff = self::DEFAULT_COLOR_OFF;
        } else {
            $colorOff = get_option('einsatzvw_list_annotations_color_off', self::DEFAULT_COLOR_OFF);
        }

        return sprintf(
            '<i class="%s" aria-hidden="true" title="%s" style="%s"></i>',
            esc_attr('fa fa-' . $icon),
            esc_attr($titles[$state ? 1 : 0]),
            esc_attr($state ? '' : 'color: ' . $colorOff . ';')
        );
    }
}
