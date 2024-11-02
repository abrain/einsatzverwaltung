<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Frontend\ReportList\Renderer as ReportListRenderer;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Types\IncidentType;
use abrain\Einsatzverwaltung\Types\Report;
use abrain\Einsatzverwaltung\Types\Unit;
use abrain\Einsatzverwaltung\Types\Vehicle;
use abrain\Einsatzverwaltung\Util\Formatter;
use WP_Post;
use WP_Query;
use function add_filter;
use function date_i18n;
use function get_post_type;
use function get_term_meta;
use function get_the_terms;
use function in_array;
use function is_wp_error;
use function sprintf;
use function wp_enqueue_style;
use function wp_kses;

/**
 * Generiert alle Inhalte für das Frontend, mit Ausnahme der Shortcodes und des Widgets
 */
class Frontend
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Options
     */
    private $options;

    /**
     * Constructor
     *
     * @param Options $options
     * @param Formatter $formatter
     */
    public function __construct(Options $options, Formatter $formatter)
    {
        $this->formatter = $formatter;
        $this->options = $options;
    }

    public function addHooks()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueueStyleAndScripts'));
        if (!(
            is_array($_REQUEST) &&
            array_key_exists('plugin', $_REQUEST) && $_REQUEST['plugin'] == 'all-in-one-event-calendar' &&
            array_key_exists('action', $_REQUEST) && $_REQUEST['action'] == 'export_events'
        )) {
            add_filter('the_content', array($this, 'renderContent'), 9);
        }
        add_filter('the_excerpt', array($this, 'filterEinsatzExcerpt'));
        add_filter('the_excerpt_rss', array($this, 'filterEinsatzExcerpt'));
        add_filter('the_excerpt_embed', array($this, 'filterEinsatzExcerpt'));
        add_action('pre_get_posts', array($this, 'addReportsToQuery'));
        add_filter('default_post_metadata', array($this, 'filterDefaultThumbnail'), 10, 3);

        // Adjustment for Avada theme
        add_action('awb_remove_third_party_the_content_changes', function () {
            remove_filter('the_content', array($this, 'renderContent'), 9);
        }, 5);
        add_action('awb_readd_third_party_the_content_changes', function () {
            add_filter('the_content', array($this, 'renderContent'), 9);
        }, 99);
    }

    /**
     * Bindet CSS für das Frontend ein
     */
    public function enqueueStyleAndScripts()
    {
        if (get_option('einsatzvw_disable_fontawesome') !== '1') {
            wp_enqueue_style(
                'einsatzverwaltung-font-awesome',
                Core::$pluginUrl . 'font-awesome/css/fontawesome.min.css',
                false,
                '6.2.1'
            );
            wp_enqueue_style(
                'einsatzverwaltung-font-awesome-solid',
                Core::$pluginUrl . 'font-awesome/css/solid.min.css',
                array('einsatzverwaltung-font-awesome'),
                '6.2.1'
            );
        }

        wp_enqueue_style(
            'einsatzverwaltung-frontend',
            Core::$styleUrl . 'style-frontend.css',
            array(),
            Core::VERSION
        );
        wp_add_inline_style('einsatzverwaltung-frontend', ReportListRenderer::getDynamicCss());
        wp_enqueue_script('einsatzverwaltung-reportlist', Core::$scriptUrl . 'reportlist.js');
    }

    /**
     * Erzeugt den Kopf eines Einsatzberichts
     *
     * @param WP_Post $post Das Post-Objekt
     * @param bool $mayContainLinks True, wenn Links generiert werden dürfen
     * @param bool $showArchiveLinks Bestimmt, ob Links zu Archivseiten generiert werden dürfen
     *
     * @return string Auflistung der Einsatzdetails
     */
    public function getEinsatzberichtHeader(WP_Post $post, bool $mayContainLinks = true, bool $showArchiveLinks = true): string
    {
        if (get_post_type($post) !== Report::getSlug()) {
            return '';
        }

        $report = new IncidentReport($post);

        $duration = $report->getDuration();
        $durationString = ($duration === false ? '' : $this->formatter->getDurationString($duration));

        $showTypeArchiveLink = $showArchiveLinks && $this->options->isShowEinsatzartArchive();
        $art = $this->formatter->getTypeOfIncident($report, $mayContainLinks, $showTypeArchiveLink);

        if ($report->isFalseAlarm()) {
            $art = (empty($art) ? 'Fehlalarm' : $art . ' (Fehlalarm)');
        }

        $timeOfAlerting = $report->getTimeOfAlerting();
        $dateAndTime = empty($timeOfAlerting) ? '-' : sprintf(
            /* translators: 1: Date, 2: Time. */
            __('%1$s at %2$s', 'einsatzverwaltung'),
            date_i18n(get_option('date_format'), $timeOfAlerting->getTimestamp()),
            date_i18n(get_option('time_format'), $timeOfAlerting->getTimestamp())
        );
        $headerstring = $this->getDetailString(__('Date', 'einsatzverwaltung'), $dateAndTime);

        $headerstring .= $this->getDetailString(__('Alerting method', 'einsatzverwaltung'), $this->formatter->getTypesOfAlerting($report, true));
        $headerstring .= $this->getDetailString(__('Duration', 'einsatzverwaltung'), $durationString);
        $headerstring .= $this->getDetailString(__('Incident Category', 'einsatzverwaltung'), $art);
        $headerstring .= $this->getDetailString(__('Location', 'einsatzverwaltung'), $report->getLocation());
        $headerstring .= $this->getDetailString('Einsatzleiter', $report->getIncidentCommander());
        $headerstring .= $this->getDetailString('Mannschaftsst&auml;rke', $report->getWorkforce());

        // If both units and vehicles have been assigned to any report in the past, show the vehicles grouped by unit
        if (Unit::isActivelyUsed() && Vehicle::isActivelyUsed()) {
            $headerstring .= $this->getDetailString(
                __('Units and Vehicles', 'einsatzverwaltung'),
                $this->formatter->getVehiclesByUnitString($report->getVehiclesByUnit()),
                false
            );
        } else {
            $headerstring .= $this->getDetailString(
                __('Vehicles', 'einsatzverwaltung'),
                $this->formatter->getVehicleString($report->getVehicles(), $mayContainLinks, $showArchiveLinks)
            );
            $headerstring .= $this->getDetailString(
                __('Units', 'einsatzverwaltung'),
                $this->formatter->getUnits($report, $mayContainLinks)
            );
        }

        $additionalForces = $this->formatter->getAdditionalForces($report, $mayContainLinks, $showArchiveLinks);
        $headerstring .= $this->getDetailString('Weitere Kr&auml;fte', $additionalForces);

        return "<p>$headerstring</p>";
    }


    /**
     * Erzeugt eine Zeile für die Einsatzdetails
     *
     * @param string $title Bezeichnung des Einsatzdetails
     * @param string $value Wert des Einsatzdetails
     * @param bool $newline Zeilenumbruch hinzufügen
     *
     * @return string Formatiertes Einsatzdetail
     */
    private function getDetailString(string $title, string $value, bool $newline = true): string
    {
        if ($this->options->isHideEmptyDetails() && (!isset($value) || $value === '')) {
            return '';
        }

        /* translators: Single incident detail, 1: Label, 2: Value */
        $format = __('<b>%1$s:</b> %2$s', 'einsatzverwaltung');
        $filteredFormat = wp_kses($format, ['b' => []]);
        if ($newline) {
            $filteredFormat .= '<br>';
        }

        return sprintf($filteredFormat, $title, $value);
    }


    /**
     * Beim Aufrufen eines Einsatzberichts vor den Text den Kopf mit den Details einbauen
     *
     * @param string $content Der Beitragstext des Einsatzberichts
     *
     * @return string Mit Einsatzdetails angereicherter Beitragstext
     */
    public function renderContent(string $content): string
    {
        $post = get_post();

        // Bail, if the post object is empty, not a report, or the content is password protected
        if (empty($post) || get_post_type($post) !== Report::getSlug() || post_password_required($post)) {
            return $content;
        }

        if ($this->useReportTemplate()) {
            $template = get_option('einsatzverwaltung_reporttemplate', '');

            if (empty($template)) {
                return $content;
            }

            $replacementText = get_option('einsatzverwaltung_report_contentifempty', '');
            if (empty($content) && !empty($replacementText)) {
                $content = sprintf('<p>%s</p>', esc_html($replacementText));
            }
            
            $templateWithData = $this->formatter->formatIncidentData($template, array(), $post);
            return str_replace('%content%', $content, $templateWithData);
        }

        if (!is_singular('einsatz')) {
            return $content;
        }

        // Fallback auf das klassische Layout
        $header = $this->getEinsatzberichtHeader($post);
        $content = $this->prepareContent($content);

        return $header . '<hr>' . $content;
    }

    /**
     * Entscheidet, ob für die Ausgabe des Einsatzberichts das Template verwendet wird oder nicht
     *
     * @return bool
     */
    private function useReportTemplate(): bool
    {
        $useTemplate = get_option('einsatzverwaltung_use_reporttemplate', 'no');

        if ($useTemplate === 'no') {
            return false;
        }

        if ($useTemplate === 'singular' && is_singular('einsatz') && is_main_query() && in_the_loop()) {
            return true;
        }

        if ($useTemplate === 'loops' && get_post_type() === 'einsatz' && is_main_query() && in_the_loop()) {
            return true;
        }

        if ($useTemplate === 'everywhere' && get_post_type() === 'einsatz') {
            return true;
        }

        return false;
    }

    /**
     * Bereitet den Beitragstext auf
     *
     * @param string $content Der Beitragstext des Einsatzberichts
     *
     * @return string Der Beitragstext mit einer vorangestellten Überschrift. Wenn der Beitragstext leer ist, wird ein
     * Ersatztext zurückgegeben
     */
    private function prepareContent(string $content): string
    {
        $replacementText = get_option('einsatzverwaltung_report_contentifempty', '');
        if (!empty($replacementText)) {
            $replacementText = sprintf('<p>%s</p>', esc_html($replacementText));
        }

        return empty($content) ? $replacementText : '<h3>Einsatzbericht:</h3>' . $content;
    }


    /**
     * Stellt den Auszug zur Verfügung, im Fall von Einsatzberichten wird
     * hier wahlweise der Berichtstext, Einsatzdetails oder beides zurückgegeben
     *
     * @param string $excerpt Filterparameter, wird bei Einsatzberichten nicht beachtet, bei anderen Beitragstypen
     * unverändert verwendet
     *
     * @return string Der Auszug
     */
    public function filterEinsatzExcerpt(string $excerpt): string
    {
        global $post;
        if (get_post_type() !== 'einsatz') {
            return $excerpt;
        }

        if (get_option('einsatzverwaltung_use_excerpttemplate') !== '1') {
            return $excerpt;
        }

        $template = get_option('einsatzverwaltung_excerpttemplate', '');

        if (empty($template)) {
            return $excerpt;
        }

        $formatted = $this->formatter->formatIncidentData($template, array(), $post, is_feed() ? 'feed' : 'post');
        return stripslashes(wp_filter_post_kses(addslashes($formatted)));
    }

    /**
     * Gibt Einsatzberichte ggf. auch zwischen den 'normalen' Blogbeiträgen aus
     *
     * @param WP_Query $query
     */
    public function addReportsToQuery(WP_Query $query)
    {
        // Nur, wenn Filter erlaubt sind, soll weitergemacht werden
        if (!empty($query->query_vars['suppress_filters'])) {
            return;
        }

        // Im Adminbereich wird nicht herumgepfuscht!
        if (is_admin()) {
            return;
        }

        // Bei Abfragen einzelner Posts gibt es auch nichts zu ändern
        if ($query->is_singular()) {
            return;
        }

        $categoryId = $this->options->getEinsatzberichteCategory();
        if ($this->options->isShowReportsInLoop() || $query->is_category($categoryId)) {
            // Einsatzberichte mit abfragen
            if (isset($query->query_vars['post_type'])) {
                $postTypes = (array) $query->query_vars['post_type'];
            } else {
                $postTypes = array('post');
            }
            
            // Einsatzberichte nur zusammen mit Beiträgen abfragen
            if (!in_array('post', $postTypes)) {
                return;
            }

            $postTypes[] = 'einsatz';
            $query->set('post_type', $postTypes);

            if ($this->options->isOnlySpecialInLoop()) {
                // Nur als besonders markierte Einsatzberichte abfragen
                $metaQuery = $query->get('meta_query');
                if (empty($metaQuery)) {
                    $metaQuery = array();
                }
                $metaQuery['relation'] = 'OR';
                $metaQuery[] = array(
                    'key' => 'einsatz_special',
                    'value' => '1'
                );
                // normale Beiträge haben diesen Metaeintrag nicht, sollen aber trotzdem angezeigt werden
                $metaQuery[] = array(
                    'key' => 'einsatz_special',
                    'value' => '1',
                    'compare' => 'NOT EXISTS'
                );
                $query->set('meta_query', $metaQuery);
            }
        }
    }

    /**
     * Filters the default value of the thumbnail ID for incident reports. Is used to provide a fallback thumbnail,
     * which is defined per incident type.
     *
     * @param mixed $value The default value to filter, probably null.
     * @param int $postId ID of the object metadata is for.
     * @param string $metaKey Metadata key.
     *
     * @return mixed
     */
    public function filterDefaultThumbnail($value, int $postId, string $metaKey)
    {
        // Only continue if this is about the thumbnail of an incident report
        if ($metaKey !== '_thumbnail_id' || get_post_type($postId) !== Report::getSlug()) {
            return $value;
        }

        // Check if an incident type has been assigned
        $terms = get_the_terms($postId, IncidentType::getSlug());
        if (empty($terms) || is_wp_error($terms)) {
            return $value;
        }

        // Use the fallback thumbnail of the incident type, if set
        $fallbackThumbId = get_term_meta($terms[0]->term_id, 'default_featured_image', true);
        return empty($fallbackThumbId) ? $value : $fallbackThumbId;
    }
}
