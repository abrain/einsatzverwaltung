<?php

namespace abrain\Einsatzverwaltung\Shortcodes;

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
}
