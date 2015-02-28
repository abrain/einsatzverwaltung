<?php
namespace abrain\Einsatzverwaltung;

use WP_Widget;
use WP_Query;

/**
 * WordPress-Widget für die letzten X Einsätze
 *
 * @author Andreas Brain
 */
class WidgetLetzteEinsaetze extends WP_Widget
{

    /**
     * Register widget with WordPress.
     */
    public function __construct()
    {
        parent::__construct(
            'einsatzverwaltung_widget', // Base ID
            'Letzte Eins&auml;tze', // Name
            array('description' => __('Zeigt die neuesten Eins&auml;tze an', 'einsatzverwaltung'),) // Args
        );
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
        $anzahl = Utilities::getArrayValueIfKey($instance, 'anzahl', 3);
        $zeigeDatum = Utilities::getArrayValueIfKey($instance, 'zeigeDatum', false);
        $zeigeZeit = Utilities::getArrayValueIfKey($instance, 'zeigeZeit', false);
        $zeigeFeedlink = Utilities::getArrayValueIfKey($instance, 'zeigeFeedlink', false);
        $zeigeOrt = Utilities::getArrayValueIfKey($instance, 'zeigeOrt', false);
        $zeigeArt = Utilities::getArrayValueIfKey($instance, 'zeigeArt', false);
        $zeigeArtHierarchie = Utilities::getArrayValueIfKey($instance, 'zeigeArtHierarchie', false);

        if (empty($title)) {
            $title = "Letzte Eins&auml;tze";
        }

        if (!isset($anzahl) || empty ($anzahl) || !is_numeric($anzahl) || $anzahl < 1) {
            $anzahl = 3;
        }

        $letzteEinsaetze = "";
        $query = new WP_Query('&post_type=einsatz&post_status=publish&posts_per_page='.$anzahl);
        while ($query->have_posts()) {
            $nextPost = $query->next_post();
            $letzteEinsaetze .= '<li class="einsatzbericht">';

            $letzteEinsaetze .= "<a href=\"".get_permalink($nextPost->ID)."\" rel=\"bookmark\" class=\"einsatzmeldung\">";
            $meldung = get_the_title($nextPost->ID);
            if (!empty($meldung)) {
                $letzteEinsaetze .= $meldung;
            } else {
                $letzteEinsaetze .= "(kein Titel)";
            }
            $letzteEinsaetze .= "</a>";

            if ($zeigeDatum) {
                $timestamp = strtotime($nextPost->post_date);
                $datumsformat = Options::getDateFormat();
                $letzteEinsaetze .= "<br><span class=\"einsatzdatum\">".date_i18n($datumsformat, $timestamp)."</span>";
                if ($zeigeZeit) {
                    $zeitformat = Options::getTimeFormat();
                    $letzteEinsaetze .= " | <span class=\"einsatzzeit\">".date_i18n($zeitformat, $timestamp)." Uhr</span>";
                }
            }

            if ($zeigeArt) {
                $einsatzart = Data::getEinsatzart($nextPost->ID);
                if ($einsatzart !== false) {
                    $einsatzart_str = Frontend::getEinsatzartString($einsatzart, false, false, $zeigeArtHierarchie);
                    $letzteEinsaetze .= sprintf('<br><span class="einsatzart">%s</span>', $einsatzart_str);
                }
            }

            if ($zeigeOrt) {
                $einsatzort = Data::getEinsatzort($nextPost->ID);
                if ($einsatzort != "") {
                    $letzteEinsaetze .= "<br><span class=\"einsatzort\">Ort:&nbsp;".$einsatzort."</span>";
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
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = strip_tags($new_instance['title']);

        $anzahl = $new_instance['anzahl'];
        if (empty ($anzahl) || !is_numeric($anzahl) || $anzahl < 1) {
            $instance['anzahl'] = $old_instance['anzahl'];
        } else {
            $instance['anzahl'] = $new_instance['anzahl'];
        }

        $instance['zeigeDatum'] = Utilities::getArrayValueIfKey($new_instance, 'zeigeDatum', false);
        $instance['zeigeZeit'] = Utilities::getArrayValueIfKey($new_instance, 'zeigeZeit', false);
        $instance['zeigeOrt'] = Utilities::getArrayValueIfKey($new_instance, 'zeigeOrt', false);
        $instance['zeigeArt'] = Utilities::getArrayValueIfKey($new_instance, 'zeigeArt', false);
        $instance['zeigeArtHierarchie'] = Utilities::getArrayValueIfKey($new_instance, 'zeigeArtHierarchie', false);
        $instance['zeigeFeedlink'] = Utilities::getArrayValueIfKey($new_instance, 'zeigeFeedlink', false);

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
        $title = Utilities::getArrayValueIfKey($instance, 'title', __('Letzte Eins&auml;tze', 'einsatzverwaltung'));
        $anzahl = Utilities::getArrayValueIfKey($instance, 'anzahl', 3);
        $zeigeDatum = Utilities::getArrayValueIfKey($instance, 'zeigeDatum', false);
        $zeigeZeit = Utilities::getArrayValueIfKey($instance, 'zeigeZeit', false);
        $zeigeFeedlink = Utilities::getArrayValueIfKey($instance, 'zeigeFeedlink', false);
        $zeigeOrt = Utilities::getArrayValueIfKey($instance, 'zeigeOrt', false);
        $zeigeArt = Utilities::getArrayValueIfKey($instance, 'zeigeArt', false);
        $zeigeArtHierarchie = Utilities::getArrayValueIfKey($instance, 'zeigeArtHierarchie', false);

        echo '<p><label for="'.$this->get_field_id('title').'">' . __('Titel:', 'einsatzverwaltung') . '</label>';
        echo '<input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . esc_attr($title).'" /></p>';

        echo '<p><label for="'.$this->get_field_id('anzahl').'">' . __('Anzahl der Eins&auml;tze, die angezeigt werden:', 'einsatzverwaltung') . '</label>&nbsp;';
        echo '<input id="'.$this->get_field_id('anzahl').'" name="'.$this->get_field_name('anzahl').'" type="text" value="'.$anzahl.'" size="3" /></p>';

        echo '<p><input id="'.$this->get_field_id('zeigeFeedlink').'" name="'.$this->get_field_name('zeigeFeedlink').'" type="checkbox" '.($zeigeFeedlink ? 'checked="checked" ' : '').'/>';
        echo '&nbsp;<label for="'.$this->get_field_id('zeigeFeedlink').'">' . __('Link zum Feed anzeigen', 'einsatzverwaltung') . '</label></p>';

        echo '<p><strong>Einsatzdaten:</strong></p>';

        echo '<p><input id="'.$this->get_field_id('zeigeDatum').'" name="'.$this->get_field_name('zeigeDatum').'" type="checkbox" '.($zeigeDatum ? 'checked="checked" ' : '').'/>';
        echo '&nbsp;<label for="'.$this->get_field_id('zeigeDatum').'">' . __('Datum anzeigen', 'einsatzverwaltung') . '</label></p>';

        echo '<p style="text-indent:1em;"><input id="'.$this->get_field_id('zeigeZeit').'" name="'.$this->get_field_name('zeigeZeit').'" type="checkbox" '.($zeigeZeit ? 'checked="checked" ' : '').'/>';
        echo '&nbsp;<label for="'.$this->get_field_id('zeigeZeit').'">' . __('Zeit anzeigen (nur in Kombination mit Datum)', 'einsatzverwaltung') . '</label></p>';

        echo '<p><input id="'.$this->get_field_id('zeigeArt').'" name="'.$this->get_field_name('zeigeArt').'" type="checkbox" '.($zeigeArt ? 'checked="checked" ' : '').'/>';
        echo '&nbsp;<label for="'.$this->get_field_id('zeigeArt').'">' . __('Einsatzart anzeigen', 'einsatzverwaltung') . '</label></p>';

        echo '<p style="text-indent:1em;"><input id="'.$this->get_field_id('zeigeArtHierarchie').'" name="'.$this->get_field_name('zeigeArtHierarchie').'" type="checkbox" '.($zeigeArtHierarchie ? 'checked="checked" ' : '').'/>';
        echo '&nbsp;<label for="'.$this->get_field_id('zeigeArtHierarchie').'">' . __('Hierarchie der Einsatzart anzeigen', 'einsatzverwaltung') . '</label></p>';

        echo '<p><input id="'.$this->get_field_id('zeigeOrt').'" name="'.$this->get_field_name('zeigeOrt').'" type="checkbox" '.($zeigeOrt ? 'checked="checked" ' : '').'/>';
        echo '&nbsp;<label for="'.$this->get_field_id('zeigeOrt').'">' . __('Ort anzeigen', 'einsatzverwaltung') . '</label></p>';
    }
}

// Widget in WordPress registrieren
add_action('widgets_init', function() {
    register_widget('abrain\Einsatzverwaltung\WidgetLetzteEinsaetze');
});
