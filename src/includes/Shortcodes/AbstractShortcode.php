<?php
namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Types\Unit;
use function array_intersect;
use function array_key_exists;
use function array_map;
use function explode;
use function is_string;
use function shortcode_atts;
use const ARRAY_A;
use const CASE_LOWER;

/**
 * Base class for Shortcode renderers that provides common functions
 *
 * @package abrain\Einsatzverwaltung\Shortcodes
 */
abstract class AbstractShortcode
{
    /**
     * The default attributes for the shortcode. Will be used to filter unknown attributes and provide default values
     * for missing attributes.
     *
     * @var array
     */
    private $defaultAttributes;

    /**
     * @param array $defaultAttributes
     */
    public function __construct(array $defaultAttributes)
    {
        $this->defaultAttributes = $defaultAttributes;
    }

    /**
     * @param array|string $attributes
     *
     * @return string
     */
    abstract public function render($attributes): string;

    /**
     * Can be overridden to rewrite the attributes before matching them against the default attributes.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function fixOutdatedAttributes(array $attributes): array
    {
        return $attributes;
    }

    /**
     * Filters the provided attributes based on the default attributes of the shortcode. Subclasses can rewrite the
     * attributes in `fixOutdatedAttributes()` before they get filtered.
     *
     * @param array|string $attributes
     *
     * @return array
     */
    protected function getAttributes($attributes): array
    {
        // See https://core.trac.wordpress.org/ticket/45929
        $attributesArray = is_string($attributes) ? [] : $attributes;

        // Ignore capitalization of attribute keys
        $attributesArray = array_change_key_case($attributesArray, CASE_LOWER);

        // Ensure backwards compatibility
        $attributesArray = $this->fixOutdatedAttributes($attributesArray);

        return shortcode_atts($this->defaultAttributes, $attributesArray);
    }

    /**
     * Extracts a list of integers from a comma-separated string
     *
     * @param string $value
     *
     * @return int[] All the valid numbers from the string. Empty array if key doesn't exist.
     */
    protected function getIntegerList(string $value): array
    {
        $integers = explode(',', $value);
        $integers = array_map('trim', $integers);
        $integers = array_filter($integers, 'is_numeric');

        return array_map('intval', $integers);
    }

    /**
     * Extracts a list of allowed strings from a comma-separated string.
     *
     * @param string $value
     * @param string[] $allowedValues
     *
     * @return string[]
     */
    protected function getStringList(string $value, array $allowedValues): array
    {
        $givenValues = array_map('trim', explode(',', $value));
        return array_intersect($allowedValues, $givenValues);
    }

    /**
     * Translates IDs of old Units to the IDs of the new Units.
     *
     * @param int[] $idsToTranslate
     *
     * @return int[]
     */
    protected function translateOldUnitIds(array $idsToTranslate): array
    {
        global $wpdb;

        if (empty($idsToTranslate)) {
            return $idsToTranslate;
        }

        // Get a mapping of old and new IDs from the database
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT term_id, meta_value FROM $wpdb->termmeta WHERE term_id IN (SELECT term_id FROM $wpdb->term_taxonomy WHERE taxonomy = %s) AND meta_key = %s",
            Unit::getSlug(),
            'old_unit_id'
        ), ARRAY_A);
        if (empty($results)) {
            return $idsToTranslate;
        }

        $map = [];
        foreach ($results as $result) {
            $map[$result['meta_value']] = $result['term_id'];
        }

        return array_map(function ($idToTranslate) use ($map) {
            return array_key_exists($idToTranslate, $map) ? $map[$idToTranslate] : $idToTranslate;
        }, $idsToTranslate);
    }
}
