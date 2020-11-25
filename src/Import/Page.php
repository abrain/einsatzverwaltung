<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\AdminPage;
use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
use abrain\Einsatzverwaltung\Import\Sources\Csv;
use abrain\Einsatzverwaltung\Import\Sources\WpEinsatz;
use abrain\Einsatzverwaltung\Utilities;
use function array_key_exists;
use function check_admin_referer;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function explode;
use function filter_input;
use function sanitize_text_field;
use function submit_button;
use function wp_die;
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

        if (empty($postAction)) {
            printf('<p>%s</p>', esc_html__('You can import incident reports from the following sources:', 'einsatzverwaltung'));

            echo '<ul>';
            foreach ($this->sources as $source) {
                $firstStep = $source->getFirstStep();

                echo '<li>';
                printf('<h2>%s</h2>', esc_html($source->getName()));
                printf('<p class="description">%s</p>', esc_html($source->getDescription()));
                if (false !== $firstStep) {
                    echo '<form method="post">';
                    printf('<input type="hidden" name="action" value="%s"/>', esc_attr($source->getActionAttribute($firstStep)));
                    wp_nonce_field($source->getNonce($firstStep));
                    submit_button($firstStep->getButtonText(), 'secondary', 'submit', false);
                    echo '</form>';
                }
                echo '</li>';
            }
            echo '</ul>';
            return;
        }

        list($identifier, $action) = explode(':', $postAction);
        if (!array_key_exists($identifier, $this->sources)) {
            wp_die('Invalid source');
        }
        $this->currentSource = $this->sources[$identifier];

        // Set variables for further flow control
        $currentStep = $this->currentSource->getStep($action);
        if ($currentStep === false) {
            wp_die('Invalid step');
        }

        // Check if the request has been sent through the form
        check_admin_referer($this->currentSource->getNonce($currentStep));

        $nextStep = $this->currentSource->getNextStep($currentStep);

        // Read the settings that have been passed from the previous step
        foreach ($currentStep->getArguments() as $argument) {
            $value = filter_input(INPUT_POST, $argument, FILTER_SANITIZE_STRING);
            $this->currentSource->putArg($argument, $value);
        }

        // Pass settings for date and time to the CSV source
        // TODO move custom logic into the class of the source
        if ('evw_csv' == $this->currentSource->getIdentifier()) {
            if (array_key_exists('import_date_format', $_POST)) {
                $this->currentSource->putArg('import_date_format', sanitize_text_field($_POST['import_date_format']));
            }

            if (array_key_exists('import_time_format', $_POST)) {
                $this->currentSource->putArg('import_time_format', sanitize_text_field($_POST['import_time_format']));
            }
        }

        // Carry over the setting whether to publish imported reports immediately
        $publishReports = filter_input(INPUT_POST, 'import_publish_reports', FILTER_SANITIZE_STRING);
        $this->currentSource->putArg(
            'import_publish_reports',
            Utilities::sanitizeCheckbox($publishReports)
        );

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
