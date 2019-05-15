<?php

namespace abrain\Einsatzverwaltung\Types;

/**
 * Interface CustomPostType
 * @package abrain\Einsatzverwaltung\Types
 */
interface CustomPostType extends CustomType
{
    /**
     * @return void
     */
    public function registerCustomFields();
}
