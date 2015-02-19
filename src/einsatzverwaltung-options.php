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
}
