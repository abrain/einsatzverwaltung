<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Frontend\ReportList;

/**
 * Ersetzt die Shortcodes durch Inhalte
 */
class Shortcodes
{
    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * @var Core
     */
    private $core;

    /**
     * @var Options
     */
    private $options;

    /**
     * Constructor
     *
     * @param Utilities $utilities
     * @param Core $core
     * @param Options $options
     */
    public function __construct($utilities, $core, $options)
    {
        $this->addHooks();
        $this->utilities = $utilities;
        $this->core = $core;
        $this->options = $options;
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
        $shortcodeParams = shortcode_atts(array(
            'jahr' => date('Y'),
            'sort' => 'ab',
            'monatetrennen' => 'nein',
            'link' => 'title',
            'options' => ''
        ), $atts);
        $jahr = $shortcodeParams['jahr'];
        $sort = $shortcodeParams['sort'];
        $monateTrennen = $shortcodeParams['monatetrennen'];
        $linkColIds = $shortcodeParams['link'];

        // Optionen auswerten
        $rawOptions = array_map('trim', explode(',', $shortcodeParams['options']));
        $possibleOptions = array('special', 'noLinkWithoutContent');
        $filteredOptions = array_intersect($possibleOptions, $rawOptions);
        $showOnlySpecialReports = in_array('special', $filteredOptions);
        $linkEmptyReports = !in_array('noLinkWithoutContent', $filteredOptions);

        // Datumsabfrage zusammenbasteln
        $dateQuery = array();
        if ($jahr == '*') {
            $jahreMitEinsatz = Data::getJahreMitEinsatz();
            foreach ($jahreMitEinsatz as $year) {
                $dateQuery[] = array('year' => $year);
            }
            $dateQuery['relation'] = 'OR';
        } elseif (is_numeric($jahr) && $jahr < 0) {
            for ($i=0; $i < abs(intval($jahr)) && $i < $aktuelles_jahr; $i++) {
                $dateQuery[] = array('year' => $aktuelles_jahr - $i);
            }
            $dateQuery['relation'] = 'OR';
        } elseif (empty($jahr) || strlen($jahr)!=4 || !is_numeric($jahr)) {
            echo '<p>' . sprintf('INFO: Jahreszahl %s ung&uuml;ltig, verwende %s', $jahr, $aktuelles_jahr) . '</p>';
            $dateQuery = array('year' => $aktuelles_jahr);
        } else {
            // FIXME hier sind noch keine Fehlerfälle abgefangen
            $dateQuery = array('year' => $jahr);
        }

        // Metaabfrage zusammenbasteln
        $metaQuery = array();
        if ($showOnlySpecialReports) {
            $metaQuery[] = array('key' => 'einsatz_special', 'value' => '1');
        }

        $columnsWithLink = $this->utilities->sanitizeColumnsArray(explode(',', $linkColIds));

        // TODO Für diese Art von Anfragen eine eigene Klasse wie WP_Query bauen
        $posts = get_posts(array(
            'post_type' => 'einsatz',
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => ($sort == 'auf' ? 'ASC' : 'DESC'),
            'nopaging' => true,
            'date_query' => $dateQuery,
            'meta_query' => $metaQuery,
        ));
        $reports = $this->utilities->postsToIncidentReports($posts);

        $reportList = new ReportList($this->utilities, $this->core, $this->options);
        return $reportList->getList(
            $reports,
            array(
                'splitMonths' => ($monateTrennen == 'ja'),
                'columns' => $this->options->getEinsatzlisteEnabledColumns(),
                'columnsWithLink' => $columnsWithLink,
                'linkEmptyReports' => $linkEmptyReports
            )
        );
    }

    /**
     * Gibt Links zu den Archivseiten der Jahre, in denen Einsatzberichte existieren, zurück
     *
     * @return string
     */
    public function einsatzjahre()
    {
        global $year, $wp_rewrite;
        $jahre = Data::getJahreMitEinsatz();

        $string = '';
        foreach ($jahre as $jahr) {
            if (!empty($string)) {
                $string .= ' | ';
            }

            $link = get_post_type_archive_link('einsatz');
            $link = ($wp_rewrite->using_permalinks() ? trailingslashit($link) : $link . '&year=') . $jahr;
            $string .= '<a href="' . user_trailingslashit($link) . '">';

            if ($year == $jahr || empty($year) && $jahr == date('Y')) {
                $string .= "<strong>$jahr</strong>";
            } else {
                $string .= $jahr;
            }

            $string .= '</a>';
        }

        return $string;
    }
}
