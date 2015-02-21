<?php
namespace abrain\Einsatzverwaltung;

/**
 * Bietet Schnittstellen zur Abfrage von Einstellungen
 */
class Options
{
    const DEFAULT_COLUMNS = 'number,date,time,title';
    const DEFAULT_EINSATZNR_STELLEN = 3;

    /**
     * @return array
     */
    public static function getEinsatzlisteEnabledColumns()
    {
        $enabledColumns = get_option('einsatzvw_list_columns', self::DEFAULT_COLUMNS);
        return explode(',', $enabledColumns);
    }

    /**
     * @return int
     */
    public static function getEinsatznummerStellen()
    {
        return get_option('einsatzvw_einsatznummer_stellen', self::DEFAULT_EINSATZNR_STELLEN);
    }

    /**
     * Gibt die Option einsatzvw_einsatz_hideemptydetails als bool zurück
     *
     * @return bool
     */
    public static function isHideEmptyDetails()
    {
        $hide_empty_details = get_option('einsatzvw_einsatz_hideemptydetails');
        if ($hide_empty_details === false) {
            return EINSATZVERWALTUNG__D__HIDE_EMPTY_DETAILS;
        } else {
            return ($hide_empty_details == 1 ? true : false);
        }
    }
}
