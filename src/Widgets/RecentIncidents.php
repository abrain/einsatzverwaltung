<?php
namespace abrain\Einsatzverwaltung\Widgets;

use abrain\Einsatzverwaltung\Frontend\AnnotationIconBar;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ReportQuery;
use abrain\Einsatzverwaltung\Types\Unit;
use abrain\Einsatzverwaltung\Util\Formatter;
use abrain\Einsatzverwaltung\Utilities;

/**
 * WordPress-Widget für die letzten X Einsätze
 *
 * @author Andreas Brain
 */
class RecentIncidents extends AbstractWidget
{
    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * Register widget with WordPress.
     *
     * @param Formatter $formatter
     */
    public function __construct(Formatter $formatter)
    {
        parent::__construct(
            'einsatzverwaltung_widget', // Base ID
            'Letzte Eins&auml;tze', // Name
            array(
                'description' => 'Zeigt die neuesten Eins&auml;tze an.',
                'customize_selective_refresh' => true,
            ) // Args
        );
        $this->formatter = $formatter;
    }

    /**
     * @inheritDoc
     */
    public function widget($args, $instance)
    {
        $defaults = array(
            'title' => 'Letzte Eins&auml;tze',
            'anzahl' => 3,
            'units' => array(),
            'zeigeDatum' => false,
            'zeigeZeit' => false,
            'zeigeFeedlink' => false,
            'zeigeOrt' => false,
            'zeigeArt' => false,
            'zeigeArtHierarchie' => false,
            'showAnnotations' => false
        );
        $instance = wp_parse_args($instance, $defaults);

        $title = apply_filters('widget_title', $instance['title']);

        echo $args['before_widget'];
        echo $args['before_title'];
        echo esc_html($title);
        echo $args['after_title'];

        $this->echoReports($instance);

        if ($instance['zeigeFeedlink']) {
            printf(
                '<p class="einsatzfeed"><span class="fa fa-rss"></span>&nbsp;<a href="%s">%s</a></p>',
                get_post_type_archive_feed_link('einsatz'),
                'Einsatzberichte (Feed)'
            );
        }
        echo $args['after_widget'];
    }

    /**
     * @param array $instance
     */
    private function echoReports($instance)
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setOrderAsc(false);
        $reportQuery->setLimit(absint($instance['anzahl']));
        $reportQuery->setUnits($instance['units']);
        $reports = $reportQuery->getReports();

        if (empty($reports)) {
            echo '<p>Keine Eins&auml;tze</p>';
            return;
        }

        echo '<ul class="einsatzberichte">';
        foreach ($reports as $report) {
            echo '<li class="einsatzbericht">';
            $this->echoSingleReport($report, $instance);
            echo "</li>";
        }
        echo '</ul>';
    }

    /**
     * @param IncidentReport $report
     * @param array $instance
     */
    private function echoSingleReport(IncidentReport $report, $instance)
    {
        if (true === ($instance['showAnnotations'])) {
            $annotationIconBar = AnnotationIconBar::getInstance();
            printf('<div class="annotation-icon-bar">%s</div>', $annotationIconBar->render($report));
        }

        $meldung = get_the_title($report->getPostId());
        printf(
            '<a href="%s" rel="bookmark" class="einsatzmeldung">%s</a>',
            esc_attr(get_permalink($report->getPostId())),
            (empty($meldung) ? "(kein Titel)" : $meldung)
        );

        if ($instance['zeigeDatum']) {
            $timestamp = $report->getTimeOfAlerting()->getTimestamp();
            printf(
                '<br><span class="einsatzdatum">%s</span>',
                esc_html(date_i18n(get_option('date_format', 'd.m.Y'), $timestamp))
            );
            if ($instance['zeigeZeit']) {
                printf(
                    ' | <span class="einsatzzeit">%s Uhr</span>',
                    esc_html(date_i18n(get_option('time_format', 'H:i'), $timestamp))
                );
            }
        }

        if ($instance['zeigeArt']) {
            $typeOfIncident = $this->formatter->getTypeOfIncident(
                $report,
                false,
                false,
                $instance['zeigeArtHierarchie']
            );
            if (!empty($typeOfIncident)) {
                printf('<br><span class="einsatzart">%s</span>', $typeOfIncident);
            }
        }

        if ($instance['zeigeOrt']) {
            $location = $report->getLocation();
            if (!empty($location)) {
                printf('<br><span class="einsatzort">Ort:&nbsp;%s</span>', $location);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function update($newInstance, $oldInstance)
    {
        $instance = array();
        $instance['title'] = strip_tags($newInstance['title']);

        $anzahl = $newInstance['anzahl'];
        if (empty($anzahl) || !is_numeric($anzahl) || $anzahl < 1) {
            $instance['anzahl'] = $oldInstance['anzahl'];
        } else {
            $instance['anzahl'] = $newInstance['anzahl'];
        }

        $instance['units'] = Utilities::getArrayValueIfKey($newInstance, 'units', array());
        $instance['zeigeDatum'] = Utilities::getArrayValueIfKey($newInstance, 'zeigeDatum', false);
        $instance['zeigeZeit'] = Utilities::getArrayValueIfKey($newInstance, 'zeigeZeit', false);
        $instance['zeigeOrt'] = Utilities::getArrayValueIfKey($newInstance, 'zeigeOrt', false);
        $instance['zeigeArt'] = Utilities::getArrayValueIfKey($newInstance, 'zeigeArt', false);
        $instance['zeigeArtHierarchie'] = Utilities::getArrayValueIfKey($newInstance, 'zeigeArtHierarchie', false);
        $instance['zeigeFeedlink'] = Utilities::getArrayValueIfKey($newInstance, 'zeigeFeedlink', false);
        $instance['showAnnotations'] = '1' === Utilities::getArrayValueIfKey($newInstance, 'showAnnotations', false);

        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function form($instance)
    {
        $title = Utilities::getArrayValueIfKey($instance, 'title', 'Letzte Eins&auml;tze');
        $anzahl = Utilities::getArrayValueIfKey($instance, 'anzahl', 3);
        $selectedUnits = Utilities::getArrayValueIfKey($instance, 'units', array());
        $zeigeDatum = Utilities::getArrayValueIfKey($instance, 'zeigeDatum', false);
        $zeigeZeit = Utilities::getArrayValueIfKey($instance, 'zeigeZeit', false);
        $zeigeFeedlink = Utilities::getArrayValueIfKey($instance, 'zeigeFeedlink', false);
        $zeigeOrt = Utilities::getArrayValueIfKey($instance, 'zeigeOrt', false);
        $zeigeArt = Utilities::getArrayValueIfKey($instance, 'zeigeArt', false);
        $zeigeArtHierarchie = Utilities::getArrayValueIfKey($instance, 'zeigeArtHierarchie', false);
        $showAnnotations = Utilities::getArrayValueIfKey($instance, 'showAnnotations', false);

        printf(
            '<p><label for="%1$s">%2$s</label><input class="widefat" id="%1$s" name="%3$s" type="text" value="%4$s" /></p>',
            $this->get_field_id('title'),
            'Titel:',
            $this->get_field_name('title'),
            esc_attr($title)
        );

        printf(
            '<p><label for="%1$s">%2$s</label>&nbsp;<input class="tiny-text" id="%1$s" name="%3$s" type="number" min="1" value="%4$s" size="3" /></p>',
            $this->get_field_id('anzahl'),
            'Anzahl der Einsatzberichte, die angezeigt werden:',
            $this->get_field_name('anzahl'),
            esc_attr($anzahl)
        );

        $this->echoChecklistBox(
            get_post_type_object(Unit::POST_TYPE),
            'units',
            __('Only show reports for these units:', 'einsatzverwaltung'),
            $selectedUnits,
            __('Select no unit to show all reports', 'einsatzverwaltung')
        );

        printf(
            '<p><input id="%1$s" name="%2$s" type="checkbox" %3$s />&nbsp;<label for="%1$s">%4$s</label></p>',
            esc_attr($this->get_field_id('zeigeFeedlink')),
            esc_attr($this->get_field_name('zeigeFeedlink')),
            checked($zeigeFeedlink, 'on', false),
            'Link zum Feed anzeigen'
        );

        echo '<p><strong>Einsatzdaten:</strong></p>';

        printf(
            '<p><input id="%1$s" name="%2$s" type="checkbox" %3$s />&nbsp;<label for="%1$s">%4$s</label></p>',
            esc_attr($this->get_field_id('zeigeDatum')),
            esc_attr($this->get_field_name('zeigeDatum')),
            checked($zeigeDatum, 'on', false),
            'Datum anzeigen'
        );

        printf(
            '<p style="text-indent:1em;"><input id="%1$s" name="%2$s" type="checkbox" %3$s />&nbsp;<label for="%1$s">%4$s</label></p>',
            esc_attr($this->get_field_id('zeigeZeit')),
            esc_attr($this->get_field_name('zeigeZeit')),
            checked($zeigeZeit, 'on', false),
            'Zeit anzeigen (nur in Kombination mit Datum)'
        );

        printf(
            '<p><input id="%1$s" name="%2$s" type="checkbox" %3$s />&nbsp;<label for="%1$s">%4$s</label></p>',
            esc_attr($this->get_field_id('zeigeArt')),
            esc_attr($this->get_field_name('zeigeArt')),
            checked($zeigeArt, 'on', false),
            'Einsatzart anzeigen'
        );

        printf(
            '<p><input id="%1$s" name="%2$s" type="checkbox" %3$s />&nbsp;<label for="%1$s">%4$s</label></p>',
            esc_attr($this->get_field_id('zeigeArtHierarchie')),
            esc_attr($this->get_field_name('zeigeArtHierarchie')),
            checked($zeigeArtHierarchie, 'on', false),
            'Hierarchie der Einsatzart anzeigen'
        );

        printf(
            '<p><input id="%1$s" name="%2$s" type="checkbox" %3$s />&nbsp;<label for="%1$s">%4$s</label></p>',
            esc_attr($this->get_field_id('zeigeOrt')),
            esc_attr($this->get_field_name('zeigeOrt')),
            checked($zeigeOrt, 'on', false),
            'Ort anzeigen'
        );

        printf(
            '<p><input id="%1$s" name="%2$s" type="checkbox" value="1" %3$s />&nbsp;<label for="%1$s">%4$s</label></p>',
            esc_attr($this->get_field_id('showAnnotations')),
            esc_attr($this->get_field_name('showAnnotations')),
            checked($showAnnotations, '1', false),
            'Vermerke anzeigen'
        );

        return '';
    }
}
