<?php
namespace abrain\Einsatzverwaltung\Api;

/**
 * Encapsulates the creation of REST API controllers.
 * @package abrain\Einsatzverwaltung\Api
 */
class Initializer
{
    /**
     * Registers the routes. This function must not be called before the `rest_api_init` action.
     */
    public function onRestApiInit()
    {
        (new Reports())->register_routes();
    }
}
