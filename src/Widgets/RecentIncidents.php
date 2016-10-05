<?php
namespace abrain\Einsatzverwaltung\Widgets;

use abrain\Einsatzverwaltung\Frontend;
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
     */
    public static function setDependencies($options, $utilities)
    {
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
        $formatter = new Formatter(self::$options, self::$utilities);

        $title = apply_filters('widget_title', $instance['title']);
        $anzahl = self::$utilities->getArrayValueIfKey($instance, 'anzahl', 3);
        $zeigeDatum = self::$utilities->getArrayValueIfKey($instance, 'zeigeDatum', false);
        $zeigeZeit = self::$utilities->getArrayValueIfKey($instance, 'zeigeZeit', false);
        $zeigeFeedlink = self::$utilities->getArrayValueIfKey($instance, 'zeigeFeedlink', false);
        $zeigeOrt = self::$utilities->getArrayValueIfKey($instance, 'zeigeOrt', false);
        $zeigeArt = self::$utilities->getArrayValueIfKey($instance, 'zeigeArt', false);
        $zeigeArtHierarchie = self::$utilities->getArrayValueIfKey($instance, 'zeigeArtHierarchie', false);

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
                $typeOfIncident = $formatter->getTypeOfIncident($report, false, false, $zeigeArtHierarchie);
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

        echo '<p><label for="'.$this->get_field_id('title').'">' . 'Titel:' . '</label>';
        echo '<input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . esc_attr($title).'" /></p>';

        echo '<p><label for="'.$this->get_field_id('anzahl').'">' . 'Anzahl der Einsatzberichte, die angezeigt werden:' . '</label>&nbsp;';
        echo '<input id="'.$this->get_field_id('anzahl').'" name="'.$this->get_field_name('anzahl').'" type="text" value="'.$anzahl.'" size="3" /></p>';

        echo '<p><input id="'.$this->get_field_id('zeigeFeedlink').'" name="'.$this->get_field_name('zeigeFeedlink').'" type="checkbox" '.($zeigeFeedlink ? 'checked="checked" ' : '').'/>';
        echo '&nbsp;<label for="'.$this->get_field_id('zeigeFeedlink').'">' . 'Link zum Feed anzeigen' . '</label></p>';

        echo '<p><strong>Einsatzdaten:</strong></p>';

        echo '<p><input id="'.$this->get_field_id('zeigeDatum').'" name="'.$this->get_field_name('zeigeDatum').'" type="checkbox" '.($zeigeDatum ? 'checked="checked" ' : '').'/>';
        echo '&nbsp;<label for="'.$this->get_field_id('zeigeDatum').'">' . 'Datum anzeigen' . '</label></p>';

        echo '<p style="text-indent:1em;"><input id="'.$this->get_field_id('zeigeZeit').'" name="'.$this->get_field_name('zeigeZeit').'" type="checkbox" '.($zeigeZeit ? 'checked="checked" ' : '').'/>';
        echo '&nbsp;<label for="'.$this->get_field_id('zeigeZeit').'">' . 'Zeit anzeigen (nur in Kombination mit Datum)' . '</label></p>';

        echo '<p><input id="'.$this->get_field_id('zeigeArt').'" name="'.$this->get_field_name('zeigeArt').'" type="checkbox" '.($zeigeArt ? 'checked="checked" ' : '').'/>';
        echo '&nbsp;<label for="'.$this->get_field_id('zeigeArt').'">' . 'Einsatzart anzeigen' . '</label></p>';

        echo '<p style="text-indent:1em;"><input id="'.$this->get_field_id('zeigeArtHierarchie').'" name="'.$this->get_field_name('zeigeArtHierarchie').'" type="checkbox" '.($zeigeArtHierarchie ? 'checked="checked" ' : '').'/>';
        echo '&nbsp;<label for="'.$this->get_field_id('zeigeArtHierarchie').'">' . 'Hierarchie der Einsatzart anzeigen' . '</label></p>';

        echo '<p><input id="'.$this->get_field_id('zeigeOrt').'" name="'.$this->get_field_name('zeigeOrt').'" type="checkbox" '.($zeigeOrt ? 'checked="checked" ' : '').'/>';
        echo '&nbsp;<label for="'.$this->get_field_id('zeigeOrt').'">' . 'Ort anzeigen' . '</label></p>';
    }
}
