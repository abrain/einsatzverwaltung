<?php
namespace abrain\Einsatzverwaltung\Frontend\ReportList;

/**
 * Einstellungsobjekt für die ReportList
 *
 * @author Andreas Brain
 * @package abrain\Einsatzverwaltung\Frontend\ReportList
 */
class Settings
{
    const DEFAULT_NTHCHILD = 'even';
    const DEFAULT_ZEBRACOLOR = '#eee';

    /**
     * Eine Farbe der Zebrastreifen, die nicht vom Theme vorgegeben wird
     *
     * @return string
     */
    public function getZebraColor(): string
    {
        $option = get_option('einsatzvw_list_zebracolor', self::DEFAULT_ZEBRACOLOR);
        $sanitized = sanitize_hex_color($option);
        if (empty($sanitized)) {
            $sanitized = self::DEFAULT_ZEBRACOLOR;
        }

        return $sanitized;
    }

    /**
     * Gibt das Argument für den :nth-child()-Selektor für die Zebrastreifen zurück
     *
     * @return string
     */
    public function getZebraNthChildArg(): string
    {
        $option = get_option('einsatzvw_list_zebra_nth', self::DEFAULT_NTHCHILD);
        return $this->sanitizeZebraNthChildArg($option);
    }

    /**
     * Gibt an, ob die Zeilen der Tabelle abwechselnd eingefärbt werden sollen
     *
     * @return boolean
     */
    public function isZebraTable(): bool
    {
        return (get_option('einsatzvw_list_zebra', '1') !== '0');
    }

    /**
     * Stellt sicher, dass das Argument für den :nth-child()-Selektor für die Zebrastreifen gültig ist
     *
     * @param string $input Der zu prüfende Wert
     *
     * @return string
     */
    public function sanitizeZebraNthChildArg($input): string
    {
        if (!in_array($input, array('odd', 'even'))) {
            return self::DEFAULT_NTHCHILD;
        }

        return $input;
    }
}
