<?php
namespace abrain\Einsatzverwaltung\Types;

/**
 * Interface CustomType
 * @package abrain\Einsatzverwaltung\Types
 */
interface CustomType
{
    /**
     * @return string
     */
    public function getSlug();

    /**
     * @return array
     */
    public function getRegistrationArgs();
}
