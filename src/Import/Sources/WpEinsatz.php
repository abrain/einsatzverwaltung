<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Utilities;
use wpdb;

/**
 * Importiert Daten aus wp-einsatz
 */
class WpEinsatz extends AbstractSource
{
    /**
     * @var Utilities
     */
    protected $utilities;
    private $tablename;

    /**
     * Constructor
     *
     * @param Utilities $utilities
     */
    public function __construct($utilities)
    {
        $this->utilities = $utilities;

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
    public function checkPreconditions()
    {
        global $wpdb; /** @var wpdb $wpdb */
        if ($wpdb->get_var("SHOW TABLES LIKE '$this->tablename'") != $this->tablename) {
            $this->utilities->printError('Die Tabelle, in der wp-einsatz seine Daten speichert, konnte nicht gefunden werden.');
            return false;
        }

        $this->utilities->printSuccess('Die Tabelle, in der wp-einsatz seine Daten speichert, wurde gefunden.');
        return true;
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
    public function getDescription()
    {
        return 'Importiert Einsätze aus dem WordPress-Plugin wp-einsatz.';
    }

    /**
     * @inheritDoc
     */
    public function getEntries($fields)
    {
        global $wpdb; /** @var wpdb $wpdb */
        $queryFields = (null === $fields ? '*' : implode(',', array_merge(array('ID'), $fields)));
        $query = sprintf('SELECT %s FROM %s ORDER BY Datum', $queryFields, $this->tablename);
        $entries = $wpdb->get_results($query, ARRAY_A);

        if ($entries === null) {
            $this->utilities->printError('Dieser Fehler sollte nicht auftreten, da hat der Entwickler Mist gebaut...');
            return false;
        }

        return $entries;
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier()
    {
        return 'evw_wpe';
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'wp-einsatz';
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

        global $wpdb; /** @var wpdb $wpdb */

        $fields = array();
        foreach ($wpdb->get_col("DESC " . $this->tablename, 0) as $columnName) {
            // Unwichtiges ignorieren
            if ($columnName == 'ID' || $columnName == 'Nr_Jahr' || $columnName == 'Nr_Monat') {
                continue;
            }

            $fields[] = $columnName;
        }

        foreach ($fields as $field) {
            if (strpbrk($field, 'äöüÄÖÜß/#')) {
                $this->utilities->printWarning(sprintf(
                    'Feldname %s enth&auml;lt Zeichen (z.B. Umlaute oder Sonderzeichen), die beim Import zu Problemen f&uuml;hren.<br>Bitte das Feld in den Einstellungen von wp-einsatz umbenennen, wenn Sie es importieren wollen.',
                    $field
                ));
                $this->problematicFields[] = $field;
            }
        }

        $this->cachedFields = $fields;

        return $fields;
    }
}
