<?php
namespace abrain\Einsatzverwaltung\Widgets;

use abrain\Einsatzverwaltung\Util\Formatter;
use abrain\Einsatzverwaltung\Utilities;
use WP_Widget;

/**
 * Widget für die neuesten Einsätze, das Aussehen wird vom Benutzer per HTML-Templates bestimmt
 *
 * @author Andreas Brain
 */
class RecentIncidentsFormatted extends WP_Widget
{
    private $allowedHtmlTags = array(
        'a' => array(
            'href' => true,
            'rel' => true,
            'rev' => true,
            'name' => true,
            'target' => true,
        ),
        'abbr' => array(),
        'acronym' => array(),
        'br' => array(),
        'div' => array(
            'align' => true,
            'class' => true,
            'dir' => true,
            'lang' => true,
            'xml:lang' => true,
        ),
        'h3' => array(
            'align' => true,
        ),
        'h4' => array(
            'align' => true,
        ),
        'h5' => array(
            'align' => true,
        ),
        'h6' => array(
            'align' => true,
        ),
        'hr' => array(
            'align' => true,
            'noshade' => true,
            'size' => true,
            'width' => true,
        ),
        'img' => array(
            'alt' => true,
            'align' => true,
            'border' => true,
            'class' => true,
            'height' => true,
            'hspace' => true,
            'longdesc' => true,
            'vspace' => true,
            'src' => true,
            'width' => true,
        ),
        'li' => array(
            'align' => true,
            'class' => true,
            'value' => true,
        ),
        'p' => array(
            'align' => true,
            'class' => true,
            'dir' => true,
            'lang' => true,
            'xml:lang' => true,
        ),
        'span' => array(
            'dir' => true,
            'align' => true,
            'class' => true,
            'lang' => true,
            'xml:lang' => true,
        ),
        'ul' => array(
            'class' => true,
            'type' => true,
        ),
        'ol' => array(
            'class' => true,
            'start' => true,
            'type' => true,
        ),
    );
    private $defaults = array(
        'title' => '',
        'numIncidents' => 3,
        'beforeContent' => '',
        'pattern' => '',
        'afterContent' => ''
    );
    private $allowedTagsPattern = array('%title%', '%date%', '%time%', '%location%', '%duration%', '%incidentType%',
        '%url%');
    private $allowedTagsAfter = array('%feedUrl%');

    /**
     * Konstruktor, generiert und registriert das Widget
     */
    public function __construct()
    {
        parent::__construct(
            'recent-incidents-formatted',
            'Letzte Eins&auml;tze (eigenes Format)',
            array(
                'description' => __('Zeigt die neuesten Eins&auml;tze an.', 'einsatzverwaltung') . ' ' .
                    __('Das Aussehen kann vollst&auml;ndig mit eigenem HTML bestimmt werden.', 'einsatzverwaltung')
            )
        );

        // Widget in WordPress registrieren
        add_action('widgets_init', function () {
            register_widget('abrain\Einsatzverwaltung\Widgets\RecentIncidentsFormatted');
        });
    }

    /**
     * Die Ausgabe des Widgetinhalts
     *
     * @param array $args     Anzeigeargumente, u.a. before_title, after_title, before_widget und after_widget.
     * @param array $instance Die Einstellungen der betreffenden Instanz des Widgets.
     */
    public function widget($args, $instance)
    {
        $settings = wp_parse_args($instance, $this->defaults);

        if (empty($settings['title'])) {
            $settings['title'] = __('Letzte Eins&auml;tze', 'einsatzverwaltung');
        }

        if (empty($settings['numIncidents'])) {
            $settings['numIncidents'] = $this->defaults['numIncidents'];
        }

        echo $args['before_widget'];
        echo $args['before_title'] . apply_filters('widget_title', $settings['title']) . $args['after_title'];

        $incidents = get_posts(array(
            'post_type' => 'einsatz',
            'post_status' => 'publish',
            'posts_per_page' => $settings['numIncidents']
        ));

        $widgetContent = $settings['beforeContent'];
        foreach ($incidents as $incident) {
            $widgetContent .= Formatter::formatIncidentData($incident, $settings['pattern'], $this->allowedTagsPattern);
        }
        $widgetContent .= Formatter::formatIncidentData(null, $settings['afterContent'], $this->allowedTagsAfter);

        echo wp_kses($widgetContent, $this->allowedHtmlTags);
        echo $args['after_widget'];
    }

    /**
     * Eine bestimmte Instanz des Widgets aktualisieren
     *
     * @param array $new_instance Die neuen Einstellungen
     * @param array $old_instance Die bisherigen Einstellungen
     *
     * @return array Die zu speichernden Einstellungen oder false um das Speichern abzubrechen
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['numIncidents'] = Utilities::sanitizeNumberGreaterZero($new_instance['numIncidents'],
            $this->defaults['numIncidents']);
        $instance['beforeContent'] = wp_kses($new_instance['beforeContent'], $this->allowedHtmlTags);
        $instance['pattern'] = wp_kses($new_instance['pattern'], $this->allowedHtmlTags);
        $instance['afterContent'] = wp_kses($new_instance['afterContent'], $this->allowedHtmlTags);

        return $instance;
    }

    /**
     * Gibt das Formular für die Einstellungen aus.
     *
     * @param array $instance Derzeitige Einstellungen.
     *
     * @return string HTML-Code für das Formular
     */
    public function form($instance)
    {
        echo '<p>';
        printf('<label for="%1$s">%2$s</label><input class="widefat" id="%1$s" name="%3$s" type="text" value="%4$s" />',
            $this->get_field_id('title'),
            __('Titel:', 'einsatzverwaltung'),
            $this->get_field_name('title'),
            esc_attr(Utilities::getArrayValueIfKey($instance, 'title', '')));
        echo '</p>';

        $numIncidents = Utilities::getArrayValueIfKey($instance, 'numIncidents', $this->defaults['numIncidents']);
        echo '<p>';
        printf('<label for="%1$s">%2$s</label>&nbsp;<input id="%1$s" name="%3$s" type="text" value="%4$s" size="3" />',
            $this->get_field_id('numIncidents'),
            __('Anzahl der Einsatzberichte, die angezeigt werden:', 'einsatzverwaltung'),
            $this->get_field_name('numIncidents'),
            empty($numIncidents) ? $this->defaults['numIncidents'] : esc_attr($numIncidents));
        echo '</p>';

        echo '<p>';
        printf('<label for="%1$s">%2$s</label><textarea class="widefat" id="%1$s" name="%3$s">%4$s</textarea>',
            $this->get_field_id('beforeContent'),
            __('HTML-Code vor den Einsatzberichten:', 'einsatzverwaltung'),
            $this->get_field_name('beforeContent'),
            Utilities::getArrayValueIfKey($instance, 'beforeContent', ''));
        echo '</p>';

        echo '<p>';
        printf('<label for="%1$s">%2$s</label><textarea class="widefat" id="%1$s" name="%3$s">%4$s</textarea>',
            $this->get_field_id('pattern'),
            __('HTML-Template pro Einsatzbericht:', 'einsatzverwaltung'),
            $this->get_field_name('pattern'),
            Utilities::getArrayValueIfKey($instance, 'pattern', ''));
        echo '</p><p class="description">' . __('Folgende Tags werden ersetzt:', 'einsatzverwaltung');
        $formatterTags = Formatter::getTags();
        foreach ($this->allowedTagsPattern as $tag) {
            echo '<br>' . $tag . ' (' . $formatterTags[$tag] . ')';
        }
        echo '</p>';

        echo '<p>';
        printf('<label for="%1$s">%2$s</label><textarea class="widefat" id="%1$s" name="%3$s">%4$s</textarea>',
            $this->get_field_id('afterContent'),
            __('HTML-Code nach den Einsatzberichten:', 'einsatzverwaltung'),
            $this->get_field_name('afterContent'),
            Utilities::getArrayValueIfKey($instance, 'afterContent', ''));
        echo '</p><p class="description">' . __('Folgende Tags werden ersetzt:', 'einsatzverwaltung');
        foreach ($this->allowedTagsAfter as $tag) {
            echo '<br>' . $tag . ' (' . $formatterTags[$tag] . ')';
        }
        echo '</p>';
    }
}