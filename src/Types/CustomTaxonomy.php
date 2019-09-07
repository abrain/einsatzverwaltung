<?php

namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFieldsRepository;

/**
 * Interface CustomTaxonomy
 * @package abrain\Einsatzverwaltung\Types
 */
interface CustomTaxonomy extends CustomType
{
    /**
     * @param CustomFieldsRepository $customFields
     *
     * @return void
     */
    public function registerCustomFields(CustomFieldsRepository $customFields);
}
