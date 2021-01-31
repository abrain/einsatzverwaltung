<?php
namespace abrain\Einsatzverwaltung\Admin;

use WP_Screen;

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
     * @param string[] $hidden
     * @param WP_Screen $screen
     *
     * @return string[]
     */
    public function filterDefaultHiddenMetaboxes($hidden, WP_Screen $screen): array
    {
        if ($screen->post_type !== $this->customTypeSlug) {
            return $hidden;
        }

        // Hide the custom fields by default
        $hidden[] = 'postcustom';

        return $hidden;
    }
}
