<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Exceptions\ImportCheckException;

/**
 * Abstraktion für Importquellen
 */
abstract class AbstractSource
{
    protected $actionOrder = array();
    protected $args = array();
    protected $autoMatchFields = array();
    protected $internalFields = array();
    protected $problematicFields = array();
    protected $cachedFields;

    /**
     * AbstractSource constructor.
     */
    abstract public function __construct();

    /**
     * @return void
     * @throws ImportCheckException
     */
    abstract public function checkPreconditions(): void;

    /**
     * Generiert für Argumente, die in der nächsten Action wieder gebraucht werden, Felder, die in das Formular
     * eingebaut werden können, damit diese mitgenommen werden
     *
     * @param array $nextAction Die nächste Action
     */
    public function echoExtraFormFields(array $nextAction)
    {
        if (empty($nextAction)) {
            return;
        }

        echo '<h3>Allgemeine Einstellungen</h3>';
        echo '<label><input type="checkbox" name="import_publish_reports" value="1" ';
        checked($this->args['import_publish_reports'], '1');
        echo ' /> Einsatzberichte sofort ver&ouml;ffentlichen</label>';
        echo '<p class="description">Das Setzen dieser Option verl&auml;ngert die Importzeit deutlich, Benutzung auf eigene Gefahr. Standardm&auml;&szlig;ig werden die Berichte als Entwurf importiert.</p>';

        foreach ($nextAction['args'] as $arg) {
            if (array_key_exists($arg, $this->args)) {
                echo '<input type="hidden" name="'.$arg.'" value="' . $this->args[$arg] . '" />';
            }
        }
    }

    /**
     * Gibt die Beschreibung der Importquelle zurück
     *
     * @return string Beschreibung der Importquelle
     */
    abstract public function getDescription(): string;

    /**
     * @param $action
     * @return string
     */
    public function getActionAttribute($action): string
    {
        return $this->getIdentifier() . ':' . $action;
    }

    /**
     * Gibt das Action-Array für $slug zurück
     *
     * @param string $slug Slug der Action
     *
     * @return array|bool Das Array der Action oder false, wenn es keines für $slug gibt
     */
    public function getAction(string $slug)
    {
        if (empty($slug)) {
            return false;
        }

        foreach ($this->actionOrder as $action) {
            if ($action['slug'] == $slug) {
                return $action;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getAutoMatchFields(): array
    {
        return $this->autoMatchFields;
    }

    /**
     * @return string
     */
    abstract public function getDateFormat(): string;

    /**
     * Gibt die Einsatzberichte der Importquelle zurück
     *
     * @param string[] $requestedFields Felder der Importquelle, die abgefragt werden sollen. Ist das Array leer, werden alle
     * Felder abgefragt.
     *
     * @return array
     * @throws ImportCheckException
     */
    abstract public function getEntries(array $requestedFields): array;

    /**
     * Returns the names of the fields available in the source.
     *
     * @throws ImportCheckException
     *
     * @return string[]
     */
    abstract public function getFields(): array;

    /**
     * Gibt die erste Action der Importquelle zurück
     *
     * @return array|bool Ein Array, das die erste Action beschreibt, oder false, wenn es keine Action gibt
     */
    public function getFirstAction()
    {
        if (empty($this->actionOrder)) {
            return false;
        }

        return $this->actionOrder[0];
    }

    /**
     * Gibt den eindeutigen Bezeichner der Importquelle zurück
     *
     * @return string Eindeutiger Bezeichner der Importquelle
     */
    abstract public function getIdentifier(): string;

    /**
     * Gibt den Wert für das name-Attribut eines Formularelements zurück
     *
     * @param string $field Bezeichner des Felds
     *
     * @return string Eindeutiger Name bestehend aus Bezeichnern der Importquelle und des Felds
     * @throws ImportCheckException
     */
    public function getInputName(string $field): string
    {
        $fieldId = array_search($field, $this->getFields());
        return $this->getIdentifier() . '-field' . $fieldId;
    }

    /**
     * Gibt den Namen der Importquelle zurück
     *
     * @return string Name der Importquelle
     */
    abstract public function getName(): string;

    /**
     * Gibt die nächste Action der Importquelle zurück
     *
     * @param array $currentAction Array, das die aktuelle Action beschreibt
     *
     * @return array|bool Ein Array, das die nächste Action beschreibt, oder false, wenn es keine weitere gibt
     */
    public function getNextAction(array $currentAction)
    {
        if (empty($this->actionOrder)) {
            return false;
        }

        $key = array_search($currentAction, $this->actionOrder);

        if ($key + 1 >= count($this->actionOrder)) {
            return false;
        }

        return $this->actionOrder[$key + 1];
    }

    /**
     * @return array
     */
    public function getProblematicFields(): array
    {
        return $this->problematicFields;
    }

    /**
     * @return string
     */
    abstract public function getTimeFormat(): string;

    /**
     * @return array Felder, die nicht als Importziel angeboten werden sollen
     */
    public function getUnmatchableFields(): array
    {
        return array_merge(array_values($this->autoMatchFields), $this->internalFields);
    }

    /**
     * @return bool
     */
    public function isPublishReports(): bool
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
