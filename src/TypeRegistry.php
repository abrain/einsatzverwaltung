<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Exceptions\TypeRegistrationException;
use abrain\Einsatzverwaltung\Types\Alarmierungsart;
use abrain\Einsatzverwaltung\Types\CustomPostType;
use abrain\Einsatzverwaltung\Types\CustomTaxonomy;
use abrain\Einsatzverwaltung\Types\ExtEinsatzmittel;
use abrain\Einsatzverwaltung\Types\IncidentType;
use abrain\Einsatzverwaltung\Types\Report;
use abrain\Einsatzverwaltung\Types\Unit;
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
     * @var CustomFieldsRepository
     */
    private $customFields;

    /**
     * TypeRegistry constructor.
     *
     * @param CustomFieldsRepository $customFieldsRepo
     */
    public function __construct(CustomFieldsRepository $customFieldsRepo)
    {
        $this->customFields = $customFieldsRepo;
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
        $this->registerTaxonomy(new IncidentType(), $report::getSlug());
        $this->registerTaxonomy(new Vehicle(), $report::getSlug());
        $this->registerTaxonomy(new ExtEinsatzmittel(), $report::getSlug());
        $this->registerTaxonomy(new Alarmierungsart(), $report::getSlug());
        $this->registerTaxonomy(new Unit(), $report::getSlug());

        $permalinkController->addRewriteRules($report);
    }

    /**
     * Registers a custom post type
     *
     * @param CustomPostType $customPostType Object that describes the custom post type
     *
     * @throws TypeRegistrationException
     */
    private function registerPostType(CustomPostType $customPostType)
    {
        $slug = $customPostType::getSlug();
        if (post_type_exists($slug)) {
            throw new TypeRegistrationException(
                sprintf(__('Post type with slug "%s" already exists', 'einsatzverwaltung'), $slug)
            );
        }

        $postType = register_post_type($slug, $customPostType->getRegistrationArgs());
        if (is_wp_error($postType)) {
            throw new TypeRegistrationException(sprintf(
                __('Failed to register post type with slug "%s": %s', 'einsatzverwaltung'),
                $slug,
                $postType->get_error_message()
            ));
        }

        $customPostType->registerCustomFields($this->customFields);
        $customPostType->registerHooks();

        $this->postTypes[$slug] = $postType;
    }

    /**
     * Registers a custom taxonomy for a certain post type
     *
     * @param CustomTaxonomy $customTaxonomy Object that describes the custom taxonomy
     * @param string $postType
     *
     * @throws TypeRegistrationException
     */
    private function registerTaxonomy(CustomTaxonomy $customTaxonomy, string $postType)
    {
        $slug = $customTaxonomy::getSlug();
        if (get_taxonomy($slug) !== false) {
            throw new TypeRegistrationException(
                sprintf(__('Taxonomy with slug "%s" already exists', 'einsatzverwaltung'), $slug)
            );
        }

        $result = register_taxonomy($slug, $postType, $customTaxonomy->getRegistrationArgs());
        if (is_wp_error($result)) {
            throw new TypeRegistrationException(sprintf(
                __('Failed to register taxonomy with slug "%s": %s', 'einsatzverwaltung'),
                $slug,
                $result->get_error_message()
            ));
        }

        $customTaxonomy->registerCustomFields($this->customFields);
        $customTaxonomy->registerHooks();

        array_push($this->taxonomies, $slug);
    }
}
