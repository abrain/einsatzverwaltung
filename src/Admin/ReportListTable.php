<?php

namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Frontend\AnnotationIconBar;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Types\Report;
use WP_Term;

/**
 * Bestimmt das Aussehen der Auflistung von Einsatzberichten im Adminbereich
 * @package abrain\Einsatzverwaltung\Admin
 */
class ReportListTable
{
    /**
     * Legt fest, welche Spalten bei der Übersicht der Einsatzberichte im
     * Adminbereich angezeigt werden
     *
     * @param array $columns
     *
     * @return array
     */
    public function filterColumnsEinsatz($columns)
    {
        unset($columns['author']);
        unset($columns['date']);
        unset($columns['categories']);
        $columns['title'] = 'Einsatzbericht';
        $columns['e_nummer'] = 'Nummer';
        $columns['einsatzverwaltung_annotations'] = 'Vermerke';
        $columns['e_alarmzeit'] = 'Alarmzeit';
        $columns['e_einsatzende'] = 'Einsatzende';
        $columns['e_art'] = 'Art';
        $columns['e_fzg'] = 'Fahrzeuge';

        return $columns;
    }

    /**
     * Liefert den Inhalt für die jeweiligen Spalten bei der Übersicht der
     * Einsatzberichte im Adminbereich
     *
     * @param string $column
     * @param int $postId
     */
    public function filterColumnContentEinsatz($column, $postId)
    {
        $report = new IncidentReport($postId);
        $content = $this->getColumnContent($column, $report);
        echo (empty($content) ? '-' : $content);
    }

    /**
     * @param $columnId
     * @param IncidentReport $report
     *
     * @return string
     */
    public function getColumnContent($columnId, IncidentReport $report)
    {
        switch ($columnId) {
            case 'e_nummer':
                return $report->getNumber();
            case 'e_einsatzende':
                $timeOfEnding = $report->getTimeOfEnding();
                if (!empty($timeOfEnding)) {
                    $timestamp = strtotime($timeOfEnding);
                    return date("d.m.Y", $timestamp)."<br>".date("H:i", $timestamp);
                }
                break;
            case 'e_alarmzeit':
                $timeOfAlerting = $report->getTimeOfAlerting();
                if (!empty($timeOfAlerting)) {
                    return $timeOfAlerting->format('d.m.Y') . '<br>' . $timeOfAlerting->format('H:i');
                }
                break;
            case 'e_art':
                $term = $report->getTypeOfIncident();
                return $this->getTermFilterLink($term);
            case 'e_fzg':
                $vehicles = $report->getVehicles();
                $vehicleLinks = array_map(array($this, 'getTermFilterLink'), $vehicles);
                return join(', ', $vehicleLinks);
            case 'einsatzverwaltung_annotations':
                return AnnotationIconBar::getInstance()->render($report);
        }

        return '';
    }

    /**
     * @param WP_Term|null $term
     * @return string An HTML anchor to filter this list table for occurrences of a certain term
     */
    private function getTermFilterLink(WP_Term $term = null)
    {
        if (empty($term)) {
            return '';
        }

        $url = add_query_arg(array('post_type' => Report::SLUG, $term->taxonomy => $term->slug), 'edit.php');
        $text = sanitize_term_field('name', $term->name, $term->term_id, $term->taxonomy, 'display');
        return sprintf('<a href="%s">%s</a>', esc_url($url), esc_html($text));
    }
}
