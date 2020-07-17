<?php
namespace abrain\Einsatzverwaltung\Widgets;

use abrain\Einsatzverwaltung\ReportQuery;
use abrain\Einsatzverwaltung\Types\Unit;
use abrain\Einsatzverwaltung\Util\Formatter;

/**
 * Widget für die neuesten Einsätze, das Aussehen wird vom Benutzer per HTML-Templates bestimmt
 *
 * @author Andreas Brain
 */
class RecentIncidentsFormatted extends AbstractWidget
{
    /**
     * @var Formatter
     */
    private $formatter;

    private $allowedHtmlTags = array(
        'a' => array(
            'href' => true,
            'rel' => true,
            'rev' => true,
            'name' => true,
            'style' => true,
            'target' => true,
        ),
        'abbr' => array(),
        'acronym' => array(),
        'b' => array(),
        'br' => array(),
        'div' => array(
            'align' => true,
            'class' => true,
            'dir' => true,
            'lang' => true,
            'style' => true,
            'xml:lang' => true,
        ),
        'em' => array(),
        'figure' => array(
            'align' => true,
            'dir' => true,
            'lang' => true,
            'xml:lang' => true,
        ),
        'figcaption' => array(
            'align' => true,
            'dir' => true,
            'lang' => true,
            'xml:lang' => true,
        ),
        'h3' => array(
            'align' => true,
            'style' => true
        ),
        'h4' => array(
            'align' => true,
            'style' => true
        ),
        'h5' => array(
            'align' => true,
            'style' => true
        ),
        'h6' => array(
            'align' => true,
            'style' => true
        ),
        'hr' => array(
            'align' => true,
            'noshade' => true,
            'size' => true,
            'style' => true,
            'width' => true,
        ),
        'i' => array(
            'class' => true,
            'title'=> true,
            'style' => true
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
            'style' => true,
            'width' => true,
        ),
        'li' => array(
            'align' => true,
            'class' => true,
            'style' => true,
            'value' => true,
        ),
        'p' => array(
            'align' => true,
            'class' => true,
            'dir' => true,
            'lang' => true,
            'style' => true,
            'xml:lang' => true,
        ),
        'small' => array(),
        'span' => array(
            'dir' => true,
            'align' => true,
            'class' => true,
            'lang' => true,
            'style' => true,
            'xml:lang' => true,
        ),
        'strong' => array(),
        'u' => array(),
        'ul' => array(
            'class' => true,
            'style' => true,
            'type' => true,
        ),
        'ol' => array(
            'class' => true,
            'start' => true,
            'style' => true,
            'type' => true,
        ),
    );
    private $defaults = array(
        'title' => '',
        'numIncidents' => 3,
        'units' => array(),
        'beforeContent' => '',
        'pattern' => '',
        'afterContent' => ''
    );
    private $allowedTagsPattern = array('%title%', '%date%', '%time%', '%endTime%', '%location%', '%duration%',
        '%incidentCommander%', '%incidentType%', '%incidentTypeHierarchical%', '%incidentTypeColor%', '%url%',
        '%number%', '%seqNum%', '%annotations%', '%vehicles%', '%units%', '%additionalForces%', '%typesOfAlerting%',
        '%featuredImage%', '%workforce%');
    private $allowedTagsAfter = array('%feedUrl%', '%yearArchive%');

    /**
     * Konstruktor, generiert und registriert das Widget
     * @param Formatter $formatter
     */
    public function __construct(Formatter $formatter)
    {
        parent::__construct(
            'recent-incidents-formatted',
            'Letzte Eins&auml;tze (eigenes Format)',
            array(
                'description' => 'Zeigt die neuesten Eins&auml;tze an. Das Aussehen kann vollst&auml;ndig mit eigenem HTML bestimmt werden.',
                'customize_selective_refresh' => true,
            )
        );
        $this->formatter = $formatter;
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
            $settings['title'] = 'Letzte Eins&auml;tze';
        }

        if (empty($settings['numIncidents'])) {
            $settings['numIncidents'] = $this->defaults['numIncidents'];
        }

        echo $args['before_widget'];
        echo $args['before_title'] . apply_filters('widget_title', $settings['title']) . $args['after_title'];

        $reportQuery = new ReportQuery();
        $reportQuery->setOrderAsc(false);
        $reportQuery->setLimit($settings['numIncidents']);
        $reportQuery->setUnits($settings['units']);
        $reports = $reportQuery->getReports();

        $widgetContent = $settings['beforeContent'];
        foreach ($reports as $report) {
            $post = get_post($report->getPostId()); // FIXME converting back and forth between WP_Post and IncidenReport
            $widgetContent .= $this->formatter->formatIncidentData($settings['pattern'], $this->allowedTagsPattern, $post, 'widget');
        }
        $widgetContent .= $this->formatter->formatIncidentData($settings['afterContent'], $this->allowedTagsAfter, null, 'widget');

        $widgetContent = do_shortcode($widgetContent);

        echo wp_kses($widgetContent, $this->allowedHtmlTags);
        echo $args['after_widget'];
    }

    /**
     * Eine bestimmte Instanz des Widgets aktualisieren
     *
     * @param array $newInstance Die neuen Einstellungen
     * @param array $oldInstance Die bisherigen Einstellungen
     *
     * @return array Die zu speichernden Einstellungen oder false um das Speichern abzubrechen
     */
    public function update($newInstance, $oldInstance)
    {
        $instance = array();
        $instance['title'] = strip_tags($newInstance['title']);
        $instance['numIncidents'] = absint($newInstance['numIncidents']);
        if ($instance['numIncidents'] === 0) {
            $instance['numIncidents'] = $this->defaults['numIncidents'];
        }
        if (array_key_exists('units', $newInstance)) {
            $instance['units'] = array_filter($newInstance['units'], 'is_numeric');
        } else {
            $instance['units'] = array();
        }
        $instance['beforeContent'] = wp_kses($newInstance['beforeContent'], $this->allowedHtmlTags);
        $instance['pattern'] = wp_kses($newInstance['pattern'], $this->allowedHtmlTags);
        $instance['afterContent'] = wp_kses($newInstance['afterContent'], $this->allowedHtmlTags);

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
        $values = wp_parse_args($instance, $this->defaults);

        echo '<p>';
        printf(
            '<label for="%1$s">%2$s</label><input class="widefat" id="%1$s" name="%3$s" type="text" value="%4$s" />',
            $this->get_field_id('title'),
            'Titel:',
            $this->get_field_name('title'),
            esc_attr($values['title'])
        );
        echo '</p>';

        echo '<p>';
        printf(
            '<label for="%1$s">%2$s</label>&nbsp;<input id="%1$s" name="%3$s" type="text" value="%4$s" size="3" />',
            $this->get_field_id('numIncidents'),
            'Anzahl der Einsatzberichte, die angezeigt werden:',
            $this->get_field_name('numIncidents'),
            esc_attr($values['numIncidents'])
        );
        echo '</p>';

        $this->echoChecklistBox(
            get_post_type_object(Unit::getSlug()),
            'units',
            __('Only show reports for these units:', 'einsatzverwaltung'),
            $values['units'],
            __('Select no unit to show all reports', 'einsatzverwaltung')
        );

        echo '<p>';
        printf(
            '<label for="%1$s">%2$s</label><textarea class="widefat" id="%1$s" name="%3$s">%4$s</textarea>',
            $this->get_field_id('beforeContent'),
            'HTML-Code vor den Einsatzberichten:',
            $this->get_field_name('beforeContent'),
            esc_textarea($values['beforeContent'])
        );
        echo '</p>';

        echo '<p>';
        printf(
            '<label for="%1$s">%2$s</label><textarea class="widefat" id="%1$s" name="%3$s">%4$s</textarea>',
            $this->get_field_id('pattern'),
            'HTML-Template pro Einsatzbericht:',
            $this->get_field_name('pattern'),
            esc_textarea($values['pattern'])
        );
        $this->printTagReplacementInfo($this->allowedTagsPattern);
        echo '</p>';

        echo '<p>';
        printf(
            '<label for="%1$s">%2$s</label><textarea class="widefat" id="%1$s" name="%3$s">%4$s</textarea>',
            $this->get_field_id('afterContent'),
            'HTML-Code nach den Einsatzberichten:',
            $this->get_field_name('afterContent'),
            esc_textarea($values['afterContent'])
        );
        $this->printTagReplacementInfo($this->allowedTagsAfter);
        echo '</p>';

        return '';
    }

    /**
     * @param $allowedTags
     */
    private function printTagReplacementInfo($allowedTags)
    {
        echo '<br><small>';
        _e('The following tags will be replaced:', 'einsatzverwaltung');
        foreach ($allowedTags as $tag) {
            printf('<br><strong>%s</strong> (%s)', esc_html($tag), esc_html($this->formatter->getLabelForTag($tag)));
        }
        echo '</small>';
    }
}
