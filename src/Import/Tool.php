<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
use abrain\Einsatzverwaltung\Import\Sources\WpEinsatz;

/**
 * Werkzeug für den Import von Einsatzberichten aus verschiedenen Quellen
 */
class Tool
{
    const EVW_TOOL_WPE_SLUG = 'einsatzvw-tool-import';

    private $sources = array();

    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->addHooks();
        $this->loadSources();
    }

    private function addHooks()
    {
        add_action('admin_menu', array($this, 'addToolToMenu'));
    }

    /**
     * Fügt das Werkzeug für wp-einsatz zum Menü hinzu
     */
    public function addToolToMenu()
    {
        add_management_page(
            __('Einsatzberichte importieren', 'einsatzverwaltung'),
            __('Einsatzberichte importieren', 'einsatzverwaltung'),
            'manage_options',
            self::EVW_TOOL_WPE_SLUG,
            array($this, 'renderToolPage')
        );
    }

    private function loadSources()
    {
        require_once dirname(__FILE__) . '/Sources/AbstractSource.php';
        require_once dirname(__FILE__) . '/Sources/WpEinsatz.php';
        $wpEinsatz = new WpEinsatz();
        $this->sources[$wpEinsatz->getIdentifier()] = $wpEinsatz;
    }

    /**
     * Generiert den Inhalt der Werkzeugseiten
     */
    public function renderToolPage()
    {
        echo '<div class="wrap">';
        echo '<h1>' . __('Einsatzberichte importieren', 'einsatzverwaltung') . '</h1>';

        $source = null;
        $aktion = null;
        if (array_key_exists('aktion', $_POST)) {
            list($identifier, $aktion) = explode(':', $_POST['aktion']);
            if (array_key_exists($identifier, $this->sources)) {
                $source = $this->sources[$identifier];
            }
        }

        if ($source != null && $source instanceof AbstractSource) {
            $source->renderPage($aktion);
        } else {
            echo '<p>Dieses Werkzeug importiert Einsatzberichte aus verschiedenen Quellen.</p>';

            echo '<ul>';
            /** @var AbstractSource $source */
            foreach ($this->sources as $source) {
                echo '<li>';
                echo '<h3>' . $source->getName() . '</h3>';
                echo '<p class="description">' . $source->getDescription() . '</p>';
                echo '<form method="post">';
                echo '<input type="hidden" name="aktion" value="' . $source->getActionAttribute('begin') . '" />';
                wp_nonce_field($source->getIdentifier() . '-begin');
                submit_button(__('Assistent starten', 'einsatzverwaltung'));
                echo '</form>';
                echo '</li>';
            }
            echo '</ul>';
        }

        echo '</div>';
    }
}
