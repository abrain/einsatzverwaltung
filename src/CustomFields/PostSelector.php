<?php
namespace abrain\Einsatzverwaltung\CustomFields;

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
     * @inheritdoc
     */
    public function getAddTermMarkup()
    {
        return sprintf(
            '<div class="form-field"><label for="tag-%1$s">%2$s</label>%4$s<p>%3$s</p></div>',
            esc_attr($this->key),
            esc_html($this->label),
            esc_html($this->description),
            $this->dropdownPosts(array(
                'echo' => false,
                'name' => 'fahrzeugpid',
                'post_type' => $this->getDropdownPostTypes()
            ))
        );
    }

    /**
     * @inheritdoc
     */
    public function getEditTermMarkup($tag)
    {
        $value = $this->getValue($tag->term_id);

        return sprintf(
            '<tr class="form-field"><th scope="row"><label for="%1$s">%2$s</label></th><td>%4$s<p class="description">%3$s</p></td></tr>',
            esc_attr($this->key),
            esc_html($this->label),
            esc_html($this->description),
            $this->dropdownPosts(array(
                'echo' => false,
                'selected' => $value,
                'name' => 'fahrzeugpid',
                'post_type' => $this->getDropdownPostTypes()
            ))
        );
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

        $wpQuery = new \WP_Query(array(
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
}
