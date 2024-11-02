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
    public static function getSlug(): string;

    /**
     * @return array
     */
    public function getRegistrationArgs(): array;

    /**
     * @return string
     */
    public function getRewriteSlug(): string;

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
