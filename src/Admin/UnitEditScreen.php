<?php
namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Types\Unit;

/**
 * Customizations for the edit screen for the Unit custom post type.
 *
 * @package abrain\Einsatzverwaltung\Admin
 */
class UnitEditScreen extends EditScreen
{
    /**
     * UnitEditScreen constructor.
     */
    public function __construct()
    {
        $this->customTypeSlug = Unit::POST_TYPE;
    }
}
