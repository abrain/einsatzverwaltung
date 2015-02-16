<?php
namespace abrain\Einsatzverwaltung;

/**
 * Bietet Schnittstellen zur Abfrage von Einstellungen
 */
class Options
{
    const DEFAULT_COLUMNS = 'enr,datum,zeit,titel';

    /**
     * @return mixed|void
     */
    public static function getEinsatzlisteEnabledColumns()
    {
        return get_option('einsatzvw_list_columns', self::DEFAULT_COLUMNS);
    }
}
