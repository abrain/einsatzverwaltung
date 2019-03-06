<?php
namespace abrain\Einsatzverwaltung\Export\Formats;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Util\Formatter;

/**
 * Abstraktion für Exportformate
 */
abstract class AbstractFormat implements Format
{
    /**
     * @var string
     */
    protected $startDate;

    /**
     * @var string
     */
    protected $endDate;

    /**
     * @inheritDoc
     */
    public function setFilters($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Gibt das WP_Query-Objekt für den Abruf der zu exportierenden Einsatzberichte
     * zurück.
     *
     * @return \WP_Query
     */
    protected function getQuery()
    {
        // Wähle nur veröffentlichte Einsatzberichte aus
        $args = array(
            'post_type' => 'einsatz',
            'post_status' => 'publish',
            'nopaging' => true
        );

        // Exportiere nur die Einsatzberichte, welche nicht vor $startDate und/oder
        // nach $endDate liegen
        if ($this->startDate || $this->endDate) {
            $args['date_query'] = array();

            if ($this->startDate) {
                $args['date_query']['after'] = date('Y-m-d', strtotime($this->startDate));
            }
            if ($this->endDate) {
                $args['date_query']['before'] = date('Y-m-d ', strtotime('+1 month', strtotime($this->endDate)));
            }
        }

        return new \WP_Query($args);
    }

    /**
     * Gibt die Namen der zu exportierenden Felder zurück
     *
     * @return array
     */
    protected function getColumnNames()
    {
        return array(
            'Einsatznummer',
            'Lfd.',
            'Alarmierungsarten',
            'Alarmzeit',
            'Einsatzende',
            'Dauer (Minuten)',
            'Einsatzort',
            'Einsatzart',
            'Fahrzeuge',
            'Externe Einsatzmittel',
            'Mannschaftsstärke',
            'Einsatzleiter',
            'Berichtstitel',
            'Berichtstext',
            'Besonderer Einsatz',
            'Fehlalarm'
        );
    }

    /**
     * @param \WP_Post $post
     * @return array
     */
    protected function getValuesForReport(\WP_Post $post)
    {
        $report = new IncidentReport($post);

        $duration = $report->getDuration();
        // $duration soll stets eine Zahl sein
        if (empty($duration)) {
            $duration = 0;
        }

        return array(
            $report->getNumber(),
            $report->getSequentialNumber(),
            $this->getArrayRepresentation(array_map(array($this, 'getName'), $report->getTypesOfAlerting())),
            $report->getTimeOfAlerting()->format('Y-m-d H:i'),
            $report->getTimeOfEnding(),
            $duration,
            $report->getLocation(),
            Formatter::getTypeOfIncident($report, false, false, false),
            $this->getArrayRepresentation(array_map(array($this, 'getName'), $report->getVehicles())),
            $this->getArrayRepresentation(array_map(array($this, 'getName'), $report->getAdditionalForces())),
            $report->getWorkforce(),
            $report->getIncidentCommander(),
            $post->post_title,
            $post->post_content,
            $this->getBooleanRepresentation($report->isSpecial()),
            $this->getBooleanRepresentation($report->isFalseAlarm()),
        );
    }

    /**
     * @param \WP_Term $object
     * @return string
     */
    private function getName($object)
    {
        return $object->name;
    }

    /**
     * @param array $array
     * @return mixed
     */
    abstract protected function getArrayRepresentation($array);

    /**
     * @param bool $bool
     * @return mixed
     */
    abstract protected function getBooleanRepresentation($bool);
}
