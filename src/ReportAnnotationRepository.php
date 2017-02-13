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
    private $annotations;

    /**
     * ReportAnnotationRepository constructor.
     */
    public function __construct()
    {
        $this->annotations = array();
    }

    /**
     * @param ReportAnnotation $annotation
     */
    public function addAnnotation($annotation)
    {
        $this->annotations[$annotation->getIdentifier()] = $annotation;
    }

    /**
     * @return mixed
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }

    /**
     * @return array
     */
    public function getAnnotationIdentifiers()
    {
        return array_keys($this->annotations);
    }
}