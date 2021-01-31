<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\CustomFields\CustomField;
use abrain\Einsatzverwaltung\Types\CustomPostType;
use abrain\Einsatzverwaltung\Types\CustomTaxonomy;
use abrain\Einsatzverwaltung\Types\CustomType;
use abrain\Einsatzverwaltung\Types\Report;
use WP_Term;
use function add_action;

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
                add_action("manage_edit-{$taxonomy}_columns", array($this, 'onCustomColumns'));
                add_filter("manage_{$taxonomy}_custom_column", array($this, 'onTaxonomyColumnContent'), 10, 3);
            }

            $this->taxonomyFields[$taxonomy][$customField->key] = $customField;
        } elseif ($customType instanceof CustomPostType) {
            $postType = $customType::getSlug();

            if (!array_key_exists($postType, $this->postTypeFields)) {
                $this->postTypeFields[$postType] = array();
                add_filter("manage_edit-{$postType}_columns", array($this, 'onCustomColumns'));
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
     * Fügt für die zusätzlichen Felder zusätzliche Spalten in der Übersicht ein
     *
     * @param array $columns
     * @return array
     */
    public function onCustomColumns($columns): array
    {
        $screen = get_current_screen();

        if (empty($screen)) {
            return $columns;
        }

        if ($screen->post_type === Report::getSlug() && !empty($screen->taxonomy)) {
            $taxonomy = $screen->taxonomy;
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

        $postType = $screen->post_type;
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
     * Filterfunktion für den Inhalt der selbst angelegten Spalten
     *
     * @param string $string Leerer String.
     * @param string $columnName Name der Spalte
     * @param int $termId Term ID
     *
     * @return string Inhalt der Spalte
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
     * Speichert zusätzliche Infos zu Terms als options ab
     *
     * @param int $termId Term ID
     * @param int $ttId Term taxonomy ID
     * @param string $taxonomy Taxonomy slug
     */
    public function saveTerm($termId, $ttId, $taxonomy)
    {
        if (!$this->hasTaxonomy($taxonomy)) {
            return;
        }

        /** @var CustomField $field */
        foreach ($this->taxonomyFields[$taxonomy] as $field) {
            // TODO choose filter based on type of CustomField
            $value = filter_input(INPUT_POST, $field->key, FILTER_SANITIZE_STRING);

            update_term_meta($termId, $field->key, empty($value) ? $field->defaultValue : $value);
        }
    }
}
