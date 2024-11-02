<?php
namespace abrain\Einsatzverwaltung\CustomFields;

use abrain\Einsatzverwaltung\Types\Unit;
use Exception;
use WP_Post;
use function esc_html;
use function get_term;
use function is_wp_error;
use function wp_dropdown_categories;

/**
 * A dropdown for selecting a single Unit
 * @package abrain\Einsatzverwaltung\CustomFields
 */
class UnitSelector extends CustomField
{
    /**
     * @param string $key
     * @param string $label
     * @param string $description
     */
    public function __construct(string $key, string $label, string $description)
    {
        parent::__construct($key, $label, $description, -1);
    }

    /**
     * @inheritdoc
     */
    public function getAddTermInput(): string
    {
        return wp_dropdown_categories(array(
            'show_option_none'   => _x('- none -', 'unit dropdown', 'einsatzverwaltung'),
            'orderby'            => 'NAME',
            'order'              => 'ASC',
            'hide_empty'         => false,
            'echo'               => false,
            'name'               => $this->key,
            'taxonomy'           => Unit::getSlug(),
        ));
    }

    /**
     * @inheritdoc
     */
    public function getEditTermInput($tag): string
    {
        return wp_dropdown_categories(array(
            'show_option_none'   => _x('- none -', 'unit dropdown', 'einsatzverwaltung'),
            'orderby'            => 'NAME',
            'order'              => 'ASC',
            'hide_empty'         => false,
            'echo'               => false,
            'name'               => $this->key,
            'taxonomy'           => Unit::getSlug(),
            'selected'           => $this->getValue($tag->term_id),
        ));
    }

    /**
     * @inheritdoc
     */
    public function getColumnContent($termId): string
    {
        $unitTermId = $this->getValue($termId);

        $unit = get_term($unitTermId, Unit::getSlug());
        if (empty($unit) || is_wp_error($unit)) {
            return '';
        }

        return esc_html($unit->name);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getEditPostInput(WP_Post $post): string
    {
        throw new Exception('Not yet implemented');
    }
}
