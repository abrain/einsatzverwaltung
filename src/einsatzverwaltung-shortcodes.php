<?php
namespace abrain\Einsatzverwaltung;

/**
 * Ersetzt die Shortcodes durch Inhalte
 */
class Shortcodes
{
    private $frontend;

    /**
     * Constructor
     *
     * @param Frontend $frontend
     */
    public function __construct($frontend)
    {
        $this->addHooks();
        $this->frontend = $frontend;
    }

    private function addHooks()
    {
        add_shortcode('einsatzliste', array($this, 'einsatzliste'));
        add_shortcode('einsatzjahre', array($this, 'einsatzjahre'));
    }

    /**
     * Gibt eine Tabelle mit Einsätzen aus dem gegebenen Jahr zurück
     *
     * @param array $atts Parameter des Shortcodes
     *
     * @return string
     */
    public function einsatzliste($atts)
    {
        $aktuelles_jahr = date('Y');

        // Shortcodeparameter auslesen
        $shortcodeParams = shortcode_atts(array('jahr' => date('Y'), 'sort' => 'ab', 'monatetrennen' => 'nein'), $atts);
        $jahr = $shortcodeParams['jahr'];
        $sort = $shortcodeParams['sort'];
        $monateTrennen = $shortcodeParams['monatetrennen'];

        $einsatzjahre = array();
        if ($jahr == '*') {
            $einsatzjahre = Data::getJahreMitEinsatz();
        } elseif (is_numeric($jahr) && $jahr < 0) {
            for ($i=0; $i < abs(intval($jahr)) && $i < $aktuelles_jahr; $i++) {
                $einsatzjahre[] = $aktuelles_jahr - $i;
            }
        } elseif (empty($jahr) || strlen($jahr)!=4 || !is_numeric($jahr)) {
            echo '<p>' . sprintf('INFO: Jahreszahl %s ung&uuml;ltig, verwende %s', $jahr, $aktuelles_jahr) . '</p>';
            $einsatzjahre = array($aktuelles_jahr);
        } else {
            $einsatzjahre = array($jahr);
        }

        return $this->frontend->printEinsatzliste($einsatzjahre, !($sort == 'auf'), ($monateTrennen == 'ja'));
    }

    /**
     * Gibt Links zu den Archivseiten der Jahre, in denen Einsatzberichte existieren, zurück
     *
     * @return string
     */
    public function einsatzjahre()
    {
        global $year;
        $jahre = Data::getJahreMitEinsatz();
        $permalink_structure = get_option('permalink_structure');

        $string = "";
        foreach ($jahre as $jahr) {
            if (!empty($string)) {
                $string .= " | ";
            }

            $link = get_post_type_archive_link('einsatz') . (empty($permalink_structure) ? '&year='.$jahr : $jahr);
            $string .= '<a href="' . $link . '">';

            if ($year == $jahr || empty($year) && $jahr == date("Y")) {
                $string .= "<strong>".$jahr."</strong>";
            } else {
                $string .= $jahr;
            }

            $string .= "</a>";
        }

        return $string;
    }
}
