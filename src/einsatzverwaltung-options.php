<?php
namespace abrain\Einsatzverwaltung;

/**
 * Bietet Schnittstellen zur Abfrage von Einstellungen
 */
class Options
{
    private static $defaults = array(
        'einsatzvw_list_columns' => 'number,date,time,title',
        'einsatzvw_einsatznummer_stellen' => 3,
        'einsatzvw_show_einsatzart_archive' => false,
        'einsatzvw_show_exteinsatzmittel_archive' => false,
        'einsatzvw_show_fahrzeug_archive' => false,
        'einsatzvw_open_ext_in_new' => false,
        'einsatzvw_excerpt_type' => 'details',
        'einsatzvw_excerpt_type_feed' => 'details',
        'einsatzvw_show_einsatzberichte_mainloop' => false,
        'einsatzvw_einsatz_hideemptydetails' => true,
        'einsatzvw_einsatznummer_lfdvorne' => false,
        'date_format' => 'd.m.Y',
        'time_format' => 'H:i',
        'einsatzvw_cap_roles_administrator' => true,
        'einsatzvw_list_art_hierarchy' => false,
        'einsatzvw_list_ext_link' => false,
        'einsatzvw_list_fahrzeuge_link' => false,
        'einsatzvw_rewrite_slug' => 'einsatzberichte',
        'einsatzvw_flush_rewrite_rules' => false,
        'einsatzvw_category' => false
    );

    /**
     * Ruft die benannte Option aus der Datenbank ab
     *
     * @param string $key Schlüssel der Option
     *
     * @return mixed
     */
    public static function getOption($key)
    {
        if (array_key_exists($key, self::$defaults)) {
            $defaultValue = self::$defaults[$key];
        } else {
            if (strpos($key, 'einsatzvw_cap_roles_') !== 0) {
                error_log(sprintf('Kein Standardwert für %s gefunden!', $key));
            }
            $defaultValue = false;
        }
        return get_option($key, $defaultValue);
    }

    /**
     * @param string $key Schlüssel der Option
     *
     * @return bool
     */
    public static function getBoolOption($key)
    {
        $option = self::getOption($key);
        return self::toBoolean($option);
    }

    public static function getDefaultColumns()
    {
        return self::$defaults['einsatzvw_list_columns'];
    }

    public static function getDefaultEinsatznummerStellen()
    {
        return self::$defaults['einsatzvw_einsatznummer_stellen'];
    }

    public static function getDefaultExcerptType()
    {
        return self::$defaults['einsatzvw_excerpt_type'];
    }

    /**
     * Gibt das Datumsformat von WordPress zurück
     */
    public static function getDateFormat()
    {
        return self::getOption('date_format');
    }

    /**
     * Gibt die Kategorie zurück, in der neben Beiträgen auch Einsatzberichte angezeigt werden sollen
     *
     * @return int Die ID der Kategorie oder 0, wenn nicht gesetzt
     */
    public static function getEinsatzberichteCategory()
    {
        $categoryId = self::getOption('einsatzvw_category');
        return (false === $categoryId ? 0 : intval($categoryId));
    }

    /**
     * Gibt die aktiven Spalten für die Einsatzliste zurück
     *
     * @return array Spalten-IDs der aktiven Spalten, geprüft auf Existenz. Bei Problemen die Standardspalten.
     */
    public static function getEinsatzlisteEnabledColumns()
    {
        $enabledColumns = self::getOption('einsatzvw_list_columns');
        $enabledColumns = Utilities::sanitizeColumns($enabledColumns);
        return explode(',', $enabledColumns);
    }

    /**
     * @return int
     */
    public static function getEinsatznummerStellen()
    {
        $option = self::getOption('einsatzvw_einsatznummer_stellen');
        return Utilities::sanitizeEinsatznummerStellen($option);
    }

    /**
     * @return string
     */
    public static function getExcerptType()
    {
        $option = self::getOption('einsatzvw_excerpt_type');
        return Utilities::sanitizeExcerptType($option);
    }

    /**
     * @return string
     */
    public static function getExcerptTypeFeed()
    {
        $option = self::getOption('einsatzvw_excerpt_type_feed');
        return Utilities::sanitizeExcerptType($option);
    }

    /**
     * Gibt die Basis für die URL zu Einsatzberichten zurück
     *
     * @return string
     */
    public static function getRewriteSlug()
    {
        $option = self::getOption('einsatzvw_rewrite_slug');
        return sanitize_title($option, self::$defaults['einsatzvw_rewrite_slug']);
    }

    /**
     * @return mixed
     */
    public static function getTimeFormat()
    {
        return self::getOption('time_format');
    }

    /**
     * @return bool
     */
    public static function isEinsatznummerLfdVorne()
    {
        $option = self::getOption('einsatzvw_einsatznummer_lfdvorne');
        return self::toBoolean($option);
    }

    /**
     * @return bool
     */
    public static function isFlushRewriteRules()
    {
        return self::getBoolOption('einsatzvw_flush_rewrite_rules');
    }

    /**
     * Gibt die Option einsatzvw_einsatz_hideemptydetails als bool zurück
     *
     * @return bool
     */
    public static function isHideEmptyDetails()
    {
        $option = self::getOption('einsatzvw_einsatz_hideemptydetails');
        return self::toBoolean($option);
    }

    /**
     * @return bool
     */
    public static function isOpenExtEinsatzmittelNewWindow()
    {
        $option = self::getOption('einsatzvw_open_ext_in_new');
        return self::toBoolean($option);
    }

    /**
     * @param $roleSlug
     *
     * @return bool
     */
    public static function isRoleAllowedToEdit($roleSlug)
    {
        if ($roleSlug === 'administrator') {
            return true;
        }

        $option = self::getOption('einsatzvw_cap_roles_' . $roleSlug);
        return self::toBoolean($option);
    }

    /**
     * @return bool
     */
    public static function isShowEinsatzartArchive()
    {
        $option = self::getOption('einsatzvw_show_einsatzart_archive');
        return self::toBoolean($option);
    }

    /**
     * @return bool
     */
    public static function isShowEinsatzberichteInMainloop()
    {
        $option = self::getOption('einsatzvw_show_einsatzberichte_mainloop');
        return self::toBoolean($option);
    }

    /**
     * @return bool
     */
    public static function isShowExtEinsatzmittelArchive()
    {
        $option = self::getOption('einsatzvw_show_exteinsatzmittel_archive');
        return self::toBoolean($option);
    }

    /**
     * @return bool
     */
    public static function isShowFahrzeugArchive()
    {
        $option = self::getOption('einsatzvw_show_fahrzeug_archive');
        return self::toBoolean($option);
    }

    /**
     * @param bool $value
     */
    public static function setFlushRewriteRules($value)
    {
        update_option('einsatzvw_flush_rewrite_rules', $value ? 1 : 0);
    }

    /**
     * @param $value
     *
     * @return bool
     */
    private static function toBoolean($value)
    {
        return in_array($value, array(1, true, '1', 'yes', 'on'), true);
    }
}
