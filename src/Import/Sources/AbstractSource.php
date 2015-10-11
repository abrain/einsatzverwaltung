<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

/**
 * Abstraktion für Importquellen
 */
abstract class AbstractSource
{
    /**
     * Gibt die Beschreibung der Importquelle zurück
     *
     * @return string Beschreibung der Importquelle
     */
    abstract public function getDescription();

    /**
     * @param $action
     * @return string
     */
    public function getActionAttribute($action)
    {
        return $this->getIdentifier() . ':' . $action;
    }

    /**
     * Gibt den eindeutigen Bezeichner der Importquelle zurück
     *
     * @return string Eindeutiger Bezeichner der Importquelle
     */
    abstract public function getIdentifier();

    /**
     * Gibt den Wert für das name-Attribut eines Formularelements zurück
     *
     * @param string $field Bezeichner des Felds
     * @return string Eindeutiger Name bestehend aus Bezeichnern der Importquelle und des Felds
     */
    public function getInputName($field)
    {
        return $this->getIdentifier() . '-' . $field;
    }

    /**
     * Gibt den Namen der Importquelle zurück
     *
     * @return string Name der Importquelle
     */
    abstract public function getName();

    /**
     * @param $action
     * @return mixed
     */
    abstract public function renderPage($action);
}
