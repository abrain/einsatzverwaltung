<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Utilities;
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
        /** @var wpdb $wpdb */
        global $wpdb;
        $this->tablename = $wpdb->prefix . 'einsaetze';

        $this->autoMatchFields = array(
            'Datum' => 'post_date'
        );

        $this->actionOrder = array(
            array(
                'slug' => 'analysis',
                'name' => __('Analyse', 'einsatzverwaltung'),
                'button_text' => __('Datenbank analysieren', 'einsatzverwaltung')
            ),
            array(
                'slug' => 'import',
                'name' => __('Import', 'einsatzverwaltung'),
                'button_text' => __('Import starten', 'einsatzverwaltung')
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function checkForProblems($fields, $quiet = false)
    {
        foreach ($fields as $field) {
            if (strpbrk($field, 'äöüÄÖÜß/#')) {
                if (!$quiet) {
                    Utilities::printWarning(sprintf(
                        'Feldname %s enth&auml;lt Zeichen (z.B. Umlaute oder Sonderzeichen), die beim Import zu Problemen f&uuml;hren.<br>Bitte das Feld in den Einstellungen von wp-einsatz umbenennen, wenn Sie es importieren wollen.',
                        $field
                    ));
                }
                $this->problematicFields[] = $field;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function checkPreconditions()
    {
        global $wpdb; /** @var wpdb $wpdb */
        if ($wpdb->get_var("SHOW TABLES LIKE '$this->tablename'") != $this->tablename) {
            Utilities::printError('Die Tabelle, in der wp-einsatz seine Daten speichert, konnte nicht gefunden werden.');
            return false;
        }

        Utilities::printSuccess('Die Tabelle, in der wp-einsatz seine Daten speichert, wurde gefunden.');
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getDescription()
    {
        return __('Importiert Einsätze aus dem WordPress-Plugin wp-einsatz.', 'einsatzverwaltung');
    }

    /**
     * @inheritDoc
     */
    public function getEntries($fields)
    {
        global $wpdb; /** @var wpdb $wpdb */
        $queryFields = (null === $fields ? '*' : implode(array_merge(array('ID'), $fields), ','));
        $query = sprintf('SELECT %s FROM %s ORDER BY Datum', $queryFields, $this->tablename);
        $entries = $wpdb->get_results($query, ARRAY_A);

        if ($entries === null) {
            Utilities::printError('Dieser Fehler sollte nicht auftreten, da hat der Entwickler Mist gebaut...');
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
     * Gibt die Spaltennamen der wp-einsatz-Tabelle zurück
     * (ohne ID, Nr_Jahr und Nr_Monat)
     *
     * @return array Die Spaltennamen
     */
    public function getFields()
    {
        global $wpdb; /** @var wpdb $wpdb */

        $felder = array();
        foreach ($wpdb->get_col("DESC " . $this->tablename, 0) as $columnName) {
            // Unwichtiges ignorieren
            if ($columnName == 'ID' || $columnName == 'Nr_Jahr' || $columnName == 'Nr_Monat') {
                continue;
            }

            $felder[] = $columnName;
        }
        return $felder;
    }
}
