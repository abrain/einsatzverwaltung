<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\AdminPage;
use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
use abrain\Einsatzverwaltung\Import\Sources\Csv;
use abrain\Einsatzverwaltung\Import\Sources\FileSource;
use abrain\Einsatzverwaltung\Import\Sources\WpEinsatz;
use abrain\Einsatzverwaltung\Utilities;
use function __;
use function array_key_exists;
use function check_admin_referer;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function explode;
use function filter_input;
use function get_posts;
use function printf;
use function sanitize_text_field;
use function sprintf;
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

        printf("<h2>%s</h2>", esc_html($currentStep->getTitle()));

        switch ($action) {
            case AbstractSource::STEP_ANALYSIS:
                echo "Analysiere...";
                break;
            case AbstractSource::STEP_CHOOSEFILE:
                if (!$this->currentSource instanceof FileSource) {
                    $this->printError('The selected source does not import from a file');
                    return;
                }
                $this->echoFileChooser($this->currentSource, $nextStep);
                break;
            case AbstractSource::STEP_IMPORT:
                echo "Import...";
                break;
            default:
                $this->printError(sprintf('Action %s is unknown', esc_html($action)));
        }
    }

    /**
     * @param FileSource $source
     * @param Step $nextStep
     */
    private function echoFileChooser(FileSource $source, Step $nextStep)
    {
        $mimeType = $source->getMimeType();
        if (empty($mimeType)) {
            $this->printError('The MIME type must not be empty');
            return;
        }

        echo '<p>Bitte werfe einen Blick in die <a href="https://einsatzverwaltung.abrain.de/dokumentation/import-von-einsatzberichten/">Dokumentation</a>, um herauszufinden, welche Anforderungen an die Datei gestellt werden.</p>';

        echo '<h3>In der Mediathek gefundene Dateien</h3>';
        echo 'Bevor eine Datei f&uuml;r den Import verwendet werden kann, muss sie in die <a href="' . admin_url('upload.php') . '">Mediathek</a> hochgeladen worden sein. Nach erfolgreichem Import kann die Datei gel&ouml;scht werden.';
        $this->printWarning('Der Inhalt der Mediathek ist &ouml;ffentlich abrufbar. Achte darauf, dass die Importdatei keine sensiblen Daten enth&auml;lt.');

        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => $mimeType
        ));

        if (empty($attachments)) {
            $this->printInfo(sprintf(__('No files of type %s found.', 'einsatzverwaltung'), $mimeType));
            return;
        }

        echo '<form method="post">';
        wp_nonce_field($this->currentSource->getNonce($nextStep));

        echo '<fieldset>';
        foreach ($attachments as $attachment) {
            printf(
                '<label><input type="radio" name="file_id" value="%d">%s</label><br/>',
                esc_attr($attachment->ID),
                esc_html($attachment->post_title)
            );
        }
        echo '</fieldset>';

        $source->echoExtraFormFields(AbstractSource::STEP_CHOOSEFILE, $nextStep);

        printf('<input type="hidden" name="aktion" value="%s" />', $this->currentSource->getActionAttribute($nextStep));
        submit_button($nextStep->getButtonText());
        echo '</form>';
    }

    private function loadSources()
    {
        $wpEinsatz = new WpEinsatz();
        $this->sources[$wpEinsatz->getIdentifier()] = $wpEinsatz;

        $csv = new Csv();
        $this->sources[$csv->getIdentifier()] = $csv;
    }
}
