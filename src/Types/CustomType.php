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
    public static function getSlug();

    /**
     * @return array
     */
    public function getRegistrationArgs();

    /**
     * @return void
     */
    public function registerHooks();
}
