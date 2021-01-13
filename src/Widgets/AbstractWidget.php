<?php
namespace abrain\Einsatzverwaltung\Widgets;

use WP_Taxonomy;
use WP_Widget;
use function get_terms;

/**
 * Class AbstractWidget
 * @package abrain\Einsatzverwaltung\Widgets
 */
abstract class AbstractWidget extends WP_Widget
{
    /**
     * @param WP_Taxonomy $taxonomyObject
     * @param string $fieldName
     * @param string $label
     * @param int[] $selectedIds
     * @param string $smallText
     */
    protected function echoChecklistBox(WP_Taxonomy $taxonomyObject, string $fieldName, string $label, array $selectedIds, string $smallText)
    {
        printf('<label>%s</label>', esc_html($label));
        $terms = get_terms(array(
            'taxonomy' => $taxonomyObject->name,
            'hide_empty' => false
        ));

        if (empty($terms)) {
            printf('<div class="checkboxlist">%s</div>', esc_html($taxonomyObject->labels->no_terms));
        } else {
            echo '<div class="checkboxlist"><ul>';
            foreach ($terms as $term) {
                $selected = in_array($term->term_id, $selectedIds);
                printf(
                    '<li><label><input type="checkbox" name="%s[]" value="%d"%s>%s</label></li>',
                    $this->get_field_name($fieldName),
                    esc_attr($term->term_id),
                    checked($selected, true, false),
                    esc_html($term->name)
                );
            }
            printf(
                '</ul><small>%s</small></div>',
                esc_html($smallText)
            );
        }
    }
}
