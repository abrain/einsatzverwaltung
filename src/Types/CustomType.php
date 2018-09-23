<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\TaxonomyCustomFields;

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

    /**
     * @param TaxonomyCustomFields $taxonomyCustomFields
     * @return void
     */
    public function registerCustomFields(TaxonomyCustomFields $taxonomyCustomFields);
}
