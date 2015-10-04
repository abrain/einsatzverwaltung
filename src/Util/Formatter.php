<?php
namespace abrain\Einsatzverwaltung\Util;

/**
 * Formatierungen aller Art
 *
 * @author Andreas Brain
 */
class Formatter
{
    public static function format($post, $pattern)
    {
        $formattedString = $pattern;

        $formattedString = str_replace('%title%', get_the_title($post), $formattedString);

        return $formattedString;
    }
}