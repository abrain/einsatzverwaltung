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
    const DEFAULT_NTHCHILD = 'even';

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
     * Gibt das Argument für den :nth-child()-Selektor für die Zebrastreifen zurück
     *
     * @return string
     */
    public function getZebraNthChildArg()
    {
        $option = get_option('einsatzvw_list_zebra_nth', self::DEFAULT_NTHCHILD);
        return $this->sanitizeZebraNthChildArg($option);
    }

    /**
     * @return boolean
     */
    public function isZebraTable()
    {
        return $this->zebraTable;
    }

    /**
     * Stellt sicher, dass das Argument für den :nth-child()-Selektor für die Zebrastreifen gültig ist
     *
     * @param string $input Der zu prüfende Wert
     *
     * @return string
     */
    public function sanitizeZebraNthChildArg($input)
    {
        if (!in_array($input, array('odd', 'even'))) {
            return self::DEFAULT_NTHCHILD;
        }

        return $input;
    }
}
