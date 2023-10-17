<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\AdminPage;
use abrain\Einsatzverwaltung\Exceptions\ImportCheckException;
use abrain\Einsatzverwaltung\Exceptions\ImportException;
use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
use abrain\Einsatzverwaltung\Import\Sources\Csv;
use abrain\Einsatzverwaltung\Import\Sources\FileSource;
use abrain\Einsatzverwaltung\Import\Sources\WpEinsatz;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ReportNumberController;
use abrain\Einsatzverwaltung\Utilities;
use function __;
use function _n;
use function array_key_exists;
use function array_keys;
use function check_admin_referer;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function explode;
use function filter_input;
use function get_posts;
use function implode;
use function in_array;
use function printf;
use function sanitize_text_field;
use function selected;
use function sprintf;
use function strcmp;
use function submit_button;
use function uasort;
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
        $currentSource = $this->sources[$identifier];

        // Set variables for further flow control
        $currentStep = $currentSource->getStep($action);
        if ($currentStep === false) {
            wp_die('Invalid step');
        }

        // Check if the request has been sent through the form
        check_admin_referer($currentSource->getNonce($currentStep));

        $nextStep = $currentSource->getNextStep($currentStep);

        // Read the settings that have been passed from the previous step
        foreach ($currentStep->getArguments() as $argument) {
            $value = filter_input(INPUT_POST, $argument, FILTER_SANITIZE_STRING);
            $currentSource->putArg($argument, $value);
        }

        // Pass settings for date and time to the CSV source
        // TODO move custom logic into the class of the source
        if ('evw_csv' == $currentSource->getIdentifier()) {
            if (array_key_exists('import_date_format', $_POST)) {
                $currentSource->putArg('import_date_format', sanitize_text_field($_POST['import_date_format']));
            }

            if (array_key_exists('import_time_format', $_POST)) {
                $currentSource->putArg('import_time_format', sanitize_text_field($_POST['import_time_format']));
            }
        }

        // Carry over the setting whether to publish imported reports immediately
        $publishReports = filter_input(INPUT_POST, 'import_publish_reports', FILTER_SANITIZE_STRING);
        $currentSource->putArg(
            'import_publish_reports',
            Utilities::sanitizeCheckbox($publishReports)
        );

        printf("<h2>%s</h2>", esc_html($currentStep->getTitle()));

        switch ($action) {
            case AbstractSource::STEP_ANALYSIS:
                $this->echoAnalysis($currentSource, $currentStep, $nextStep);
                break;
            case AbstractSource::STEP_CHOOSEFILE:
                if (!$currentSource instanceof FileSource) {
                    $this->printError('The selected source does not import from a file');
                    return;
                }
                $this->echoFileChooser($currentSource, $nextStep);
                break;
            case AbstractSource::STEP_IMPORT:
                $this->echoImport($currentSource, $currentStep);
                break;
            default:
                $this->printError(sprintf('Action %s is unknown', esc_html($action)));
        }
    }

    /**
     * @param AbstractSource $source
     * @param Step $currentStep
     * @param Step $nextStep
     */
    private function echoAnalysis(AbstractSource $source, Step $currentStep, Step $nextStep)
    {
        try {
            $source->checkPreconditions();
        } catch (ImportCheckException $e) {
            $this->printError(sprintf('Voraussetzung nicht erf&uuml;llt: %s', $e->getMessage()));
            return;
        }

        try {
            $fields = $source->getFields();
        } catch (ImportCheckException $e) {
            $this->printError('Fehler beim Abrufen der Felder');
            return;
        }

        if (empty($fields)) {
            $this->printError('Es wurden keine Felder gefunden');
            return;
        }
        $numberOfFields = count($fields);
        $this->printSuccess(sprintf(
            _n('Found %1$d field: %2$s', 'Found %1$d fields: %2$s', $numberOfFields, 'einsatzverwaltung'),
            $numberOfFields,
            esc_html(implode(', ', $fields))
        ));

        // Check for mandatory fields
        $mandatoryFieldsOk = true;
        foreach (array_keys($source->getAutoMatchFields()) as $autoMatchField) {
            if (!in_array($autoMatchField, $fields)) {
                $this->printError(
                    sprintf('Das automatisch zu importierende Feld %s konnte nicht gefunden werden!', $autoMatchField)
                );
                $mandatoryFieldsOk = false;
            }
        }
        if (!$mandatoryFieldsOk) {
            return;
        }

        // Count the entries
        try {
            $entries = $source->getEntries();
        } catch (ImportException $e) {
            $this->printError(sprintf('Fehler beim Abfragen der Eins&auml;tze: %s', $e->getMessage()));
            return;
        }

        if (empty($entries)) {
            $this->printWarning('Es wurden keine Eins&auml;tze gefunden.');
            return;
        }
        $this->printSuccess(sprintf("Es wurden %s Eins&auml;tze gefunden", count($entries)));

        // Felder matchen
        echo "<h3>Felder zuordnen</h3>";
        $this->renderMatchForm($source, $currentStep, $nextStep);
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
        wp_nonce_field($source->getNonce($nextStep));

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

        printf('<input type="hidden" name="action" value="%s" />', $source->getActionAttribute($nextStep));
        submit_button($nextStep->getButtonText());
        echo '</form>';
    }

    /**
     * @param AbstractSource $source
     * @param Step $currentStep
     */
    private function echoImport(AbstractSource $source, Step $currentStep)
    {
        try {
            $source->checkPreconditions();
        } catch (ImportCheckException $e) {
            $this->printError(sprintf('Voraussetzung nicht erf&uuml;llt: %s', $e->getMessage()));
            return;
        }

        // Get the mapping of the source fields to our internal fields
        $mappingHelper = new MappingHelper();
        try {
            $mapping = $mappingHelper->getMapping($source, IncidentReport::getFields());
            $mappingHelper->validateMapping($mapping, $source);
        } catch (ImportCheckException $e) {
            $this->printError(sprintf("Fehler bei der Zuordnung: %s", $e->getMessage()));

            // Repeat the mapping
            $this->renderMatchForm($source, $currentStep, $currentStep, empty($mapping) ? [] : $mapping);
            return;
        }

        // Start the import
        echo '<p>Die Daten werden eingelesen, das kann einen Moment dauern.</p>';
        // TODO do the import

        $this->printSuccess('Der Import ist abgeschlossen');
        $url = admin_url('edit.php?post_type=einsatz');
        printf('<a href="%s">Zu den Einsatzberichten</a>', $url);
    }

    private function loadSources()
    {
        $wpEinsatz = new WpEinsatz();
        $this->sources[$wpEinsatz->getIdentifier()] = $wpEinsatz;

        $csv = new Csv();
        $this->sources[$csv->getIdentifier()] = $csv;
    }

    /**
     * Gibt das Formular für die Zuordnung zwischen zu importieren Feldern und denen von Einsatzverwaltung aus
     *
     * @param AbstractSource $source
     * @param Step $currentStep
     * @param Step $nextStep
     * @param array $mapping
     */
    private function renderMatchForm(AbstractSource $source, Step $currentStep, Step $nextStep, array $mapping = [])
    {
        try {
            $fields = $source->getFields();
        } catch (ImportCheckException $e) {
            $this->printError('Fehler beim Abrufen der Felder');
            return;
        }

        // If the incident numbers are managed automatically, don't offer to import them
        $unmatchableFields = $source->getUnmatchableFields();
        if (ReportNumberController::isAutoIncidentNumbers()) {
            $unmatchableFields[] = 'einsatz_incidentNumber';
        }

        echo '<form method="post">';
        wp_nonce_field($source->getNonce($nextStep));
        printf('<input type="hidden" name="action" value="%s" />', esc_attr($source->getActionAttribute($nextStep)));
        echo '<table class="evw_match_fields"><tr><th>';
        printf('Feld in %s', $source->getName());
        echo '</th><th>Feld in Einsatzverwaltung</th></tr><tbody>';
        foreach ($fields as $field) {
            printf("<tr><td><b>%s</b></td><td>", esc_html($field));
            if (array_key_exists($field, $source->getAutoMatchFields())) {
                echo 'wird automatisch zugeordnet';
            } elseif (in_array($field, $source->getProblematicFields())) {
                $this->printWarning(sprintf('Probleme mit Feld %s, siehe Analyse', $field));
            } else {
                $selected = '-';
                if (!empty($mapping) && array_key_exists($field, $mapping) && !empty($mapping[$field])) {
                    $selected = $mapping[$field];
                }

                try {
                    $this->renderOwnFieldsDropdown($source->getInputName($field), $selected, $unmatchableFields);
                } catch (ImportCheckException $e) {
                    echo 'ERROR';
                }
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
        if (!empty($nextStep)) {
            $source->echoExtraFormFields($currentStep->getSlug(), $nextStep);
        }
        submit_button($nextStep->getButtonText());
        echo '</form>';
    }

    /**
     * Generates a select tag for selecting the available properties of reports
     *
     * @param string $name Name of the select tag
     * @param string $selected Value of the selected option, defaults to '-' for 'do not import'
     * @param array $fieldsToSkip Array of own field names that should be skipped during output
     */
    private function renderOwnFieldsDropdown(string $name, string $selected = '-', array $fieldsToSkip = [])
    {
        $fields = IncidentReport::getFields();

        // Remove fields that should not be presented as an option
        foreach ($fieldsToSkip as $ownField) {
            unset($fields[$ownField]);
        }

        // Sort fields by name
        uasort($fields, function ($field1, $field2) {
            return strcmp($field1['label'], $field2['label']);
        });
        $string = sprintf('<select name="%s">', esc_attr($name));
        /** @noinspection HtmlUnknownAttribute */
        $string .= sprintf(
            '<option value="-" %s>%s</option>',
            selected($selected, '-', false),
            'nicht importieren'
        );
        foreach ($fields as $slug => $fieldProperties) {
            /** @noinspection HtmlUnknownAttribute */
            $string .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($slug),
                selected($selected, $slug, false),
                esc_html($fieldProperties['label'])
            );
        }
        $string .= '</select>';

        echo $string;
    }
}
