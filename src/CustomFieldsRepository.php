<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\CustomFields\CustomField;
use abrain\Einsatzverwaltung\Types\CustomPostType;
use abrain\Einsatzverwaltung\Types\CustomTaxonomy;
use abrain\Einsatzverwaltung\Types\CustomType;
use WP_Term;
use function add_action;
use function add_term_meta;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_search;
use function array_slice;
use function array_unique;
use function current_action;
use function delete_term_meta;
use function explode;
use function get_term_meta;
use function is_numeric;
use function preg_match;

/**
 * Keeps track of the custom fields of our custom types
 *
 * @package abrain\Einsatzverwaltung
 */
class CustomFieldsRepository
{
    /**
     * @var array
     */
    private $postTypeFields;

    /**
     * @var array
     */
    private $taxonomyFields;

    /**
     * TaxonomyCustomFields constructor.
     */
    public function __construct()
    {
        $this->postTypeFields = array();
        $this->taxonomyFields = array();
    }

    /**
     * Register the actions and filters, that this class expects.
     */
    public function addHooks()
    {
        add_action('edited_term', array($this, 'saveTerm'), 10, 3);
        add_action('created_term', array($this, 'saveTerm'), 10, 3);
    }

    /**
     * @param CustomType $customType
     * @param CustomField $customField
     */
    public function add(CustomType $customType, CustomField $customField)
    {
        if ($customType instanceof CustomTaxonomy) {
            $taxonomy = $customType::getSlug();

            if (!array_key_exists($taxonomy, $this->taxonomyFields)) {
                $this->taxonomyFields[$taxonomy] = array();
                add_action("{$taxonomy}_add_form_fields", array($this, 'onAddFormFields'));
                add_action("{$taxonomy}_edit_form_fields", array($this, 'onEditFormFields'), 10, 2);
                add_action("manage_edit-{$taxonomy}_columns", array($this, 'onTaxonomyCustomColumns'));
                add_filter("manage_{$taxonomy}_custom_column", array($this, 'onTaxonomyColumnContent'), 10, 3);
            }

            $this->taxonomyFields[$taxonomy][$customField->key] = $customField;
        } elseif ($customType instanceof CustomPostType) {
            $postType = $customType::getSlug();

            if (!array_key_exists($postType, $this->postTypeFields)) {
                $this->postTypeFields[$postType] = array();
                add_filter("manage_edit-{$postType}_columns", array($this, 'onPostCustomColumns'));
                add_action("manage_{$postType}_posts_custom_column", array($this, 'onPostColumnContent'), 10, 2);
            }

            $this->postTypeFields[$postType][$customField->key] = $customField;
        }
    }

    /**
     * Get all CustomFields for a given custom type.
     *
     * @param string $slug The post_type or taxonomy slug
     *
     * @return CustomField[]
     */
    public function getFieldsForType($slug): array
    {
        if ($this->hasPostType($slug)) {
            return $this->postTypeFields[$slug];
        } elseif ($this->hasTaxonomy($slug)) {
            return $this->taxonomyFields[$slug];
        }

        return array();
    }

    /**
     * Checks if we can iterate over custom fields for a certain post type.
     *
     * @param string $postType
     *
     * @return bool
     */
    private function hasPostType($postType): bool
    {
        return array_key_exists($postType, $this->postTypeFields) && is_array($this->postTypeFields[$postType]);
    }

    /**
     * Checks if we can iterate over custom fields for a certain taxonomy.
     *
     * @param string $taxonomy
     *
     * @return bool
     */
    private function hasTaxonomy($taxonomy): bool
    {
        return array_key_exists($taxonomy, $this->taxonomyFields) && is_array($this->taxonomyFields[$taxonomy]);
    }

    /**
     * @param string $taxonomy The taxonomy slug.
     */
    public function onAddFormFields($taxonomy)
    {
        if (!$this->hasTaxonomy($taxonomy)) {
            return;
        }

        /** @var CustomField $field */
        foreach ($this->taxonomyFields[$taxonomy] as $field) {
            echo $field->getAddTermMarkup();
        }
    }

    /**
     * @param WP_Term $tag Current taxonomy term object.
     * @param string $taxonomy Current taxonomy slug.
     */
    public function onEditFormFields($tag, $taxonomy)
    {
        if (!$this->hasTaxonomy($taxonomy)) {
            return;
        }

        /** @var CustomField $field */
        foreach ($this->taxonomyFields[$taxonomy] as $field) {
            echo $field->getEditTermMarkup($tag);
        }
    }

    /**
     * @param string $columnId
     * @param int $postId
     */
    public function onPostColumnContent($columnId, $postId)
    {
        $postType = get_post_type($postId);
        if (!$this->hasPostType($postType)) {
            return;
        }

        $fields = $this->postTypeFields[$postType];
        if (!array_key_exists($columnId, $fields)) {
            return;
        }

        /** @var CustomField $customField */
        $customField = $fields[$columnId];
        echo $customField->getColumnContent($postId);
    }

    /**
     * Adds custom columns to our own post types.
     *
     * @param string[] $columns
     *
     * @return string[] An associative array of column headings.
     */
    public function onPostCustomColumns($columns): array
    {
        $currentAction = current_action();
        if (preg_match('/^manage_edit-(\w+)_columns$/', $currentAction, $matches) !== 1) {
            return $columns;
        }

        $postType = $matches[1];
        if (!$this->hasPostType($postType)) {
            return $columns;
        }

        /** @var CustomField $field */
        foreach ($this->postTypeFields[$postType] as $field) {
            $columns[$field->key] = $field->label;
        }

        return $columns;
    }

    /**
     * Filterfunktion für den Inhalt der selbst angelegten Spalten
     *
     * @param string $string Leerer String.
     * @param string $columnName Name der Spalte
     * @param int $termId Term ID
     *
     * @return string Inhalt der Spalte
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) A WordPress hook with fixed signature
     * @noinspection PhpUnusedParameterInspection
     */
    public function onTaxonomyColumnContent($string, $columnName, $termId): string
    {
        $term = get_term($termId);
        if (empty($term) || is_wp_error($term)) {
            return '';
        }

        $taxonomy = $term->taxonomy;
        if (!$this->hasTaxonomy($taxonomy)) {
            return '';
        }

        $fields = $this->taxonomyFields[$taxonomy];
        if (!array_key_exists($columnName, $fields)) {
            return '';
        }

        /** @var CustomField $customField */
        $customField = $fields[$columnName];
        return $customField->getColumnContent($termId);
    }

    /**
     * Adds custom columns to our own taxonomies.
     *
     * @param string[] $columns An associative array of column headings.
     *
     * @return string[]
     */
    public function onTaxonomyCustomColumns($columns): array
    {
        $currentAction = current_action();
        if (preg_match('/^manage_edit-(\w+)_columns$/', $currentAction, $matches) !== 1) {
            return $columns;
        }

        $taxonomy = $matches[1];
        if (!$this->hasTaxonomy($taxonomy)) {
            return $columns;
        }

        // Add the columns after the description column
        $index = array_search('description', array_keys($columns));
        $index = is_numeric($index) ? $index + 1 : count($columns);
        $before = array_slice($columns, 0, $index, true);
        $after = array_slice($columns, $index, null, true);

        $columnsToAdd = array();
        /** @var CustomField $field */
        foreach ($this->taxonomyFields[$taxonomy] as $field) {
            $columnsToAdd[$field->key] = $field->label;
        }

        return array_merge($before, $columnsToAdd, $after);
    }

    /**
     * Saves custom taxonomy fields to termmeta.
     *
     * @param int $termId Term ID
     * @param int $ttId Term taxonomy ID
     * @param string $taxonomy Taxonomy slug
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) A WordPress hook with fixed signature
     * @noinspection PhpUnusedParameterInspection
     */
    public function saveTerm($termId, $ttId, $taxonomy)
    {
        if (!$this->hasTaxonomy($taxonomy)) {
            return;
        }

        /** @var CustomField $field */
        foreach ($this->taxonomyFields[$taxonomy] as $field) {
            // TODO choose filter based on type of CustomField
            $value = filter_input(INPUT_POST, $field->key);

            if ($value === null) {
                continue;
            }

            if ($field->isMultiValue()) {
                $existingValues = get_term_meta($termId, $field->key);
                $desiredValues = array_unique(array_filter(array_map('trim', explode("\n", $value))));

                $valuesToAdd = array_diff($desiredValues, $existingValues);
                $valuesToRemove = array_diff($existingValues, $desiredValues);
                foreach ($valuesToAdd as $valueToAdd) {
                    add_term_meta($termId, $field->key, $valueToAdd);
                }
                foreach ($valuesToRemove as $valueToRemove) {
                    delete_term_meta($termId, $field->key, $valueToRemove);
                }
            } else {
                update_term_meta($termId, $field->key, empty($value) ? $field->defaultValue : $value);
            }
        }
    }
}
