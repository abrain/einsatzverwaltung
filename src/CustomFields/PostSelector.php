<?php
namespace abrain\Einsatzverwaltung\CustomFields;

use WP_Post;
use WP_Query;
use function __;
use function esc_html__;
use function sprintf;

/**
 * Represents an additional dropdown of a taxonomy for selecting posts
 * @package abrain\Einsatzverwaltung\CustomFields
 */
class PostSelector extends CustomField
{
    /**
     * @var array
     */
    private $postTypes;

    /**
     * @param string $key
     * @param string $label
     * @param string $description
     * @param string[] $postTypes The slugs of the post types that should be included in the dropdown.
     */
    public function __construct($key, $label, $description, $postTypes = array('post'))
    {
        parent::__construct($key, $label, $description, '');
        $this->postTypes = $postTypes;
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
    public function dropdownPosts($args): string
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
        $string .= sprintf('<option value="">%s</option>', esc_html__('- none -', 'einsatzverwaltung'));
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
                    (empty($postTitle) ? sprintf('ID %d', $postId) : $postTitle)
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
    public function getAddTermInput(): string
    {
        return $this->dropdownPosts(array(
            'echo' => false,
            'name' => $this->key,
            'post_type' => $this->postTypes
        ));
    }

    /**
     * @inheritdoc
     */
    public function getEditTermInput($tag): string
    {
        return $this->dropdownPosts(array(
            'echo' => false,
            'selected' => $this->getValue($tag->term_id),
            'name' => $this->key,
            'post_type' => $this->postTypes
        ));
    }

    /**
     * @inheritdoc
     */
    public function getColumnContent($termId): string
    {
        $postId = $this->getValue($termId);

        if (empty($postId)) {
            return '';
        }

        $title = get_the_title($postId);
        return sprintf(
            '<a href="%1$s" title="%2$s" target="_blank">%3$s</a>',
            get_permalink($postId),
            // translators: 1: Title of a page
            esc_attr(sprintf(__('View "%1$s"', 'einsatzverwaltung'), $title)),
            esc_html($title)
        );
    }

    /**
     * @inheritDoc
     */
    public function getEditPostInput(WP_Post $post): string
    {
        return $this->dropdownPosts(array(
            'echo' => false,
            'selected' => $this->getValue($post->ID),
            'name' => $this->key,
            'post_type' => $this->postTypes
        ));
    }
}
