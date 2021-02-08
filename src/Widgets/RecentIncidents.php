<?php
namespace abrain\Einsatzverwaltung\Widgets;

use abrain\Einsatzverwaltung\Frontend\AnnotationIconBar;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ReportQuery;
use abrain\Einsatzverwaltung\Types\Unit;
use abrain\Einsatzverwaltung\Util\Formatter;
use abrain\Einsatzverwaltung\Utilities;
use function array_merge;
use function checked;
use function esc_html;
use function get_queried_object_id;
use function get_taxonomy;
use function printf;

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
     * @var array
     */
    private $defaults;

    /**
     * Register widget with WordPress.
     *
     * @param Formatter $formatter
     */
    public function __construct(Formatter $formatter)
    {
        parent::__construct(
            'einsatzverwaltung_widget',
            'Letzte Eins&auml;tze',
            [
                'description' => 'Zeigt die neuesten Eins&auml;tze an.',
                'customize_selective_refresh' => true,
            ]
        );
        $this->formatter = $formatter;

        $this->defaults = [
            'title' => 'Letzte Eins&auml;tze',
            'anzahl' => 3,
            'units' => [],
            'zeigeDatum' => false,
            'zeigeZeit' => false,
            'zeigeFeedlink' => false,
            'zeigeOrt' => false,
            'zeigeArt' => false,
            'zeigeArtHierarchie' => false,
            'showAnnotations' => false
        ];
    }

    /**
     * @inheritDoc
     */
    public function widget($args, $instance)
    {
        $instance = array_merge($this->defaults, $instance);
        $title = empty($instance['title']) ? $this->defaults['title'] : $instance['title'];

        echo $args['before_widget'];
        echo $args['before_title'];
        echo esc_html(apply_filters('widget_title', $title));
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
    private function echoReports(array $instance)
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
            printf('<div class="annotation-icon-bar">%s</div>', $annotationIconBar->render($report->getPostId()));
        }

        if (get_queried_object_id() === $report->getPostId()) {
            $format = '<a href="%s" rel="bookmark" aria-current="page" class="einsatzmeldung">%s</a>';
        } else {
            $format = '<a href="%s" rel="bookmark" class="einsatzmeldung">%s</a>';
        }
        $meldung = get_the_title($report->getPostId());
        printf(
            $format,
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
    public function update($newInstance, $oldInstance): array
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
    public function form($instance): string
    {
        $instance = array_merge($this->defaults, $instance);

        printf(
            '<p><label for="%1$s">%2$s</label><input class="widefat" id="%1$s" name="%3$s" type="text" value="%4$s" /></p>',
            $this->get_field_id('title'),
            'Titel:',
            $this->get_field_name('title'),
            esc_attr($instance['title'])
        );

        printf(
            '<p><label for="%1$s">%2$s</label>&nbsp;<input class="tiny-text" id="%1$s" name="%3$s" type="number" min="1" value="%4$s" size="3" /></p>',
            $this->get_field_id('anzahl'),
            'Anzahl der Einsatzberichte, die angezeigt werden:',
            $this->get_field_name('anzahl'),
            esc_attr($instance['anzahl'])
        );

        $this->echoChecklistBox(
            get_taxonomy(Unit::getSlug()),
            'units',
            __('Only show reports for these units:', 'einsatzverwaltung'),
            $instance['units'],
            __('Select no unit to show all reports', 'einsatzverwaltung')
        );

        echo '<p>';
        $this->echoCheckbox($instance, 'zeigeFeedlink', 'Link zum Feed anzeigen');
        echo '</p>';

        echo '<p><strong>Einsatzdaten:</strong></p>';

        echo '<p>';
        $this->echoCheckbox($instance, 'zeigeDatum', 'Datum anzeigen');
        echo '</p><p style="text-indent:1em;">';
        $this->echoCheckbox($instance, 'zeigeZeit', 'Zeit anzeigen (nur in Kombination mit Datum)');
        echo '</p><p>';
        $this->echoCheckbox($instance, 'zeigeArt', 'Einsatzart anzeigen');
        echo '</p><p style="text-indent:1em;">';
        $this->echoCheckbox($instance, 'zeigeArtHierarchie', 'Hierarchie der Einsatzart anzeigen');
        echo '</p><p>';
        $this->echoCheckbox($instance, 'zeigeOrt', 'Ort anzeigen');
        echo '</p>';

        printf(
            '<p><input id="%1$s" name="%2$s" type="checkbox" value="1" %3$s />&nbsp;<label for="%1$s">%4$s</label></p>',
            esc_attr($this->get_field_id('showAnnotations')),
            esc_attr($this->get_field_name('showAnnotations')),
            checked($instance['showAnnotations'], '1', false),
            'Vermerke anzeigen'
        );

        return '';
    }
}
