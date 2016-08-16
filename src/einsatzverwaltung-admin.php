<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use WP_Post;

/**
 * Regelt die Darstellung im Administrationsbereich
 */
class Admin
{
    /**
     * @var Core
     */
    private $core;

    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * Constructor
     *
     * @param Core $core
     * @param Utilities $utilities
     */
    public function __construct($core, $utilities)
    {
        $this->core = $core;
        $this->utilities = $utilities;
        $this->addHooks();
    }

    private function addHooks()
    {
        add_action('add_meta_boxes_einsatz', array($this, 'addMetaBoxes'));
        add_action('admin_menu', array($this, 'adjustTaxonomies'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueEditScripts'));
        add_filter('manage_edit-einsatz_columns', array($this, 'filterColumnsEinsatz'));
        add_action('manage_einsatz_posts_custom_column', array($this, 'filterColumnContentEinsatz'), 10, 2);
        add_action('dashboard_glance_items', array($this, 'addEinsatzberichteToDashboard')); // since WP 3.8
        add_action('right_now_content_table_end', array($this, 'addEinsatzberichteToDashboardLegacy')); // before WP 3.8
        add_filter('plugin_row_meta', array($this, 'pluginMetaLinks'), 10, 2);
        add_filter('plugin_action_links_' . $this->core->pluginBasename, array($this,'addActionLinks'));
    }

    /**
     * Fügt die Metabox zum Bearbeiten der Einsatzdetails ein
     */
    public function addMetaBoxes()
    {
        add_meta_box(
            'einsatzverwaltung_meta_box',
            'Einsatzdetails',
            array($this, 'displayMetaBoxEinsatzdetails'),
            'einsatz',
            'normal',
            'high'
        );
        add_meta_box(
            'einsatzverwaltung_meta_annotations',
            'Vermerke',
            array($this, 'displayMetaBoxAnnotations'),
            'einsatz',
            'side'
        );
    }

    /**
     * Nimmt Anpassungen in Bezug auf Taxonomien vor
     */
    public function adjustTaxonomies()
    {
        // Kategorieauswahl beim Bearbeiten von Einsatzberichten entfernen
        remove_meta_box('categorydiv', 'einsatz', 'side');

        // Kategorien als Untermenüpunkt von Einsatzberichten verstecken
        remove_submenu_page(
            'edit.php?post_type=einsatz',
            'edit-tags.php?taxonomy=category&amp;post_type=einsatz'
        );
    }

    /**
     * Zusätzliche Skripte im Admin-Bereich einbinden
     *
     * @param string $hook Name der aufgerufenen Datei
     */
    public function enqueueEditScripts($hook)
    {
        if ('post.php' == $hook || 'post-new.php' == $hook) {
            // Nur auf der Bearbeitungsseite anzeigen
            wp_enqueue_script(
                'einsatzverwaltung-edit-script',
                $this->core->scriptUrl . 'einsatzverwaltung-edit.js',
                array('jquery', 'jquery-ui-autocomplete'),
                Core::VERSION
            );
            wp_enqueue_style(
                'einsatzverwaltung-edit',
                $this->core->styleUrl . 'style-edit.css',
                array(),
                Core::VERSION
            );
        } elseif ('settings_page_einsatzvw-settings' == $hook) {
            wp_enqueue_script(
                'einsatzverwaltung-settings-script',
                $this->core->scriptUrl . 'einsatzverwaltung-settings.js',
                array('jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'iris'),
                Core::VERSION
            );
        }

        wp_enqueue_style(
            'font-awesome',
            $this->core->pluginUrl . 'font-awesome/css/font-awesome.min.css',
            false,
            '4.4.0'
        );
        wp_enqueue_style(
            'einsatzverwaltung-admin',
            $this->core->styleUrl . 'style-admin.css',
            array(),
            Core::VERSION
        );
    }

    /**
     * Inhalt der Metabox für Vermerke zum Einsatzbericht
     *
     * @param WP_Post $post Das Post-Objekt des aktuell bearbeiteten Einsatzberichts
     */
    public function displayMetaBoxAnnotations($post)
    {
        $report = new IncidentReport($post);

        $this->echoInputCheckbox(
            __("Fehlalarm", 'einsatzverwaltung'),
            'einsatzverwaltung_fehlalarm',
            $report->isFalseAlarm()
        );
        echo '<br>';

        $this->echoInputCheckbox(
            __("Besonderer Einsatz", 'einsatzverwaltung'),
            'einsatzverwaltung_special',
            $report->isSpecial()
        );
    }

    /**
     * Inhalt der Metabox zum Bearbeiten der Einsatzdetails
     *
     * @param WP_Post $post Das Post-Objekt des aktuell bearbeiteten Einsatzberichts
     */
    public function displayMetaBoxEinsatzdetails($post)
    {
        // Use nonce for verification
        wp_nonce_field('save_einsatz_details', 'einsatzverwaltung_nonce');

        $report = new IncidentReport($post);

        $nummer = $report->getNumber();
        $alarmzeit = $report->getTimeOfAlerting();
        $einsatzende = $report->getTimeOfEnding();
        $einsatzort = $report->getLocation();
        $einsatzleiter = $report->getIncidentCommander();
        $mannschaftsstaerke = $report->getWorkforce();

        $names = Data::getEinsatzleiterNamen();
        echo '<input type="hidden" id="einsatzleiter_used_values" value="' . implode(',', $names) . '" />';
        echo '<table><tbody>';

        $this->echoInputText(
            __("Einsatznummer", 'einsatzverwaltung'),
            'einsatzverwaltung_nummer',
            esc_attr($nummer),
            $this->core->getNextEinsatznummer(date('Y')),
            10
        );

        $this->echoInputText(
            __("Alarmzeit", 'einsatzverwaltung'),
            'einsatzverwaltung_alarmzeit',
            esc_attr($alarmzeit->format('Y-m-d H:i')),
            'JJJJ-MM-TT hh:mm'
        );

        $this->echoInputText(
            __("Einsatzende", 'einsatzverwaltung'),
            'einsatzverwaltung_einsatzende',
            esc_attr($einsatzende),
            'JJJJ-MM-TT hh:mm'
        );

        echo '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';

        $this->echoInputText(
            __("Einsatzort", 'einsatzverwaltung'),
            'einsatzverwaltung_einsatzort',
            esc_attr($einsatzort)
        );

        $this->echoInputText(
            __("Einsatzleiter", 'einsatzverwaltung'),
            'einsatzverwaltung_einsatzleiter',
            esc_attr($einsatzleiter)
        );

        $this->echoInputText(
            __("Mannschaftsst&auml;rke", 'einsatzverwaltung'),
            'einsatzverwaltung_mannschaft',
            esc_attr($mannschaftsstaerke)
        );

        echo '</tbody></table>';
    }

    /**
     * Gibt ein Eingabefeld für die Metabox aus
     *
     * @param string $label Beschriftung
     * @param string $name Feld-ID
     * @param string $value Feldwert
     * @param string $placeholder Platzhalter
     * @param int $size Größe des Eingabefelds
     */
    private function echoInputText($label, $name, $value, $placeholder = '', $size = 20)
    {
        echo '<tr><td><label for="' . $name . '">' . $label . '</label></td>';
        echo '<td><input type="text" id="' . $name . '" name="' . $name . '" value="'.$value.'" size="' . $size . '" ';
        if (!empty($placeholder)) {
            echo 'placeholder="'.$placeholder.'" ';
        }
        echo '/></td></tr>';
    }

    /**
     * Gibt eine Checkbox für die Metabox aus
     *
     * @param string $label Beschriftung
     * @param string $name Feld-ID
     * @param bool $state Zustandswert
     */
    private function echoInputCheckbox($label, $name, $state)
    {
        echo '<input type="checkbox" id="' . $name . '" name="' . $name . '" value="1" ';
        echo $this->utilities->checked($state) . '/><label for="' . $name . '">' . $label . '</label>';
    }

    /**
     * Zeigt die Metabox für die Einsatzart
     *
     * @param WP_Post $post Post-Object
     */
    public static function displayMetaBoxEinsatzart($post)
    {
        $report = new IncidentReport($post);
        $typeOfIncident = $report->getTypeOfIncident();
        Frontend::dropdownEinsatzart($typeOfIncident ? $typeOfIncident->term_id : 0);
    }

    /**
     * Legt fest, welche Spalten bei der Übersicht der Einsatzberichte im
     * Adminbereich angezeigt werden
     *
     * @param array $columns
     *
     * @return array
     */
    public function filterColumnsEinsatz($columns)
    {
        unset($columns['author']);
        unset($columns['date']);
        unset($columns['categories']);
        $columns['title'] = __('Einsatzbericht', 'einsatzverwaltung');
        $columns['e_nummer'] = __('Nummer', 'einsatzverwaltung');
        $columns['e_alarmzeit'] = __('Alarmzeit', 'einsatzverwaltung');
        $columns['e_einsatzende'] = __('Einsatzende', 'einsatzverwaltung');
        $columns['e_art'] = __('Art', 'einsatzverwaltung');
        $columns['e_fzg'] = __('Fahrzeuge', 'einsatzverwaltung');

        return $columns;
    }

    /**
     * Liefert den Inhalt für die jeweiligen Spalten bei der Übersicht der
     * Einsatzberichte im Adminbereich
     *
     * @param string $column
     * @param int $postId
     */
    public function filterColumnContentEinsatz($column, $postId)
    {
        global $post;

        $report = new IncidentReport($postId);

        switch ($column) {
            case 'e_nummer':
                $einsatznummer = $report->getNumber();
                echo (empty($einsatznummer) ? '-' : $einsatznummer);
                break;
            case 'e_einsatzende':
                $timeOfEnding = $report->getTimeOfEnding();
                if (empty($timeOfEnding)) {
                    echo '-';
                } else {
                    $timestamp = strtotime($timeOfEnding);
                    echo date("d.m.Y", $timestamp)."<br>".date("H:i", $timestamp);
                }
                break;
            case 'e_alarmzeit':
                $timeOfAlerting = $report->getTimeOfAlerting();

                if (empty($timeOfAlerting)) {
                    echo '-';
                } else {
                    echo $timeOfAlerting->format('d.m.Y') . '<br>' . $timeOfAlerting->format('H:i');
                }
                break;
            case 'e_art':
                $term = $report->getTypeOfIncident();
                if ($term) {
                    $url = esc_url(
                        add_query_arg(
                            array('post_type' => $post->post_type, 'einsatzart' => $term->slug),
                            'edit.php'
                        )
                    );
                    $text = esc_html(sanitize_term_field('name', $term->name, $term->term_id, 'einsatzart', 'display'));
                    echo '<a href="' . $url . '">' . $text . '</a>';
                } else {
                    echo '-';
                }
                break;
            case 'e_fzg':
                $fahrzeuge = $report->getVehicles();

                if (!empty($fahrzeuge)) {
                    $out = array();
                    foreach ($fahrzeuge as $term) {
                        $url = esc_url(
                            add_query_arg(
                                array('post_type' => $post->post_type, 'fahrzeug' => $term->slug),
                                'edit.php'
                            )
                        );
                        $text = esc_html(
                            sanitize_term_field('name', $term->name, $term->term_id, 'fahrzeug', 'display')
                        );
                        $out[] = '<a href="' . $url . '">' . $text . '</a>';
                    }
                    echo join(', ', $out);
                } else {
                    echo '-';
                }
                break;
            default:
                break;
        }
    }

    /**
     * Zahl der Einsatzberichte im Dashboard anzeigen
     *
     * @param array $items
     *
     * @return array
     */
    public function addEinsatzberichteToDashboard($items)
    {
        $postType = 'einsatz';
        if (post_type_exists($postType)) {
            $ptInfo = get_post_type_object($postType); // get a specific CPT's details
            $numberOfPosts = wp_count_posts($postType); // retrieve number of posts associated with this CPT
            $num = number_format_i18n($numberOfPosts->publish); // number of published posts for this CPT
            // singular/plural text label for CPT
            $text = _n($ptInfo->labels->singular_name, $ptInfo->labels->name, intval($numberOfPosts->publish));
            echo '<li class="'.$ptInfo->name.'-count page-count">';
            if (current_user_can('edit_einsatzberichte')) {
                echo '<a href="edit.php?post_type='.$postType.'">'.$num.' '.$text.'</a>';
            } else {
                echo '<span>'.$num.' '.$text.'</span>';
            }
            echo '</li>';
        }

        return $items;
    }

    /**
     * Zahl der Einsatzberichte im Dashboard anzeigen (für WordPress 3.7 und älter)
     */
    public function addEinsatzberichteToDashboardLegacy()
    {
        if (post_type_exists('einsatz')) {
            $postType = 'einsatz';
            $ptInfo = get_post_type_object($postType); // get a specific CPT's details
            $numberOfPosts = wp_count_posts($postType); // retrieve number of posts associated with this CPT
            $num = number_format_i18n($numberOfPosts->publish); // number of published posts for this CPT
            // singular/plural text label for CPT
            $text = _n($ptInfo->labels->singular_name, $ptInfo->labels->name, intval($numberOfPosts->publish));
            echo '<tr><td class="first b">';
            if (current_user_can('edit_einsatzberichte')) {
                echo '<a href="edit.php?post_type='.$postType.'">'.$num.'</a>';
            } else {
                echo $num;
            }
            echo '</td><td class="t">';
            if (current_user_can('edit_einsatzberichte')) {
                echo '<a href="edit.php?post_type='.$postType.'">'.$text.'</a>';
            } else {
                echo $text;
            }
            echo '</td></tr>';
        }
    }

    /**
     * Fügt weiterführende Links in der Pluginliste ein
     *
     * @param array $links Liste mit Standardlinks von WordPress
     * @param string $file Name der Plugindatei
     * @return array Vervollständigte Liste mit Links
     */
    public function pluginMetaLinks($links, $file)
    {
        if ($this->core->pluginBasename === $file) {
            $links[] = '<a href="https://einsatzverwaltung.abrain.de/feed/">Newsfeed</a>';
        }

        return $links;
    }

    /**
     * Zeigt einen Link zu den Einstellungen direkt auf der Plugin-Seite an
     *
     * @param $links
     *
     * @return array
     */
    public function addActionLinks($links)
    {
        $settingsPage = 'options-general.php?page=' . Settings::EVW_SETTINGS_SLUG;
        $actionLinks = array('<a href="' . admin_url($settingsPage) . '">Einstellungen</a>');
        return array_merge($links, $actionLinks);
    }
}
