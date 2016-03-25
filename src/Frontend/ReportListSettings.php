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
    const DEFAULT_ZEBRACOLOR = '#eee';

    /**
     * Eine Farbe der Zebrastreifen, die nicht vom Theme vorgegeben wird
     *
     * @return string
     */
    public function getZebraColor()
    {
        return get_option('einsatzvw_list_zebracolor', self::DEFAULT_ZEBRACOLOR);
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
     * Gibt an, ob die Zeilen der Tabelle abwechselnd eingefärbt werden sollen
     *
     * @return boolean
     */
    public function isZebraTable()
    {
        return (bool) get_option('einsatzvw_list_zebra', true);
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
