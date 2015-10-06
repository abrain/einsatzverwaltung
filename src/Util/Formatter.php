<?php
namespace abrain\Einsatzverwaltung\Util;

use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\Frontend;
use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\Utilities;
use WP_Post;

/**
 * Formatierungen aller Art
 *
 * @author Andreas Brain
 */
class Formatter
{
    /**
     * @param WP_Post $post
     * @param string $pattern
     * @return mixed
     */
    public static function formatIncidentData($post, $pattern)
    {
        $timeOfAlerting = Data::getAlarmzeit($post->ID);
        $timeOfAlertingTS = strtotime($timeOfAlerting);

        $formattedString = str_replace('%title%', get_the_title($post), $pattern);
        $formattedString = str_replace('%date%', date_i18n(Options::getDateFormat(), $timeOfAlertingTS),
            $formattedString);
        $formattedString = str_replace('%time%', date_i18n(Options::getTimeFormat(), $timeOfAlertingTS),
            $formattedString);
        $formattedString = str_replace('%duration%', Utilities::getDurationString(Data::getDauer($post->ID)),
            $formattedString);
        $formattedString = str_replace('%incidentType%', Frontend::getEinsatzartString(Data::getEinsatzart($post->ID),
            false, false, false), $formattedString);
        $formattedString = str_replace('%url%', get_permalink($post->ID), $formattedString);
        $formattedString = str_replace('%location%', Data::getEinsatzort($post->ID), $formattedString);

        return $formattedString;
    }
}