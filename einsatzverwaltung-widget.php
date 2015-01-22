<?php

class Einsatzverwaltung_Widget extends WP_Widget
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
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
        $anzahl = $instance['anzahl'];
        $zeigeDatum = $instance['zeigeDatum'];
        $zeigeZeit = $instance['zeigeZeit'];
        $zeigeFeedlink = (array_key_exists('zeigeFeedlink', $instance) ? $instance['zeigeFeedlink'] : null);
        $zeigeOrt = (array_key_exists('zeigeOrt', $instance) ? $instance['zeigeOrt'] : null);
        $zeigeArt = (array_key_exists('zeigeArt', $instance) ? $instance['zeigeArt'] : null);
        $zeigeArtHierarchie = (array_key_exists('zeigeArtHierarchie', $instance) ? $instance['zeigeArtHierarchie'] : null);
        
        if (empty($title)) {
            $title = "Letzte Eins&auml;tze";
        }
        
        if (!isset($anzahl) || empty ($anzahl) || !is_numeric($anzahl) || $anzahl < 1) {
            $anzahl = 3;
        }

        $letzteEinsaetze = "";
        $query = new WP_Query('&post_type=einsatz&post_status=publish&posts_per_page='.$anzahl);
        while ($query->have_posts()) {
            $p = $query->next_post();
            $letzteEinsaetze .= "<li>";
            
            $letzteEinsaetze .= "<a href=\"".get_permalink($p->ID)."\" rel=\"bookmark\" class=\"einsatzmeldung\">";
            $meldung = get_the_title($p->ID);
            if (!empty($meldung)) {
                $letzteEinsaetze .= $meldung;
            } else {
                $letzteEinsaetze .= "(kein Titel)";
            }
            $letzteEinsaetze .= "</a>";
            
            if ($zeigeDatum) {
                $timestamp = strtotime($p->post_date);
                $datumsformat = get_option('date_format', 'd.m.Y');
                $letzteEinsaetze .= "<br><span class=\"einsatzdatum\">".date_i18n($datumsformat, $timestamp)."</span>";
                if ($zeigeZeit) {
                    $zeitformat = get_option('time_format', 'H:i');
                    $letzteEinsaetze .= " | <span class=\"einsatzzeit\">".date_i18n($zeitformat, $timestamp)." Uhr</span>";
                }
            }
            
            if ($zeigeArt) {
                $einsatzart = einsatzverwaltung_get_einsatzart($p->ID);
                if ($einsatzart) {
                    $einsatzart_str = $zeigeArtHierarchie ? einsatzverwaltung_get_einsatzart_string($einsatzart, false, false) : $einsatzart->name;
                    $letzteEinsaetze .= sprintf('<br><span class="einsatzart">%s</span>', $einsatzart_str);
                }
            }
            
            if ($zeigeOrt) {
                $einsatzort = get_post_meta($p->ID, $key = 'einsatz_einsatzort', $single = true);
                if ($einsatzort != "") {
                    $letzteEinsaetze .= "<br><span class=\"einsatzort\">Ort:&nbsp;".$einsatzort."</span>";
                }
            }
            
            $letzteEinsaetze .= "</li>";
        }

        echo $before_widget;
        echo $before_title . $title . $after_title;
        echo (empty($letzteEinsaetze) ? "Keine Eins&auml;tze" : "<ul>".$letzteEinsaetze."</ul>");
        echo ($zeigeFeedlink ? '<p><span class="fa fa-rss"></span>&nbsp;<a href="'.get_post_type_archive_feed_link('einsatz', $feed = '').'">Einsatzberichte (Feed)</a></p>' : '');
        echo $after_widget;
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
        
        $instance['zeigeDatum'] = $new_instance['zeigeDatum'];
        $instance['zeigeZeit'] = $new_instance['zeigeZeit'];
        $instance['zeigeOrt'] = $new_instance['zeigeOrt'];
        $instance['zeigeArt'] = $new_instance['zeigeArt'];
        $instance['zeigeArtHierarchie'] = $new_instance['zeigeArtHierarchie'];
        $instance['zeigeFeedlink'] = $new_instance['zeigeFeedlink'];

        return $instance;
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form($instance)
    {
        if (isset($instance[ 'title' ])) {
            $title = $instance[ 'title' ];
        } else {
            $title = __('Letzte Eins&auml;tze', 'einsatzverwaltung');
        }
        
        if (isset($instance[ 'anzahl' ])) {
            $anzahl = $instance[ 'anzahl' ];
        } else {
            $anzahl = 3;
        }
        
        $zeigeDatum = (array_key_exists('zeigeDatum', $instance) ? $instance['zeigeDatum'] : null);
        $zeigeZeit = (array_key_exists('zeigeZeit', $instance) ? $instance['zeigeZeit'] : null);
        $zeigeFeedlink = (array_key_exists('zeigeFeedlink', $instance) ? $instance['zeigeFeedlink'] : null);
        $zeigeOrt = (array_key_exists('zeigeOrt', $instance) ? $instance['zeigeOrt'] : null);
        $zeigeArt = (array_key_exists('zeigeArt', $instance) ? $instance['zeigeArt'] : null);
        $zeigeArtHierarchie = (array_key_exists('zeigeArtHierarchie', $instance) ? $instance['zeigeArtHierarchie'] : null);
        
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

// register Einsatz_Widget widget
add_action('widgets_init', create_function('', 'register_widget("einsatzverwaltung_widget");'));
