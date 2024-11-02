<?php
namespace abrain\Einsatzverwaltung\Frontend;

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
     * Hält die einzige Instanz dieser Klasse (Singleton)
     *
     * @var AnnotationIconBar
     */
    private static $instance;

    /**
     * @var string
     */
    private $iconColorOff;

    /**
     * AnnotationIconBar constructor.
     */
    private function __construct()
    {
        $this->iconColorOff = $this->getAnnotationColorOff();
    }

    /**
     * Gibt die global einzigartige Instanz dieser Klasse zurück
     *
     * @return AnnotationIconBar
     */
    public static function getInstance(): AnnotationIconBar
    {
        if (null === self::$instance) {
            self::$instance = new AnnotationIconBar();
        }
        return self::$instance;
    }

    /**
     * Generiert HTML-Code, der die Vermerke eines Einsatzberichts je nach Zustand des Vermerks als helle oder dunkle
     * Icons anzeigt
     *
     * @param int $postId
     * @param array $annotationIds Liste von Bezeichnern von Vermerken, die in dieser Reihenfolge gerendert werden
     * sollen. Bei einer leeren Liste werden alle bekannten Vermerke ausgegeben.
     *
     * @return string Der generierte HTML-Code
     */
    public function render(int $postId, $annotationIds = array()): string
    {
        $annotationRepository = ReportAnnotationRepository::getInstance();
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
                $annotation->getStateForReport($postId)
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
    private function getAnnotationIcon($icon, $titles, $state): string
    {
        return sprintf(
            '<i class="%s" aria-hidden="true" title="%s" style="%s"></i>',
            esc_attr('fa-solid fa-' . $icon),
            esc_attr($titles[$state ? 1 : 0]),
            esc_attr($state ? '' : "color: {$this->iconColorOff};")
        );
    }

    /**
     * @return string
     */
    private function getAnnotationColorOff(): string
    {
        if (is_admin()) {
            return self::DEFAULT_COLOR_OFF;
        }

        $color = get_option('einsatzvw_list_annotations_color_off', self::DEFAULT_COLOR_OFF);
        $sanitizedColor = sanitize_hex_color($color);
        if (empty($sanitizedColor)) {
            $sanitizedColor = self::DEFAULT_COLOR_OFF;
        }

        return $sanitizedColor;
    }
}
