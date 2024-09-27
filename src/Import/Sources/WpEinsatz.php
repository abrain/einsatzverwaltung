<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Exceptions\ImportCheckException;
use abrain\Einsatzverwaltung\Exceptions\ImportException;
use abrain\Einsatzverwaltung\Import\Step;
use function __;
use function esc_html;
use function join;
use function sprintf;
use function strpbrk;

/**
 * Importiert Daten aus wp-einsatz
 */
class WpEinsatz extends AbstractSource
{
    /**
     * @var string
     */
    private $tablename;

    public function __construct()
    {
        global $wpdb;
        $this->tablename = "{$wpdb->prefix}einsaetze";

        $this->description = 'Importiert Einsätze aus dem WordPress-Plugin wp-einsatz.';
        $this->identifier = 'evw_wpe';
        $this->name = 'wp-einsatz';

        $this->autoMatchFields = array(
            'Datum' => 'post_date'
        );

        $this->steps[] = new Step(self::STEP_ANALYSIS, 'Analyse', 'Datenbank analysieren');
        $this->steps[] = new Step(self::STEP_IMPORT, 'Import', 'Import starten');
    }

    /**
     * @inheritDoc
     */
    public function checkPreconditions()
    {
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '$this->tablename'") != $this->tablename) {
            throw new ImportCheckException(__('Database table of wp-einsatz does not exist', 'einsatzverwaltung'));
        }

        $fields = $this->getFields();
        foreach ($fields as $field) {
            if (strpbrk($field, 'äöüÄÖÜß/#')) {
                $this->problematicFields[] = $field;
            }
        }
        if (!empty($this->problematicFields)) {
            throw new ImportCheckException(sprintf(
                // translators: 1: comma-separated list of field names
                __('One or more fields have a special character in their name. This can become a problem during the import. Please rename the following fields in the settings of wp-einsatz: %s', 'einsatzverwaltung'),
                esc_html(join(', ', $this->problematicFields))
            ));
        }
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y-m-d';
    }

    /**
     * @inheritDoc
     */
    public function getEntries(array $requestedFields = [])
    {
        global $wpdb;
        $queryFields = (empty($requestedFields) ? '*' : implode(',', array_merge(array('ID'), $requestedFields)));
        $query = sprintf('SELECT %s FROM `%s` ORDER BY `Datum`', $queryFields, $this->tablename);
        $entries = $wpdb->get_results($query, ARRAY_A);

        if ($entries === null) {
            throw new ImportException('Dieser Fehler sollte nicht auftreten, da hat der Entwickler Mist gebaut...');
        }

        return $entries;
    }

    /**
     * @return string
     */
    public function getTimeFormat()
    {
        return 'H:i:s';
    }

    /**
     * Gibt die Spaltennamen der wp-einsatz-Tabelle zurück
     * (ohne ID, Nr_Jahr und Nr_Monat)
     *
     * @return array Die Spaltennamen
     */
    public function getFields()
    {
        if (!empty($this->cachedFields)) {
            return $this->cachedFields;
        }

        global $wpdb;

        $fields = array();
        foreach ($wpdb->get_col("DESCRIBE `$this->tablename`", 0) as $columnName) {
            // Unwichtiges ignorieren
            if ($columnName == 'ID' || $columnName == 'Nr_Jahr' || $columnName == 'Nr_Monat') {
                continue;
            }

            $fields[] = $columnName;
        }

        $this->cachedFields = $fields;

        return $fields;
    }
}
