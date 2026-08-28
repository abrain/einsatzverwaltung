<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Exceptions\ImportCheckException;
use wpdb;

/**
 * Importiert Daten aus wp-einsatz
 */
class WpEinsatz extends AbstractSource
{
    private $tablename;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->tablename = $wpdb->prefix . 'einsaetze';

        $this->autoMatchFields = array(
            'Datum' => 'post_date'
        );

        $this->actionOrder = array(
            array(
                'slug' => 'analysis',
                'name' => 'Analyse',
                'button_text' => 'Datenbank analysieren',
                'args' => array()
            ),
            array(
                'slug' => 'import',
                'name' => 'Import',
                'button_text' => 'Import starten',
                'args' => array()
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function checkPreconditions(): void
    {
        global $wpdb; /** @var wpdb $wpdb */
        if ($wpdb->get_var("SHOW TABLES LIKE '$this->tablename'") != $this->tablename) {
            throw new ImportCheckException(__('The database table in which wp-einsatz stores its data could not be found.', 'einsatzverwaltung'));
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
                join(', ', $this->problematicFields)
            ));
        }
    }

    /**
     * @return string
     */
    public function getDateFormat(): string
    {
        return 'Y-m-d';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Importiert Einsätze aus dem WordPress-Plugin wp-einsatz.';
    }

    /**
     * @inheritDoc
     */
    public function getEntries(array $requestedFields = []): array
    {
        global $wpdb; /** @var wpdb $wpdb */
        $queryFields = (empty($requestedFields) ? '*' : implode(',', array_merge(array('ID'), $requestedFields)));
        $query = sprintf('SELECT %s FROM `%s` ORDER BY `Datum`', $queryFields, $this->tablename);
        $entries = $wpdb->get_results($query, ARRAY_A);

        if ($entries === null) {
            throw new ImportCheckException(__('There was a problem retrieving the entries from the database.', 'einsatzverwaltung'));
        }

        return $entries;
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): string
    {
        return 'evw_wpe';
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'wp-einsatz';
    }

    /**
     * @return string
     */
    public function getTimeFormat(): string
    {
        return 'H:i:s';
    }

    /**
     * Gibt die Spaltennamen der wp-einsatz-Tabelle zurück
     * (ohne ID, Nr_Jahr und Nr_Monat)
     *
     * @return string[] Die Spaltennamen
     */
    public function getFields(): array
    {
        if (!empty($this->cachedFields)) {
            return $this->cachedFields;
        }

        global $wpdb; /** @var wpdb $wpdb */

        $fields = array();
        foreach ($wpdb->get_col("DESCRIBE `{$this->tablename}`") as $columnName) {
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
