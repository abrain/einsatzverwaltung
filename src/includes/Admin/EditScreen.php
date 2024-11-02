<?php
namespace abrain\Einsatzverwaltung\Admin;

use WP_Screen;
use WP_Taxonomy;
use WP_Term;
use function checked;
use function esc_attr;
use function esc_html;
use function in_array;
use function printf;
use function str_replace;

/**
 * Base class for the edit screen customizations of custom post types
 *
 * @package abrain\Einsatzverwaltung\Admin
 */
abstract class EditScreen
{
    /**
     * @var string
     */
    protected $customTypeSlug;

    /**
     * Gibt eine Checkbox für die Metabox aus
     *
     * @param string $label Beschriftung
     * @param string $name Feld-ID
     * @param bool $state Zustandswert
     */
    protected function echoInputCheckbox(string $label, string $name, bool $state)
    {
        printf(
            '<input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s/><label for="%1$s">%3$s</label>',
            esc_attr($name),
            checked($state, '1', false),
            $label
        );
    }

    /**
     * Gibt ein Eingabefeld für die Metabox aus
     *
     * @param string $label Beschriftung
     * @param string $name Feld-ID
     * @param string $value Feldwert
     * @param string $placeholder Platzhalter
     * @param int $size Größe des Eingabefelds
     */
    protected function echoInputText(string $label, string $name, string $value, $placeholder = '', $size = 20)
    {
        printf('<tr><td><label for="%1$s">%2$s</label></td>', esc_attr($name), esc_html($label));
        printf(
            '<td><input type="text" id="%1$s" name="%1$s" value="%2$s" size="%3$s" placeholder="%4$s" /></td></tr>',
            esc_attr($name),
            esc_attr($value),
            esc_attr($size),
            esc_attr($placeholder)
        );
    }

    /**
     * @param WP_Term[] $terms
     * @param WP_Taxonomy $taxonomy
     * @param int[] $assignedIds
     */
    protected function echoTermCheckboxes(array $terms, WP_Taxonomy $taxonomy, array $assignedIds)
    {
        $format = '<li><label><input type="checkbox" name="tax_input[%1$s][]" value="%2$s" %3$s>%4$s</label></li>';
        if ($taxonomy->hierarchical) {
            $format = str_replace('%2$s', '%2$d', $format);
        }
        foreach ($terms as $term) {
            $assigned = in_array($term->term_id, $assignedIds);
            printf(
                $format,
                $taxonomy->name,
                ($taxonomy->hierarchical ? esc_attr($term->term_id) : esc_attr($term->name)),
                checked($assigned, true, false),
                esc_html($term->name)
            );
        }
    }

    /**
     * @param string[] $hidden
     * @param WP_Screen $screen
     *
     * @return string[]
     */
    public function filterDefaultHiddenMetaboxes(array $hidden, WP_Screen $screen): array
    {
        if ($screen->post_type !== $this->customTypeSlug) {
            return $hidden;
        }

        // Hide the custom fields by default
        $hidden[] = 'postcustom';

        return $hidden;
    }
}
