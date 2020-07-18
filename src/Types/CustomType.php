<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFieldsRepository;

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
     * @return string
     */
    public function getRewriteSlug();

    /**
     * @param CustomFieldsRepository $customFields
     *
     * @return void
     */
    public function registerCustomFields(CustomFieldsRepository $customFields);

    /**
     * @return void
     */
    public function registerHooks();
}
