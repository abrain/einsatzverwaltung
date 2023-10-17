<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Exceptions\ImportCheckException;
use abrain\Einsatzverwaltung\Exceptions\ImportException;
use abrain\Einsatzverwaltung\Import\Step;
use function esc_attr;
use function in_array;
use function sprintf;

/**
 * Abstraktion für Importquellen
 */
abstract class AbstractSource
{
    const STEP_ANALYSIS = 'analysis';
    const STEP_CHOOSEFILE = 'choosefile';
    const STEP_IMPORT = 'import';
    protected $args = array();
    protected $autoMatchFields = array();
    protected $cachedFields;
    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $identifier = '';

    protected $internalFields = array();

    /**
     * @var string
     */
    protected $name = '';

    protected $problematicFields = array();

    /**
     * @var Step[]
     */
    protected $steps = array();

    /**
     * AbstractSource constructor.
     *
     */
    abstract public function __construct();

    /**
     * Checks if the preconditions for importing from this source are met.
     *
     * @throws ImportCheckException
     */
    abstract public function checkPreconditions();

    /**
     * TODO: The source shouldn't echo anything, but return its requirements in a standardized way
     *
     * Generiert für Argumente, die in der nächsten Action wieder gebraucht werden, Felder, die in das Formular
     * eingebaut werden können, damit diese mitgenommen werden
     *
     * @param string $currentAction
     * @param Step $nextStep
     */
    public function echoExtraFormFields(string $currentAction, Step $nextStep)
    {
        if (empty($nextStep) || !in_array($currentAction, [self::STEP_ANALYSIS, self::STEP_IMPORT])) {
            return;
        }

        echo '<h3>Allgemeine Einstellungen</h3>';
        echo '<label><input type="checkbox" name="import_publish_reports" value="1" ';
        checked($this->args['import_publish_reports'], '1');
        echo ' /> Einsatzberichte sofort ver&ouml;ffentlichen</label>';
        echo '<p class="description">Das Setzen dieser Option verl&auml;ngert die Importzeit deutlich, Benutzung auf eigene Gefahr. Standardm&auml;&szlig;ig werden die Berichte als Entwurf importiert.</p>';

        foreach ($nextStep->getArguments() as $arg) {
            if (array_key_exists($arg, $this->args)) {
                printf('<input type="hidden" name="%s" value="%s" />', esc_attr($arg), esc_attr($this->args[$arg]));
            }
        }
    }

    /**
     * Gibt die Beschreibung der Importquelle zurück
     *
     * @return string Beschreibung der Importquelle
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param Step $step
     *
     * @return string
     */
    public function getActionAttribute(Step $step)
    {
        return sprintf("%s:%s", $this->getIdentifier(), $step->getSlug());
    }

    /**
     * Gets a Step object based on its slug.
     *
     * @param string $slug Slug of the step
     *
     * @return Step|false The Step object or false if there is no step for this slug
     */
    public function getStep(string $slug)
    {
        if (empty($slug)) {
            return false;
        }

        foreach ($this->steps as $step) {
            if ($step->getSlug() == $slug) {
                return $step;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getAutoMatchFields()
    {
        return $this->autoMatchFields;
    }

    /**
     * @return string
     */
    abstract public function getDateFormat();

    /**
     * Gibt die Einsatzberichte der Importquelle zurück
     *
     * @param string[] $requestedFields Names of the fields that should be queried from the source. Defaults to empty
     * array, which requests all fields.
     *
     * @return array
     * @throws ImportException
     */
    abstract public function getEntries(array $requestedFields = []);

    /**
     * @return array
     * @throws ImportCheckException
     */
    abstract public function getFields();

    /**
     * Returns the first step of the import source.
     *
     * @return Step|false The Step object representing the first step or false if there are no steps defined.
     */
    public function getFirstStep()
    {
        if (empty($this->steps)) {
            return false;
        }

        return $this->steps[0];
    }

    /**
     * Gibt den eindeutigen Bezeichner der Importquelle zurück
     *
     * @return string Eindeutiger Bezeichner der Importquelle
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Gibt den Wert für das name-Attribut eines Formularelements zurück
     *
     * @param string $field Bezeichner des Felds
     *
     * @return string Eindeutiger Name bestehend aus Bezeichnern der Importquelle und des Felds
     * @throws ImportCheckException
     */
    public function getInputName(string $field)
    {
        $fieldId = array_search($field, $this->getFields());
        return $this->getIdentifier() . '-field' . $fieldId;
    }

    /**
     * Gibt den Namen der Importquelle zurück
     *
     * @return string Name der Importquelle
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gibt die nächste Action der Importquelle zurück
     *
     * @param Step $currentStep Array, das die aktuelle Action beschreibt
     *
     * @return Step|false Ein Array, das die nächste Action beschreibt, oder false, wenn es keine weitere gibt
     */
    public function getNextStep(Step $currentStep)
    {
        if (empty($this->steps)) {
            return false;
        }

        $key = array_search($currentStep, $this->steps);

        // Make sure the given step was found in the list of steps
        if ($key === false) {
            return false;
        }

        // Return false if this was the last step
        if ($key + 1 >= count($this->steps)) {
            return false;
        }

        return $this->steps[$key + 1];
    }

    /**
     * @param Step $step
     *
     * @return string
     */
    public function getNonce(Step $step)
    {
        return sprintf("%s_%s", $this->getIdentifier(), $step->getSlug());
    }

    /**
     * @return array
     */
    public function getProblematicFields()
    {
        return $this->problematicFields;
    }

    /**
     * @return string
     */
    abstract public function getTimeFormat();

    /**
     * @return array Felder, die nicht als Importziel angeboten werden sollen
     */
    public function getUnmatchableFields()
    {
        return array_merge(array_values($this->autoMatchFields), $this->internalFields);
    }

    /**
     * @return bool
     */
    public function isPublishReports()
    {
        if (!array_key_exists('import_publish_reports', $this->args)) {
            return false;
        }

        return 1 === $this->args['import_publish_reports'];
    }

    /**
     * Setzt ein Argument in der Importquelle
     *
     * @param $key
     * @param $value
     */
    public function putArg($key, $value)
    {
        if (empty($key)) {
            return;
        }

        $this->args[$key] = $value;
    }
}
