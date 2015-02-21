<?php
namespace abrain\Einsatzverwaltung;

/**
 * Bietet Schnittstellen zur Abfrage von Einstellungen
 */
class Options
{
    const DEFAULT_COLUMNS = 'number,date,time,title';

    /**
     * @return array
     */
    public static function getEinsatzlisteEnabledColumns()
    {
        $enabledColumns = get_option('einsatzvw_list_columns', self::DEFAULT_COLUMNS);
        return explode(',', $enabledColumns);
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
