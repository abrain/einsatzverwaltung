<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ToolEinsatznummernReparieren;
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
     * @inheritDoc
     */
    public function renderPage($action)
    {
        if ($action == 'begin') {
            check_admin_referer($this->getIdentifier() . '-begin');

            /** @var wpdb $wpdb */
            global $wpdb;
            if ($wpdb->get_var("SHOW TABLES LIKE '$this->tablename'") != $this->tablename) {
                Utilities::printError('Die Tabelle, in der wp-einsatz seine Daten speichert, konnte nicht gefunden werden.');
                return;
            }
            Utilities::printSuccess('Die Tabelle, in der wp-einsatz seine Daten speichert, wurde gefunden.');

            // Datenbank analysieren
            echo "<h3>Analyse</h3>";
            $felder = $this->getFields();
            if (empty($felder)) {
                Utilities::printError('Es wurden keine Felder in der Tabelle gefunden');
                return;
            }

            Utilities::printSuccess('Es wurden folgende Felder gefunden: ' . implode($felder, ', '));

            // Auf Pflichtfelder prüfen
            foreach (array_keys($this->autoMatchFields) as $mandatoryField) {
                if (!in_array($mandatoryField, $felder)) {
                    Utilities::printError(
                        sprintf('Das Pflichtfeld %s konnte nicht in der Datenbank gefunden werden!', $mandatoryField)
                    );
                    return;
                }
            }

            // Auf problematische Felder prüfen
            $this->checkForProblems($felder);

            // Einsätze zählen
            $anzahl_einsaetze = $wpdb->get_var("SELECT COUNT(*) FROM $this->tablename");
            if ($anzahl_einsaetze === null) {
                Utilities::printWarning('Konnte die Anzahl der Ein&auml;tze in wp-einsatz nicht abfragen. M&ouml;glicherweise gibt es ein Problem mit der Datenbank.');
            } else {
                if ($anzahl_einsaetze > 0) {
                    Utilities::printSuccess("Es wurden $anzahl_einsaetze Eins&auml;tze gefunden");
                } else {
                    Utilities::printWarning('Es wurden keine Eins&auml;tze gefunden.');
                }
            }

            // Hinweise ausgeben
            echo '<h3>Hinweise zu den erwarteten Daten</h3>';
            echo '<p>Die Felder <strong>Berichtstext, Berichtstitel, Einsatzleiter, Einsatzort</strong> und <strong>Mannschaftsst&auml;rke</strong> sind Freitextfelder.</p>';
            echo '<p>F&uuml;r die Felder <strong>Alarmierungsart, Einsatzart, Externe Einsatzmittel</strong> und <strong>Fahrzeuge</strong> wird eine kommagetrennte Liste erwartet.<br>Bisher unbekannte Eintr&auml;ge werden automatisch angelegt, die Einsatzart sollte nur ein einzelner Wert sein.</p>';
            echo '<p>Das Feld <strong>Einsatzende</strong> erwartet eine Datums- und Zeitangabe im Format <code>JJJJ-MM-TT hh:mm:ss</code> (z.B. 2014-04-21 21:48:06). Die Sekundenangabe ist optional.</p>';
            echo '<p>Das Feld <strong>Fehlalarm</strong> erwartet den Wert 1 (= ja) oder 0 (= nein). Es darf auch leer bleiben, was als 0 (= nein) zählt.</p>';

            // Felder matchen
            echo "<h3>Felder zuordnen</h3>";
            $this->renderMatchForm(array(
                'fields' => $felder,
                'nonce_action' => 'evw-import-wpe-import',
                'action_value' => $this->getActionAttribute('import_wpe')
            ));
        } elseif ($action == 'import_wpe') {
            // Nonce überprüfen
            check_admin_referer('evw-import-wpe-import');

            echo '<h3>Import</h3>';

            $wpe_felder = $this->getFields();
            if (empty($wpe_felder)) {
                Utilities::printError('Es wurden keine Felder in der Tabelle von wp-einsatz gefunden');
                return;
            }

            // nicht zu importierende Felder aussortieren
            $evw_felder = IncidentReport::getFields();
            $feld_mapping = $this->getMapping($wpe_felder, $evw_felder);

            // Prüfen, ob mehrere Felder das gleiche Zielfeld haben
            if (!$this->validateMapping($feld_mapping)) {
                $this->renderMatchForm(array(
                    'fields' => $wpe_felder,
                    'mapping' => $feld_mapping,
                    'nonce_action' => 'evw-import-wpe-import',
                    'action_value' => $this->getActionAttribute('import_wpe')
                ));
                return;
            }

            // Import starten
            echo '<p>Die Daten werden eingelesen, das kann einen Moment dauern.</p>';
            $this->import($feld_mapping);
        } else {
            echo "Unbekannte Aktion";
        }
    }

    /**
     * Gibt die Spaltennamen der wp-einsatz-Tabelle zurück
     * (ohne ID, Nr_Jahr und Nr_Monat)
     *
     * @return array Die Spaltennamen
     */
    private function getFields()
    {
        global $wpdb; /** @var wpdb $wpdb */

        $felder = array();
        foreach ($wpdb->get_col("DESC " . $this->tablename, 0) as $column_name) {
            // Unwichtiges ignorieren
            if ($column_name == 'ID' || $column_name == 'Nr_Jahr' || $column_name == 'Nr_Monat') {
                continue;
            }

            $felder[] = $column_name;
        }
        return $felder;
    }

    /**
     * Importiert Einsätze aus der wp-einsatz-Tabelle
     *
     * @param array $feld_mapping Zuordnung von wp-einsatz-Feldern auf Einsatzverwaltungsfelder
     */
    private function import($feld_mapping)
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $query = sprintf('SELECT ID,%s FROM %s ORDER BY Datum', implode(array_keys($feld_mapping), ','), $this->tablename);
        $wpe_einsaetze = $wpdb->get_results($query, ARRAY_A);

        if ($wpe_einsaetze === null) {
            Utilities::printError('Dieser Fehler sollte nicht auftreten, da hat der Entwickler Mist gebaut...');
            return;
        }

        if (empty($wpe_einsaetze)) {
            Utilities::printError('Die Datenbank lieferte keine Ergebnisse. Entweder sind in wp-einsatz keine Eins&auml;tze gespeichert oder es gab ein Problem bei der Abfrage.');
            Utilities::printInfo('Um ein Problem bei der Abfrage zu vermeiden, entfernen Sie bitte alle Umlaute und Sonderzeichen aus den Feldnamen in wp-einsatz.');
            return;
        }

        foreach ($wpe_einsaetze as $wpe_einsatz) {
            $meta_values = array();
            $einsatz_args = array();
            $einsatz_args['post_content'] = '';
            $einsatz_args['tax_input'] = array();

            foreach ($feld_mapping as $wpe_feld_name => $evw_feld_name) {
                if (!empty($evw_feld_name) && is_string($evw_feld_name)) {
                    $evw_terms = IncidentReport::getTerms();
                    if (array_key_exists($evw_feld_name, IncidentReport::getMetaFields())) {
                        // Wert gehört in ein Metafeld
                        $meta_values[$evw_feld_name] = $wpe_einsatz[$wpe_feld_name];
                    } elseif (array_key_exists($evw_feld_name, $evw_terms)) {
                        // Wert gehört zu einer Taxonomie
                        if (is_taxonomy_hierarchical($evw_feld_name)) {
                            // Bei hierarchischen Taxonomien muss die ID statt des Namens verwendet werden
                            $term = get_term_by('name', $wpe_einsatz[$wpe_feld_name], $evw_feld_name);
                            if ($term === false) {
                                // Term existiert in dieser Taxonomie noch nicht, neu anlegen
                                $newterm = wp_insert_term($wpe_einsatz[$wpe_feld_name], $evw_feld_name);
                                if (is_wp_error($newterm)) {
                                    Utilities::printError(
                                        sprintf(
                                            "Konnte %s '%s' nicht anlegen: %s",
                                            $evw_terms[$evw_feld_name],
                                            $wpe_einsatz[$wpe_feld_name],
                                            $newterm->get_error_message()
                                        )
                                    );
                                } else {
                                    // Anlegen erfolgreich, zurückgegebene ID verwenden
                                    $einsatz_args['tax_input'][$evw_feld_name] = $newterm['term_id'];
                                }
                            } else {
                                // Term existiert bereits, ID verwenden
                                $einsatz_args['tax_input'][$evw_feld_name] = $term->term_id;
                            }
                        } else {
                            // Name kann direkt verwendet werden
                            $einsatz_args['tax_input'][$evw_feld_name] = $wpe_einsatz[$wpe_feld_name];
                        }
                    } elseif (array_key_exists($evw_feld_name, IncidentReport::getPostFields())) {
                        // Wert gehört direkt zum Post
                        $einsatz_args[$evw_feld_name] = $wpe_einsatz[$wpe_feld_name];
                    } elseif ($evw_feld_name == '-') {
                        Utilities::printWarning("Feld '$wpe_feld_name' nicht zugeordnet");
                    } else {
                        Utilities::printError("Feld '$evw_feld_name' unbekannt");
                    }
                } else {
                    Utilities::printError("Feld '$evw_feld_name' ung&uuml;ltig");
                }
            }

            // Datum des Einsatzes prüfen
            $alarmzeit = date_create($einsatz_args['post_date']);
            if ($alarmzeit === false) {
                Utilities::printError(
                    sprintf('Konnte Datum vom Einsatz mit der ID %d nicht einlesen', $wpe_einsatz['ID'])
                );
                continue;
            }

            $einsatzjahr = date_format($alarmzeit, 'Y');
            $einsatznummer = Core::getNextEinsatznummer($einsatzjahr);
            $einsatz_args['post_name'] = $einsatznummer;
            $einsatz_args['post_type'] = 'einsatz';
            $einsatz_args['post_status'] = 'publish';
            $einsatz_args['post_date_gmt'] = get_gmt_from_date($einsatz_args['post_date']);
            $meta_values['einsatz_alarmzeit'] = date_format($alarmzeit, 'Y-m-d H:i');

            // Titel sicherstellen
            if (!array_key_exists('post_title', $einsatz_args)) {
                $einsatz_args['post_title'] = 'Einsatz';
            }
            $einsatz_args['post_title'] = wp_strip_all_tags($einsatz_args['post_title']);
            if (empty($einsatz_args['post_title'])) {
                $einsatz_args['post_title'] = 'Einsatz';
            }

            // Mannschaftsstärke validieren
            if (array_key_exists('einsatz_mannschaft', $meta_values)) {
                $meta_values['einsatz_mannschaft'] = sanitize_text_field($meta_values['einsatz_mannschaft']);
            }

            // Neuen Beitrag anlegen
            remove_action('save_post', 'einsatzverwaltung_save_postdata');
            $post_id = wp_insert_post($einsatz_args, true);
            if (is_wp_error($post_id)) {
                Utilities::printError('Konnte Einsatz nicht importieren: ' . $post_id->get_error_message());
            } else {
                Utilities::printInfo('Einsatz importiert, ID ' . $post_id);
                foreach ($meta_values as $mkey => $mval) {
                    update_post_meta($post_id, $mkey, $mval);
                }

                // Einsatznummer prüfen
                $gespeicherteEnr = get_post_field('post_name', $post_id);
                if ($gespeicherteEnr != $einsatznummer) {
                    Utilities::printWarning('WordPress hat diesem Einsatz nicht die vorgesehene Einsatznummer erteilt.<br>Verwendung des Werkzeugs <a href="'.admin_url('tools.php?page='.ToolEinsatznummernReparieren::EVW_TOOL_ENR_SLUG).'">Einsatznummern reparieren</a> wird empfohlen.');
                }
            }
            add_action('save_post', 'einsatzverwaltung_save_postdata');
        }

        Utilities::printSuccess('Der Import ist abgeschlossen');
        echo '<a href="edit.php?post_type=einsatz">Zu den Einsatzberichten</a>';
    }

    /**
     * @param array $felder
     * @param bool $quiet
     */
    private function checkForProblems($felder, $quiet = false)
    {
        foreach ($felder as $feld) {
            if (strpbrk($feld, 'äöüÄÖÜß/#')) {
                if (!$quiet) {
                    Utilities::printWarning(sprintf(
                        'Feldname %s enth&auml;lt Zeichen (z.B. Umlaute oder Sonderzeichen), die beim Import zu Problemen f&uuml;hren.<br>Bitte das Feld in den Einstellungen von wp-einsatz umbenennen, wenn Sie es importieren wollen.',
                        $feld
                    ));
                }
                $this->problematicFields[] = $feld;
            }
        }
    }
}
