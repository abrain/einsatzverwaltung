<?php

namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Types\Unit;
use function array_key_exists;
use function array_map;
use const ARRAY_A;

/**
 * Base class for Shortcode renderers that provides common functions
 *
 * @package abrain\Einsatzverwaltung\Shortcodes
 */
abstract class AbstractShortcode
{
    /**
     * @param array $atts
     *
     * @return string
     */
    abstract public function render($atts);

    /**
     * Extracts a list of integers from a comma-separated string
     *
     * @param array $attributes Attributes array
     * @param string $key Array key to access the string to extract from
     *
     * @return int[] All the valid numbers from the string. Empty array if key doesn't exist.
     */
    protected function getIntegerList($attributes, $key)
    {
        if (!array_key_exists($key, $attributes)) {
            return array();
        }

        $integers = explode(',', $attributes[$key]);
        $integers = array_map('trim', $integers);
        $integers = array_filter($integers, 'is_numeric');

        return array_map('intval', $integers);
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
