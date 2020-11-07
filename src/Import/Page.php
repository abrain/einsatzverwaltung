<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\AdminPage;
use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
use abrain\Einsatzverwaltung\Import\Sources\Csv;
use abrain\Einsatzverwaltung\Import\Sources\WpEinsatz;
use function __;
use function array_key_exists;
use function check_admin_referer;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function explode;
use function filter_input;
use function submit_button;
use function wp_nonce_field;
use const FILTER_SANITIZE_STRING;
use const INPUT_POST;

/**
 * The main page for the Import tool
 * @package abrain\Einsatzverwaltung\Import
 */
class Page extends AdminPage
{

    /**
     * @var AbstractSource
     */
    private $currentSource;

    /**
     * @var AbstractSource[]
     */
    private $sources;

    /**
     * Page constructor.
     */
    public function __construct()
    {
        parent::__construct('Einsatzberichte importieren', 'einsatzvw-tool-import');
    }

    protected function echoPageContent()
    {
        $this->loadSources();

        $action = null;
        $postAction = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        if (!empty($postAction)) {
            list($identifier, $action) = explode(':', $postAction);
            if (array_key_exists($identifier, $this->sources)) {
                $this->currentSource = $this->sources[$identifier];
            }
        }

        if (null == $this->currentSource || !($this->currentSource instanceof AbstractSource) || empty($action)) {
            printf('<p>%s</p>', esc_html__('You can import incident reports from the following sources:', 'einsatzverwaltung'));

            echo '<ul>';
            foreach ($this->sources as $source) {
                $firstAction = $source->getFirstAction();

                echo '<li>';
                printf('<h2>%s</h2>', esc_html($source->getName()));
                printf('<p class="description">%s</p>', esc_html($source->getDescription()));
                if (false !== $firstAction) {
                    echo '<form method="post">';
                    printf('<input type="hidden" name="action" value="%s"/>', esc_attr($source->getActionAttribute($firstAction['slug'])));
                    wp_nonce_field($source->getNonce($firstAction['slug']));
                    submit_button($firstAction['button_text'], 'secondary', 'submit', false);
                    echo '</form>';
                }
                echo '</li>';
            }
            echo '</ul>';
            return;
        }

        // Check if the request has been sent through the form
        check_admin_referer($this->currentSource->getNonce($action));

        // Set variables for further flow control
        $currentAction = $this->currentSource->getAction($action);
        $nextAction = $this->currentSource->getNextAction($currentAction);

        echo '<p>Content</p>';
    }

    private function loadSources()
    {
        $wpEinsatz = new WpEinsatz();
        $this->sources[$wpEinsatz->getIdentifier()] = $wpEinsatz;

        $csv = new Csv();
        $this->sources[$csv->getIdentifier()] = $csv;
    }
}
