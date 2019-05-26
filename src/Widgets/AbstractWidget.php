<?php
namespace abrain\Einsatzverwaltung\Widgets;

use WP_Post_Type;
use WP_Widget;

/**
 * Class AbstractWidget
 * @package abrain\Einsatzverwaltung\Widgets
 */
abstract class AbstractWidget extends WP_Widget
{
    /**
     * @param WP_Post_Type $postTypeObject
     * @param string $fieldName
     * @param string $label
     * @param int[] $selectedPostIds
     * @param string $smallText
     */
    protected function echoChecklistBox(WP_Post_Type $postTypeObject, $fieldName, $label, $selectedPostIds, $smallText)
    {
        printf('<label>%s</label>', esc_html($label));
        $posts = get_posts(array(
            'post_type' => $postTypeObject->name,
            'numberposts' => -1,
            'order' => 'ASC',
            'orderby' => 'name'
        ));
        if (empty($posts)) {
            printf('<div class="checkboxlist">%s</div>', esc_html($postTypeObject->labels->not_found));
        } else {
            echo '<div class="checkboxlist"><ul>';
            foreach ($posts as $post) {
                $selected = in_array($post->ID, $selectedPostIds);
                printf(
                    '<li><label><input type="checkbox" name="%s[]" value="%d"%s>%s</label></li>',
                    $this->get_field_name($fieldName),
                    esc_attr($post->ID),
                    checked($selected, true, false),
                    esc_html($post->post_title)
                );
            }
            printf(
                '</ul><small>%s</small></div>',
                esc_html($smallText)
            );
        }
    }
}
