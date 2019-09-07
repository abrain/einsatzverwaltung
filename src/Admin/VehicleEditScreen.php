<?php
namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Types\Vehicle;

/**
 * Customizations for the edit screen for the Vehicle custom post type.
 *
 * @package abrain\Einsatzverwaltung\Admin
 */
class VehicleEditScreen extends EditScreen
{
    /**
     * VehicleEditScreen constructor.
     */
    public function __construct()
    {
        $this->customTypeSlug = Vehicle::getSlug();
    }
}
