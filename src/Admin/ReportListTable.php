<?php

namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Frontend\AnnotationIconBar;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Types\Report;
use abrain\Einsatzverwaltung\Types\Unit;
use WP_Post;
use WP_Post_Type;
use WP_Term;

/**
 * Bestimmt das Aussehen der Auflistung von Einsatzberichten im Adminbereich
 * @package abrain\Einsatzverwaltung\Admin
 */
class ReportListTable
{
    /**
     * Additional columns for the report listing.
     *
     * @var array
     */
    private $customColumns = array();

    /**
     * ReportListTable constructor.
     */
    public function __construct()
    {
        $this->customColumns = array(
            'title' => array(
                'label' => 'Einsatzbericht',
                'quickedit' => false
            ),
            'e_nummer' => array(
                'label' => 'Nummer',
                'quickedit' => false
            ),
            'einsatzverwaltung_annotations' => array(
                'label' => 'Vermerke',
                'quickedit' => false
            ),
            'e_alarmzeit' => array(
                'label' => 'Alarmzeit',
                'quickedit' => false
            ),
            'e_einsatzende' => array(
                'label' => 'Einsatzende',
                'quickedit' => false
            ),
            'e_art' => array(
                'label' => 'Art',
                'quickedit' => false
            ),
            'einsatzverwaltung_units' => array(
                'label' => __('Units', 'einsatzverwaltung'),
                'quickedit' => true
            ),
            'e_fzg' => array(
                'label' => 'Fahrzeuge',
                'quickedit' => false
            )
        );
    }

    /**
     * Echo the values of custom columns for a post, to be used for Quick Edit mode.
     *
     * @param WP_Post $post
     * @param WP_Post_Type $post_type_object
     */
    public function addInlineData(WP_Post $post, WP_Post_Type $post_type_object)
    {
        if ($post_type_object->name !== Report::SLUG) {
            return;
        }

        $report = new IncidentReport($post);
        $unitIds = array_map(function (WP_Post $unit) {
            return $unit->ID;
        }, $report->getUnits());
        $unitIds = join(',', $unitIds);
        printf(
            '<div id="%s" class="post_category">%s</div>',
            esc_attr(Unit::POST_TYPE . '_' . $post->ID),
            esc_html($unitIds)
        );
    }

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
        $columnLabels = array_map(function ($column) {
            return $column['label'];
        }, $this->customColumns);
        $columns = array_merge($columns, $columnLabels);

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
            case 'einsatzverwaltung_units':
                $unitNames = array_map('get_the_title', $report->getUnits());
                return join(', ', $unitNames);
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

    /**
     * Gets called for each custom column to output a custom edit box for Quick Edit mode.
     *
     * @param string $columnName Name of the column to edit.
     * @param string $postType The post type slug, or current screen name if this is a taxonomy list table.
     * @param string $taxonomy The taxonomy name, if any.
     */
    public function quickEditCustomBox($columnName, $postType, $taxonomy)
    {
        if (!empty($taxonomy) || $postType !== Report::SLUG) {
            return;
        }

        if ($this->columnHasCustomBox($columnName)) {
            echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col">';
            $this->echoEditCustomBox($columnName);
            echo '</div></fieldset>';
        }
    }

    /**
     * Gets called for each custom column to output a custom edit box for Bulk Edit mode.
     *
     * @param string $columnName Name of the column to edit.
     * @param string $postType
     */
    public function bulkEditCustomBox($columnName, $postType)
    {
        if ($postType !== Report::SLUG) {
            return;
        }

        if ($this->columnHasCustomBox($columnName)) {
            echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col">';
            $this->echoEditCustomBox($columnName);
            echo '</div></fieldset>';
        }
    }

    /**
     * Echo form elements for custom columns used in Quick Edit and Bulk Edit mode.
     *
     * @param string $columnName Identifier of the custom column
     */
    private function echoEditCustomBox($columnName)
    {
        printf(
            '<span class="title inline-edit-categories-label">%s</span>',
            esc_html($this->getColumnLabel($columnName))
        );
        if ($columnName === 'einsatzverwaltung_units') {
            $units = get_posts(array(
                'post_type' => Unit::POST_TYPE,
                'numberposts' => -1,
                'order' => 'ASC',
                'orderby' => 'name'
            ));
            if (empty($units)) {
                $postTypeObject = get_post_type_object(Unit::POST_TYPE);
                printf("<div>%s</div>", esc_html($postTypeObject->labels->not_found));
                return;
            }

            echo '<ul class="cat-checklist evw_unit-checklist">';
            foreach ($units as $unit) {
                $identifier = Unit::POST_TYPE . '-' . $unit->ID;
                printf(
                    '<li id="%1$s"><label><input type="checkbox" name="evw_units[]" value="%2$d">%3$s</label></li>',
                    esc_attr($identifier),
                    esc_attr($unit->ID),
                    esc_html($unit->post_title)
                );
            }
            echo '</ul>';
        }
    }

    /**
     * Checks wether a custom column should have a custom edit box in Quick Edit / Bulk Edit mode.
     *
     * @param string $columnName
     *
     * @return bool
     */
    private function columnHasCustomBox($columnName)
    {
        return array_key_exists($columnName, $this->customColumns) && $this->customColumns[$columnName]['quickedit'];
    }

    /**
     * Returns the label of a custom column.
     *
     * @param string $columnName
     *
     * @return string
     */
    private function getColumnLabel($columnName)
    {
        if (!array_key_exists($columnName, $this->customColumns)) {
            return '';
        }

        return $this->customColumns[$columnName]['label'];
    }
}
