<?php

namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\TaxonomyCustomFields;

/**
 * Interface CustomTaxonomy
 * @package abrain\Einsatzverwaltung\Types
 */
interface CustomTaxonomy extends CustomType
{
    /**
     * @param TaxonomyCustomFields $taxonomyCustomFields
     *
     * @return void
     */
    public function registerCustomFields(TaxonomyCustomFields $taxonomyCustomFields);
}
