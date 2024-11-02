<?php

namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Frontend\AnnotationIconBar;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ReportNumberController;
use abrain\Einsatzverwaltung\Types\Report;
use WP_Post;
use WP_Post_Type;
use WP_Term;
use function array_map;
use function esc_html;
use function join;
use function printf;
use function sprintf;

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
    private $customColumns;

    /**
     * ReportListTable constructor.
     */
    public function __construct()
    {
        $this->customColumns = array(
            'title' => array(
                'label' => __('Title', 'einsatzverwaltung'),
                'bulkEdit' => false,
                'quickEdit' => false
            ),
            'e_nummer' => array(
                'label' => __('Incident number', 'einsatzverwaltung'),
                'bulkEdit' => false,
                'quickEdit' => true
            ),
            'einsatzverwaltung_annotations' => array(
                'label' => __('Annotations', 'einsatzverwaltung'),
                'bulkEdit' => false,
                'quickEdit' => false
            ),
            'e_alarmzeit' => array(
                'label' => __('Alarm time', 'einsatzverwaltung'),
                'bulkEdit' => false,
                'quickEdit' => false
            ),
            'e_einsatzende' => array(
                'label' => __('End time', 'einsatzverwaltung'),
                'bulkEdit' => false,
                'quickEdit' => false
            ),
            'e_art' => array(
                'label' => __('Incident Category', 'einsatzverwaltung'),
                'bulkEdit' => false,
                'quickEdit' => false
            ),
            'einsatzverwaltung_units' => array(
                'label' => __('Units', 'einsatzverwaltung'),
                'bulkEdit' => false,
                'quickEdit' => false
            ),
            'e_fzg' => array(
                'label' => __('Vehicles', 'einsatzverwaltung'),
                'bulkEdit' => false,
                'quickEdit' => false
            )
        );
    }

    public function addHooks()
    {
        add_filter('manage_edit-einsatz_columns', array($this, 'filterColumnsEinsatz'));
        add_action('manage_einsatz_posts_custom_column', array($this, 'filterColumnContentEinsatz'), 10, 2);
        add_action('quick_edit_custom_box', array($this, 'quickEditCustomBox'), 10, 3);
        add_action('bulk_edit_custom_box', array($this, 'bulkEditCustomBox'), 10, 2);
        add_action('add_inline_data', array($this, 'addInlineData'), 10, 2);
    }

    /**
     * Echo the values of custom columns for a post, to be used for Quick Edit mode.
     *
     * @param WP_Post $post
     * @param WP_Post_Type $postTypeObject
     */
    public function addInlineData(WP_Post $post, WP_Post_Type $postTypeObject)
    {
        if ($postTypeObject->name !== Report::getSlug()) {
            return;
        }

        $meta = get_post_meta($post->ID, 'einsatz_incidentNumber', true);
        printf('<div id="report_number_%1$d" class="meta_input">%2$s</div>', $post->ID, empty($meta) ? '' : esc_html($meta));
    }

    /**
     * Legt fest, welche Spalten bei der Übersicht der Einsatzberichte im
     * Adminbereich angezeigt werden
     *
     * @param array $columns
     *
     * @return array
     */
    public function filterColumnsEinsatz($columns): array
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
        echo empty($content) ? '-' : $content;
    }

    /**
     * @param $columnId
     * @param IncidentReport $report
     *
     * @return string
     */
    public function getColumnContent($columnId, IncidentReport $report): string
    {
        switch ($columnId) {
            case 'e_nummer':
                $numberString = $report->getNumber();
                $weight = $report->getWeight();
                if ($weight > 1) {
                    // translators: 1: number of incidents represented by this report (at least 2)
                    $numberString .= '<br>' . esc_html(sprintf(__('(%d incidents)', 'einsatzverwaltung'), $weight));
                }
                return $numberString;
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
                return AnnotationIconBar::getInstance()->render($report->getPostId());
            case 'einsatzverwaltung_units':
                $units = $report->getUnits();
                $unitLinks = array_map(array($this, 'getTermFilterLink'), $units);
                return join(', ', $unitLinks);
        }

        return '';
    }

    /**
     * @param WP_Term|null $term
     * @return string An HTML anchor to filter this list table for occurrences of a certain term
     */
    private function getTermFilterLink(WP_Term $term = null): string
    {
        if (empty($term)) {
            return '';
        }

        $url = add_query_arg(array('post_type' => Report::getSlug(), $term->taxonomy => $term->slug), 'edit.php');
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
        if (!empty($taxonomy) || $postType !== Report::getSlug()) {
            return;
        }

        if ($this->columnHasCustomBox($columnName, 'quickEdit')) {
            echo '<fieldset class="inline-edit-col-right inline-edit-' . $postType.'">';
            echo '<div class="inline-edit-col column-' . $columnName.'">';
            echo '<label class="inline-edit-group">';
            $this->echoEditCustomBox($columnName);
            echo '</label></div></fieldset>';
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
        if ($postType !== Report::getSlug()) {
            return;
        }

        if ($this->columnHasCustomBox($columnName, 'bulkEdit')) {
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
    private function echoEditCustomBox(string $columnName)
    {
        printf(
            '<span class="title">%s</span>',
            esc_html($this->getColumnLabel($columnName))
        );
        if ($columnName === 'e_nummer') {
            echo '<input type="text" name="einsatz_number">';
        }
    }

    /**
     * Checks whether a custom column should have a custom edit box in Quick Edit / Bulk Edit mode.
     *
     * @param string $columnName Identifier of the column.
     * @param string $context Either 'quickEdit' or 'bulkEdit'
     * @return bool
     */
    private function columnHasCustomBox(string $columnName, string $context): bool
    {
        $enabled = array_key_exists($columnName, $this->customColumns) && $this->customColumns[$columnName][$context] === true;

        if ($columnName === 'e_nummer' && ReportNumberController::isAutoIncidentNumbers()) {
            return false;
        }

        return $enabled;
    }

    /**
     * Returns the label of a custom column.
     *
     * @param string $columnName
     *
     * @return string
     */
    private function getColumnLabel($columnName): string
    {
        if (!array_key_exists($columnName, $this->customColumns)) {
            return '';
        }

        return $this->customColumns[$columnName]['label'];
    }
}
