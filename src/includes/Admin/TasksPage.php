<?php
namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\Utilities;

/**
 * Für diverse Aufgaben, die anfallen können und sonst keine eigene Seite haben
 */
class TasksPage
{
    const PAGE_SLUG = 'einsatzverwaltung-tasks';

    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * @var Data
     */
    private $data;

    /**
     * TasksPage constructor.
     *
     * @param Utilities $utilities
     * @param Data $data
     */
    public function __construct(Utilities $utilities, Data $data)
    {
        $this->utilities = $utilities;
        $this->data = $data;
    }

    public function addHooks()
    {
        add_action('admin_menu', array($this, 'registerPage'));
        add_action('admin_menu', array($this, 'hidePage'), 999);
    }

    public function registerPage()
    {
        add_management_page(
            'Einsatzverwaltung Tasks',
            'Einsatzverwaltung Tasks',
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'renderPage')
        );
    }

    public function hidePage()
    {
        remove_submenu_page('tools.php', self::PAGE_SLUG);
    }

    public function renderPage()
    {
        echo '<div class="wrap">';
        echo '<h1>Einsatzverwaltung</h1>';

        if (!current_user_can('manage_options')) {
            $this->utilities->printError('Du hast keine Berechtigung');
            return;
        }

        $action = filter_input(
            INPUT_GET,
            'action',
            FILTER_SANITIZE_SPECIAL_CHARS,
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
        );

        switch ($action) {
            case 'regenerate-slugs':
                $posts = get_posts(array(
                    'nopaging' => true,
                    'orderby' => 'post_date',
                    'order' => 'ASC',
                    'post_type' => 'einsatz',
                    'post_status' => array('publish', 'private'),
                ));

                echo '<p>Permalinks von ' . count($posts) . ' Einsatzberichten werden angepasst...</p>';

                // Da nur die Titelform geändert wird, muss keine Aktualisierung der laufenden Nummern etc. anlaufen
                remove_action('private_einsatz', array($this->data, 'onPublish'));
                remove_action('publish_einsatz', array($this->data, 'onPublish'));

                $processed = 0;
                foreach ($posts as $post) {
                    wp_update_post(array(
                        'ID' => $post->ID,
                        'post_name' => ''
                    ));
                    $processed++;

                    if ($processed % 50 == 0) {
                        echo $processed . ' Einsatzberichte verarbeitet<br>';
                        flush();
                    }
                }

                // Hooks wieder einhängen
                add_action('private_einsatz', array($this->data, 'onPublish'), 10, 2);
                add_action('publish_einsatz', array($this->data, 'onPublish'), 10, 2);

                echo $processed . ' Einsatzberichte verarbeitet<br>';
                $this->utilities->printSuccess('Die Permalinks wurden angepasst');
                $this->removeAdminNotice('regenerateSlugs');
                echo '<a href="' . admin_url('index.php') . '">Zur&uuml;ck zum Dashboard</a>';
                break;
            default:
                $this->utilities->printWarning('Unbekannte Aktion');
        }

        echo '</div>';
    }

    /**
     * Entfernt einen Bezeichner für eine Admin Notice aus der Liste der noch anzuzeigenden Notices
     *
     * @param string $slug Bezeichner für die Notice
     */
    private function removeAdminNotice($slug)
    {
        $notices = get_option('einsatzverwaltung_admin_notices');

        if (!is_array($notices)) {
            return;
        }

        $key = array_search($slug, $notices);
        if (false !== $key) {
            array_splice($notices, $key, 1);
            update_option('einsatzverwaltung_admin_notices', $notices);
        }
    }
}
