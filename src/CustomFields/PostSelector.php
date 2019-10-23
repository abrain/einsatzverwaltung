<?php
namespace abrain\Einsatzverwaltung\CustomFields;

use WP_Post;
use WP_Query;

/**
 * Represents an additional dropdown of a taxonomy for selecting posts
 * @package abrain\Einsatzverwaltung\CustomFields
 */
class PostSelector extends CustomField
{
    /**
     * @var array
     */
    private $excludedTypes;

    /**
     * @inheritDoc
     */
    public function __construct($key, $label, $description, $excludedTypes = array())
    {
        parent::__construct($key, $label, $description, '');
        $this->excludedTypes = $excludedTypes;
    }

    /**
     * @return array
     */
    private function getDropdownPostTypes()
    {
        $postTypes = get_post_types(array('public' => true));
        return array_diff($postTypes, $this->excludedTypes);
    }

    /**
     * Generiert ein Dropdown ähnlich zu wp_dropdown_pages, allerdings mit frei wählbaren Beitragstypen
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     @type bool|int $echo      Ob der generierte Code aus- oder zurückgegeben werden soll. Standard true (ausgeben)
     *     @type array    $post_type Array mit Beitragstypen, die auswählbar sein sollen
     *     @type int      $selected  Post-ID, die vorausgewählt sein soll
     *     @type string   $name      Wert für name-Attribut des Auswahlfelds
     *     @type string   $id        Wert für id-Attribut des Auswahlfelds, erhält im Standard den Wert von name
     * }
     * @return string HTML-Code für Auswahlfeld
     */
    public function dropdownPosts($args)
    {
        $defaults = array(
            'echo' => true,
            'post_type' => array('post'),
            'selected' => 0,
            'name' => '',
            'id' => ''
        );
        $parsedArgs = wp_parse_args($args, $defaults);

        if (empty($parsedArgs['id'])) {
            $parsedArgs['id'] = $parsedArgs['name'];
        }

        $wpQuery = new WP_Query(array(
            'post_type' => $parsedArgs['post_type'],
            'post_status' => 'publish',
            'orderby' => 'type title',
            'order' => 'ASC',
            'nopaging' => true
        ));

        $string = sprintf('<select name="%s" id="%s">', $parsedArgs['name'], $parsedArgs['id']);
        $string .= '<option value="">- keine -</option>';
        if ($wpQuery->have_posts()) {
            $oldPtype = null;
            while ($wpQuery->have_posts()) {
                $wpQuery->the_post();

                // Gruppierung der Elemente nach Beitragstyp
                $postType = get_post_type();
                if ($oldPtype != $postType) {
                    if ($oldPtype != null) {
                        // Nicht beim ersten Mal
                        $string .= '</optgroup>';
                    }
                    $string .= '<optgroup label="' . get_post_type_labels(get_post_type_object($postType))->name . '">';
                }

                // Element ausgeben
                $postId = get_the_ID();
                $postTitle = get_the_title();
                $string .= sprintf(
                    '<option value="%s"' . selected($postId, $parsedArgs['selected'], false) . '>%s</option>',
                    $postId,
                    (empty($postTitle) ? '(Kein Titel)' : $postTitle)
                );
                $oldPtype = $postType;
            }
            $string .= '</optgroup>';
        }
        $string .= '</select>';

        if ($parsedArgs['echo']) {
            echo $string;
        }

        return $string;
    }

    /**
     * @inheritdoc
     */
    public function getAddTermInput()
    {
        return $this->dropdownPosts(array(
            'echo' => false,
            'name' => $this->key,
            'post_type' => $this->getDropdownPostTypes()
        ));
    }

    /**
     * @inheritdoc
     */
    public function getEditTermInput($tag)
    {
        return $this->dropdownPosts(array(
            'echo' => false,
            'selected' => $this->getValue($tag->term_id),
            'name' => $this->key,
            'post_type' => $this->getDropdownPostTypes()
        ));
    }

    /**
     * @inheritdoc
     */
    public function getColumnContent($termId)
    {
        $postId = $this->getValue($termId);

        if (empty($postId)) {
            return '';
        }

        $title = get_the_title($postId);
        return sprintf(
            '<a href="%1$s" title="&quot;%2$s&quot; ansehen" target="_blank">%3$s</a>',
            get_permalink($postId),
            esc_attr($title),
            esc_html($title)
        );
    }

    /**
     * @inheritDoc
     */
    public function getEditPostInput(WP_Post $post)
    {
        return $this->dropdownPosts(array(
            'echo' => false,
            'selected' => $this->getValue($post->ID),
            'name' => $this->key,
            'post_type' => $this->getDropdownPostTypes()
        ));
    }
}
