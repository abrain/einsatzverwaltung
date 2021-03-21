<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Model\ReportAnnotation;

/**
 * Hält alle verfügbaren Typen von Vermerken für Einsatzberichte vor.
 *
 * @package abrain\Einsatzverwaltung
 */
class ReportAnnotationRepository
{
    /**
     * Hält die einzige Instanz dieser Klasse (Singleton)
     *
     * @var ReportAnnotationRepository
     */
    private static $instance;

    /**
     * @var ReportAnnotation[]
     */
    private $annotations;

    /**
     * ReportAnnotationRepository constructor.
     */
    private function __construct()
    {
        $this->annotations = array();
    }

    /**
     * Gibt die global einzigartige Instanz dieser Klasse zurück
     *
     * @return ReportAnnotationRepository
     */
    public static function getInstance(): ReportAnnotationRepository
    {
        if (null === self::$instance) {
            self::$instance = new ReportAnnotationRepository();
        }

        return self::$instance;
    }

    /**
     * @param ReportAnnotation $annotation
     */
    public function addAnnotation(ReportAnnotation $annotation)
    {
        $this->annotations[$annotation->getIdentifier()] = $annotation;
    }

    /**
     * @return ReportAnnotation[]
     */
    public function getAnnotations(): array
    {
        return $this->annotations;
    }

    /**
     * @return string[]
     */
    public function getAnnotationIdentifiers(): array
    {
        return array_keys($this->annotations);
    }

    /**
     * @param $identifier
     * @return bool|ReportAnnotation
     */
    public function getAnnotationById($identifier)
    {
        if (!key_exists($identifier, $this->annotations)) {
            return false;
        }

        return $this->annotations[$identifier];
    }
}
