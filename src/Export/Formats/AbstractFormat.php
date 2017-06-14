<?php
namespace abrain\Einsatzverwaltung\Export\Formats;

require_once dirname(__FILE__) . '/Format.php';

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
            $args['date_query'] = array('inclusive' => true);

            if ($this->startDate) {
                $args['date_query']['after'] = date('Y-m-d', strtotime($this->startDate));
            }
            if ($this->endDate) {
                $args['date_query']['before'] = date('Y-m-d ', strtotime('+1 month', strtotime($this->endDate)));
            }
        }

        return new \WP_Query($args);
    }
}
