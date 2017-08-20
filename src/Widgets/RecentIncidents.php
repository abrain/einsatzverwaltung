<?php
namespace abrain\Einsatzverwaltung\Widgets;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Frontend\AnnotationIconBar;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\Util\Formatter;
use abrain\Einsatzverwaltung\Utilities;
use WP_Widget;

/**
 * WordPress-Widget für die letzten X Einsätze
 *
 * @author Andreas Brain
 */
class RecentIncidents extends WP_Widget
{
    /**
     * @var Formatter
     */
    private static $formatter;

    /**
     * @var Options
     */
    private static $options;

    /**
     * @var Utilities
     */
    private static $utilities;

    /**
     * Register widget with WordPress.
     */
    public function __construct()
    {
        parent::__construct(
            'einsatzverwaltung_widget', // Base ID
            'Letzte Eins&auml;tze', // Name
            array(
                'description' => 'Zeigt die neuesten Eins&auml;tze an.',
                'customize_selective_refresh' => true,
            ) // Args
        );
    }

    /**
     * @param Options $options
     * @param Utilities $utilities
     * @param Formatter $formatter
     */
    public static function setDependencies($options, $utilities, $formatter)
    {
        self::$formatter = $formatter;
        self::$options = $options;
        self::$utilities = $utilities;
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance)
    {
        $title = apply_filters('widget_title', $instance['title']);

        // TODO mit wp_parse_args() vereinfachen
        $anzahl = self::$utilities->getArrayValueIfKey($instance, 'anzahl', 3);
        $zeigeDatum = self::$utilities->getArrayValueIfKey($instance, 'zeigeDatum', false);
        $zeigeZeit = self::$utilities->getArrayValueIfKey($instance, 'zeigeZeit', false);
        $zeigeFeedlink = self::$utilities->getArrayValueIfKey($instance, 'zeigeFeedlink', false);
        $zeigeOrt = self::$utilities->getArrayValueIfKey($instance, 'zeigeOrt', false);
        $zeigeArt = self::$utilities->getArrayValueIfKey($instance, 'zeigeArt', false);
        $zeigeArtHierarchie = self::$utilities->getArrayValueIfKey($instance, 'zeigeArtHierarchie', false);
        $showAnnotations = self::$utilities->getArrayValueIfKey($instance, 'showAnnotations', false);

        if (empty($title)) {
            $title = "Letzte Eins&auml;tze";
        }

        if (!isset($anzahl) || empty($anzahl) || !is_numeric($anzahl) || $anzahl < 1) {
            $anzahl = 3;
        }

        $letzteEinsaetze = "";
        $posts = get_posts(array(
            'post_type' => 'einsatz',
            'post_status' => 'publish',
            'posts_per_page' => $anzahl
        ));
        foreach ($posts as $post) {
            $report = new IncidentReport($post);
            $letzteEinsaetze .= '<li class="einsatzbericht">';

            if (true === $showAnnotations) {
                $core = Core::getInstance();
                $annotationIconBar = new AnnotationIconBar($core);
                $letzteEinsaetze .= '<div class="annotation-icon-bar">' . $annotationIconBar->render($report) . '</div>';
            }

            $letzteEinsaetze .= "<a href=\"".get_permalink($post->ID)."\" rel=\"bookmark\" class=\"einsatzmeldung\">";
            $meldung = get_the_title($post->ID);
            if (!empty($meldung)) {
                $letzteEinsaetze .= $meldung;
            } else {
                $letzteEinsaetze .= "(kein Titel)";
            }
            $letzteEinsaetze .= "</a>";

            if ($zeigeDatum) {
                $timestamp = strtotime($post->post_date);
                $datumsformat = self::$options->getDateFormat();
                $letzteEinsaetze .= "<br><span class=\"einsatzdatum\">".date_i18n($datumsformat, $timestamp)."</span>";
                if ($zeigeZeit) {
                    $zeitformat = self::$options->getTimeFormat();
                    $letzteEinsaetze .= " | <span class=\"einsatzzeit\">".date_i18n($zeitformat, $timestamp)." Uhr</span>";
                }
            }

            if ($zeigeArt) {
                $typeOfIncident = self::$formatter->getTypeOfIncident($report, false, false, $zeigeArtHierarchie);
                if (!empty($typeOfIncident)) {
                    $letzteEinsaetze .= sprintf('<br><span class="einsatzart">%s</span>', $typeOfIncident);
                }
            }

            if ($zeigeOrt) {
                $location = $report->getLocation();
                if (!empty($location)) {
                    $letzteEinsaetze .= "<br><span class=\"einsatzort\">Ort:&nbsp;".$location."</span>";
                }
            }

            $letzteEinsaetze .= "</li>";
        }

        echo $args['before_widget'];
        echo $args['before_title'] . $title . $args['after_title'];
        if (empty($letzteEinsaetze)) {
            echo '<p>Keine Eins&auml;tze</p>';
        } else {
            echo '<ul class="einsatzberichte">' . $letzteEinsaetze . '</ul>';
        }
        if ($zeigeFeedlink) {
            echo '<p class="einsatzfeed"><span class="fa fa-rss"></span>&nbsp;<a href="';
            echo get_post_type_archive_feed_link('einsatz');
            echo '">Einsatzberichte (Feed)</a></p>';
        }
        echo $args['after_widget'];
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $newInstance Values just sent to be saved.
     * @param array $oldInstance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
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

        $instance['zeigeDatum'] = self::$utilities->getArrayValueIfKey($newInstance, 'zeigeDatum', false);
        $instance['zeigeZeit'] = self::$utilities->getArrayValueIfKey($newInstance, 'zeigeZeit', false);
        $instance['zeigeOrt'] = self::$utilities->getArrayValueIfKey($newInstance, 'zeigeOrt', false);
        $instance['zeigeArt'] = self::$utilities->getArrayValueIfKey($newInstance, 'zeigeArt', false);
        $instance['zeigeArtHierarchie'] = self::$utilities->getArrayValueIfKey($newInstance, 'zeigeArtHierarchie', false);
        $instance['zeigeFeedlink'] = self::$utilities->getArrayValueIfKey($newInstance, 'zeigeFeedlink', false);
        $instance['showAnnotations'] = '1' === self::$utilities->getArrayValueIfKey($newInstance, 'showAnnotations', false);

        return $instance;
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     * @return string|void
     */
    public function form($instance)
    {
        $title = self::$utilities->getArrayValueIfKey($instance, 'title', 'Letzte Eins&auml;tze');
        $anzahl = self::$utilities->getArrayValueIfKey($instance, 'anzahl', 3);
        $zeigeDatum = self::$utilities->getArrayValueIfKey($instance, 'zeigeDatum', false);
        $zeigeZeit = self::$utilities->getArrayValueIfKey($instance, 'zeigeZeit', false);
        $zeigeFeedlink = self::$utilities->getArrayValueIfKey($instance, 'zeigeFeedlink', false);
        $zeigeOrt = self::$utilities->getArrayValueIfKey($instance, 'zeigeOrt', false);
        $zeigeArt = self::$utilities->getArrayValueIfKey($instance, 'zeigeArt', false);
        $zeigeArtHierarchie = self::$utilities->getArrayValueIfKey($instance, 'zeigeArtHierarchie', false);
        $showAnnotations = self::$utilities->getArrayValueIfKey($instance, 'showAnnotations', false);

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
    }
}
