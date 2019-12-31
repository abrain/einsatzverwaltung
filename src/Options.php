<?php
namespace abrain\Einsatzverwaltung;

/**
 * Bietet Schnittstellen zur Abfrage von Einstellungen
 */
class Options
{
    private $defaults = array(
        'einsatzvw_show_einsatzart_archive' => false,
        'einsatzvw_show_exteinsatzmittel_archive' => false,
        'einsatzvw_open_ext_in_new' => false,
        'einsatzvw_show_einsatzberichte_mainloop' => false,
        'einsatzvw_einsatz_hideemptydetails' => true,
        'einsatzvw_flush_rewrite_rules' => false,
        'einsatzvw_category' => false,
        'einsatzvw_loop_only_special' => false,
        'einsatzverwaltung_incidentnumbers_auto' => false,
        'einsatzverwaltung_use_excerpttemplate' => false,
    );

    /**
     * Ruft die benannte Option aus der Datenbank ab
     *
     * @param string $key Schlüssel der Option
     *
     * @return mixed
     */
    public function getOption($key)
    {
        if (array_key_exists($key, $this->defaults)) {
            return get_option($key, $this->defaults[$key]);
        }

        // Fehlenden Standardwert beklagen, außer es handelt sich um eine Rechteeinstellung
        if (strpos($key, 'einsatzvw_cap_roles_') !== 0) {
            error_log(sprintf('Kein Standardwert für %s gefunden!', $key));
        }

        return get_option($key, false);
    }

    /**
     * @param string $key Schlüssel der Option
     *
     * @return bool
     */
    public function getBoolOption($key)
    {
        $option = $this->getOption($key);
        return $this->toBoolean($option);
    }

    /**
     * Gibt die Kategorie zurück, in der neben Beiträgen auch Einsatzberichte angezeigt werden sollen
     *
     * @since 1.0.0
     *
     * @return int Die ID der Kategorie oder -1, wenn nicht gesetzt
     */
    public function getEinsatzberichteCategory()
    {
        $categoryId = $this->getOption('einsatzvw_category');
        return (false === $categoryId ? -1 : intval($categoryId));
    }

    /**
     * @since 1.0.0
     *
     * @return bool
     */
    public function isFlushRewriteRules()
    {
        return $this->getBoolOption('einsatzvw_flush_rewrite_rules');
    }

    /**
     * Gibt die Option einsatzvw_einsatz_hideemptydetails als bool zurück
     *
     * @return bool
     */
    public function isHideEmptyDetails()
    {
        $option = $this->getOption('einsatzvw_einsatz_hideemptydetails');
        return $this->toBoolean($option);
    }

    /**
     * Gibt zurück, ob nur als besonders markierte Einsatzberichte zwischen normalen WordPress-Beiträgen angezeigt
     * werden sollen
     *
     * @return bool
     */
    public function isOnlySpecialInLoop()
    {
        return $this->getBoolOption('einsatzvw_loop_only_special');
    }


    /**
     * @return bool
     */
    public function isOpenExtEinsatzmittelNewWindow()
    {
        $option = $this->getOption('einsatzvw_open_ext_in_new');
        return $this->toBoolean($option);
    }

    /**
     * @return bool
     */
    public function isShowEinsatzartArchive()
    {
        $option = $this->getOption('einsatzvw_show_einsatzart_archive');
        return $this->toBoolean($option);
    }

    /**
     * @return bool
     */
    public function isShowReportsInLoop()
    {
        $option = $this->getOption('einsatzvw_show_einsatzberichte_mainloop');
        return $this->toBoolean($option);
    }

    /**
     * @return bool
     */
    public function isShowExtEinsatzmittelArchive()
    {
        $option = $this->getOption('einsatzvw_show_exteinsatzmittel_archive');
        return $this->toBoolean($option);
    }

    /**
     * @since 1.0.0
     *
     * @param bool $value
     */
    public function setFlushRewriteRules($value)
    {
        update_option('einsatzvw_flush_rewrite_rules', $value ? 1 : 0);
    }

    /**
     * @param $value
     *
     * @return bool
     */
    private function toBoolean($value)
    {
        return in_array($value, array(1, true, '1', 'yes', 'on'), true);
    }
}
