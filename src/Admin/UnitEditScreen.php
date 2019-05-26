<?php

namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Types\Unit;
use WP_Screen;

/**
 * Class UnitEditScreen
 * @package abrain\Einsatzverwaltung\Admin
 */
class UnitEditScreen
{
    /**
     * @param string[] $hidden
     * @param WP_Screen $screen
     *
     * @return string[]
     */
    public function filterDefaultHiddenMetaboxes($hidden, WP_Screen $screen)
    {
        if ($screen->post_type !== Unit::POST_TYPE) {
            return $hidden;
        }

        $hidden[] = 'postcustom';
        return $hidden;
    }
}
