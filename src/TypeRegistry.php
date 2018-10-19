<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Exceptions\TypeRegistrationException;
use abrain\Einsatzverwaltung\Types\Alarmierungsart;
use abrain\Einsatzverwaltung\Types\CustomType;
use abrain\Einsatzverwaltung\Types\ExtEinsatzmittel;
use abrain\Einsatzverwaltung\Types\IncidentType;
use abrain\Einsatzverwaltung\Types\Report;
use abrain\Einsatzverwaltung\Types\Vehicle;

/**
 * Central place to register custom post types and taxonomies with WordPress
 *
 * @package abrain\Einsatzverwaltung
 */
class TypeRegistry
{
    private $postTypes = array();
    private $taxonomies = array();

    /**
     * @var TaxonomyCustomFields
     */
    private $taxonomyCustomFields;

    /**
     * TypeRegistry constructor.
     */
    public function __construct()
    {
        $this->taxonomyCustomFields = new TaxonomyCustomFields();
    }

    /**
     * Erzeugt den neuen Beitragstyp Einsatzbericht und die zugehörigen Taxonomien
     *
     * @param PermalinkController $permalinkController
     *
     * @throws TypeRegistrationException
     */
    public function registerTypes(PermalinkController $permalinkController)
    {
        $report = new Report();
        $this->registerPostType($report);
        $this->registerTaxonomy(new IncidentType(), $report->getSlug());
        $this->registerTaxonomy(new Vehicle(), $report->getSlug());
        $this->registerTaxonomy(new ExtEinsatzmittel(), $report->getSlug());
        $this->registerTaxonomy(new Alarmierungsart(), $report->getSlug());

        $permalinkController->addRewriteRules($report);
    }

    /**
     * Registers a custom post type
     *
     * @param CustomType $customType Object that describes the custom post type
     * @throws TypeRegistrationException
     */
    private function registerPostType(CustomType $customType)
    {
        $slug = $customType->getSlug();
        if (array_key_exists($slug, $this->postTypes)) {
            throw new TypeRegistrationException(
                sprintf(__('Post type with slug "%s" already exists', 'einsatzverwaltung'), $slug)
            );
        }

        $postType = register_post_type($slug, $customType->getRegistrationArgs());
        if (is_wp_error($postType)) {
            throw new TypeRegistrationException(sprintf(
                __('Failed to register post type with slug "%s": %s', 'einsatzverwaltung'),
                $slug,
                $postType->get_error_message()
            ));
        }

        $customType->registerCustomFields($this->taxonomyCustomFields);

        $this->postTypes[$slug] = $postType;
    }

    /**
     * Registers a custom taxonomy for a certain post type
     *
     * @param CustomType $customTaxonomy Object that describes the custom taxonomy
     * @param string $postType
     * @throws TypeRegistrationException
     */
    private function registerTaxonomy(CustomType $customTaxonomy, $postType)
    {
        $slug = $customTaxonomy->getSlug();
        if (get_taxonomy($slug) !== false) {
            throw new TypeRegistrationException(
                sprintf(__('Taxonomy with slug "%s" already exists', 'einsatzverwaltung'), $slug)
            );
        }

        $postType = register_taxonomy($slug, $postType, $customTaxonomy->getRegistrationArgs());
        if (is_wp_error($postType)) {
            throw new TypeRegistrationException(sprintf(
                __('Failed to register post type with slug "%s": %s', 'einsatzverwaltung'),
                $slug,
                $postType->get_error_message()
            ));
        }

        $customTaxonomy->registerCustomFields($this->taxonomyCustomFields);

        array_push($this->taxonomies, $slug);
    }
}
