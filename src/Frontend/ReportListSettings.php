<?php
namespace abrain\Einsatzverwaltung\Frontend;

/**
 * Einstellungsobjekt für die ReportList
 *
 * @author Andreas Brain
 * @package abrain\Einsatzverwaltung\Frontend
 */
class ReportListSettings
{
    /**
     * Eine Farbe der Zebrastreifen, die nicht vom Theme vorgegeben wird
     *
     * @var string
     */
    private $zebraColor;

    /**
     * Gibt an, ob die Zeilen der Tabelle abwechselnd eingefärbt werden sollen
     *
     * @var boolean
     */
    private $zebraTable;

    /**
     * Initialisiert alle Attribute mit den Einstellungen aus der Datenbank, oder wenn diese nicht gesetzt sind, mit
     * deren Standardwerten
     */
    public function __construct()
    {
        $this->zebraColor = get_option('einsatzvw_list_zebracolor', '#eee');
        $this->zebraTable = (bool) get_option('einsatzvw_list_zebra', true);
    }

    /**
     * @return string
     */
    public function getZebraColor()
    {
        return $this->zebraColor;
    }

    /**
     * @return boolean
     */
    public function isZebraTable()
    {
        return $this->zebraTable;
    }
}
