<?php
namespace abrain\Einsatzverwaltung;

use wpdb;

/**
 * Importiert Daten aus wp-einsatz
 */
class ToolImportWpEinsatz
{

    const EVW_TOOL_WPE_SLUG = 'einsatzvw-tool-wpe';
    const EVW_TOOL_WPE_DATE_COLUMN = 'Datum';
    const EVW_TOOL_WPE_INPUT_NAME_PREFIX = 'evw_wpe_';

    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->addHooks();
    }

    private function addHooks()
    {
        add_action('admin_menu', array($this, 'addToolToMenu'));
    }

    /**
     * Fügt das Werkzeug für wp-einsatz zum Menü hinzu
     */
    public function addToolToMenu()
    {
        add_management_page(
            'Import aus wp-einsatz',
            'Import aus wp-einsatz',
            'manage_options',
            self::EVW_TOOL_WPE_SLUG,
            array($this, 'renderToolPage')
        );
    }

    /**
     * Generiert den Inhalt der Werkzeugseiten
     */
    public function renderToolPage()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        echo '<div class="wrap">';
        echo '<h2>Import aus wp-einsatz</h2>';

        echo '<p>Dieses Werkzeug importiert Einsätze aus wp-einsatz.</p>';

        // Existenz der wp-einsatz Datenbank feststellen
        $tablename = $wpdb->prefix . "einsaetze";
        if ($wpdb->get_var("SHOW TABLES LIKE '$tablename'") != $tablename) {
            Utilities::printError('Die Tabelle, in der wp-einsatz seine Daten speichert, konnte nicht gefunden werden.');
        } else {
            if (array_key_exists('submit', $_POST) &&
                array_key_exists('aktion', $_POST) &&
                $_POST['aktion'] == 'analyse'
            ) {
                // Nonce überprüfen
                check_admin_referer('evw-import-wpe-analyse');

                // Datenbank analysieren
                echo "<h3>Analyse</h3>";
                echo "<p>Die Daten von wp-einsatz werden analysiert...</p>";
                $felder = $this->getWpeFelder($tablename);
                if (empty($felder)) {
                    Utilities::printError('Es wurden keine Felder in der Tabelle gefunden');
                    return;
                }

                Utilities::printSuccess('Es wurden folgende Felder gefunden: ' . implode($felder, ', '));

                // Auf Pflichtfelder prüfen
                if (!in_array(self::EVW_TOOL_WPE_DATE_COLUMN, $felder)) {
                    echo '<br>';
                    Utilities::printError('Das Feld "'.self::EVW_TOOL_WPE_DATE_COLUMN.'" konnte nicht in der Datenbank gefunden werden!');
                    return;
                }

                // Einsätze zählen
                $anzahl_einsaetze = $wpdb->get_var("SELECT COUNT(*) FROM $tablename");
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
                echo '<h3>Hinweise zu den Daten</h3>';
                echo '<p>Die Felder <strong>Berichtstext, Einsatzleiter, Einsatzort</strong> und <strong>Einsatzstichwort</strong> sind Freitextfelder.</p>';
                echo '<p>F&uuml;r die Felder <strong>Alarmierungsart, Einsatzart, Externe Einsatzmittel</strong> und <strong>Fahrzeuge</strong> wird eine kommagetrennte Liste erwartet.<br>Bisher unbekannte Eintr&auml;ge werden automatisch angelegt, die Einsatzart sollte nur ein einzelner Wert sein.</p>';
                echo '<p>Das Feld <strong>Einsatzende</strong> erwartet eine Datums- und Zeitangabe im Format <code>JJJJ-MM-TT hh:mm:ss</code> (z.B. 2014-04-21 21:48:06). Die Sekundenangabe ist optional.</p>';
                echo '<p>Das Feld <strong>Fehlalarm</strong> erwartet den Wert 1 (= ja) oder 0 (= nein). Es darf auch leer bleiben, was als 0 (= nein) zählt.</p>';
                echo '<p>Das Feld <strong>Mannschaftsst&auml;rke</strong> erwartet eine Zahl größer oder gleich 0. Es darf auch leer bleiben.</p>';

                // Felder matchen
                echo "<h3>Felder zuordnen</h3>";
                $this->renderMatchForm($felder);
            } elseif (
                array_key_exists('submit', $_POST) &&
                array_key_exists('aktion', $_POST) &&
                $_POST['aktion'] == 'import_wpe'
            ) {
                // Nonce überprüfen
                check_admin_referer('evw-import-wpe-import');

                echo '<h3>Import</h3>';

                $wpe_felder = $this->getWpeFelder($tablename);
                if (empty($wpe_felder)) {
                    Utilities::printError('Es wurden keine Felder in der Tabelle von wp-einsatz gefunden');
                    return;
                }

                // nicht zu importierende Felder aussortieren
                $feld_mapping = array();
                foreach ($wpe_felder as $wpe_feld) {
                    $index = self::EVW_TOOL_WPE_INPUT_NAME_PREFIX . strtolower($wpe_feld);
                    if (array_key_exists($index, $_POST)) {
                        $evw_feld_name = $_POST[$index];
                        if (!empty($evw_feld_name) && is_string($evw_feld_name) && $evw_feld_name != '-') {
                            if (array_key_exists($evw_feld_name, Core::getFields())) {
                                $feld_mapping[$wpe_feld] = $evw_feld_name;
                            } else {
                                Utilities::printWarning("Unbekanntes Feld: $evw_feld_name");
                            }
                        }
                    }
                }
                $feld_mapping[self::EVW_TOOL_WPE_DATE_COLUMN] = 'post_date';

                // Prüfen, ob mehrere Felder das gleiche Zielfeld haben
                $value_count = array_count_values($feld_mapping);
                foreach ($value_count as $zielfeld => $anzahl) {
                    if ($anzahl > 1) {
                        $evw_felder = Core::getFields();
                        Utilities::printError("Feld $evw_felder[$zielfeld] kann nur f&uuml;r ein wp-einsatz-Feld als Importziel angegeben werden");
                        $this->renderMatchForm($wpe_felder, $feld_mapping);
                        return;
                    }
                }

                // Import starten
                echo '<p>Die Daten werden eingelesen, das kann einen Moment dauern.</p>';
                $this->import($tablename, $feld_mapping);
            } else {
                Utilities::printSuccess('Die Tabelle, in der wp-einsatz seine Daten speichert, wurde gefunden. Analyse jetzt starten?');
                echo '<form method="post">';
                echo '<input type="hidden" name="aktion" value="analyse" />';
                wp_nonce_field('evw-import-wpe-analyse');
                submit_button('Analyse starten');
                echo '</form>';
            }
        }
    }

    /**
     * Gibt das Formular für die Zuordnung zwischen zu importieren Feldern und denen von Einsatzverwaltung aus
     *
     * @param array $felder Liste der Feldnamen aus wp-einsatz
     * @param array $mapping Zuordnung von wp-einsatz-Feldern auf Einsatzverwaltungsfelder
     */
    private function renderMatchForm($felder, $mapping = array())
    {
        echo '<form method="post">';
        wp_nonce_field('evw-import-wpe-import');
        echo '<input type="hidden" name="aktion" value="import_wpe" />';
        echo '<table class="evw_match_fields"><tr><th>Feld in wp-einsatz</th><th>Feld in Einsatzverwaltung</th></tr><tbody>';
        foreach ($felder as $feld) {
            echo '<tr><td><strong>' . $feld . '</strong></td><td>';
            if ($feld == self::EVW_TOOL_WPE_DATE_COLUMN) {
                echo 'wird automatisch zugeordnet';
            } else {
                // Auf problematische Zeichen prüfen
                if (strpbrk($feld, 'äöüÄÖÜß/#')) {
                    Utilities::printWarning('Feldname enth&auml;lt Zeichen (z.B. Umlaute oder Sonderzeichen), die beim Import zu Problemen f&uuml;hren.<br>Bitte das Feld in den Einstellungen von wp-einsatz umbenennen, wenn Sie es importieren wollen.');
                } else {
                    $selected = '-';
                    if (!empty($mapping) && array_key_exists($feld, $mapping) && !empty($mapping[$feld])) {
                        $selected = $mapping[$feld];
                    }
                    $this->dropdownEigeneFelder(self::EVW_TOOL_WPE_INPUT_NAME_PREFIX . strtolower($feld), $selected);
                }
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
        submit_button('Import starten');
        echo '</form>';
    }

    /**
     * Gibt ein Auswahlfeld zur Zuordnung der Felder in Einsatzverwaltung aus
     *
     * @param string $name Name des Dropdownfelds im Formular
     * @param string $selected Wert der ausgewählten Option
     */
    private function dropdownEigeneFelder($name, $selected = '-')
    {
        $felder = Core::getFields();

        // Felder, die automatisch beschrieben werden, nicht zur Auswahl stellen
        unset($felder['post_date']);
        unset($felder['post_name']);

        // Sortieren und ausgeben
        asort($felder);
        $string = '';
        $string .= '<select name="' . $name . '">';
        $string .= '<option value="-"' . ($selected == '-' ? ' selected="selected"' : '') . '>nicht importieren</option>';
        foreach ($felder as $slug => $feldname) {
            $string .= '<option value="' . $slug . '"' . ($selected == $slug ? ' selected="selected"' : '') . '>' . $feldname . '</option>';
        }
        $string .= '</select>';

        echo $string;
    }

    /**
     * Gibt die Spaltennamen der wp-einsatz-Tabelle zurück
     * (ohne ID, Nr_Jahr und Nr_Monat)
     *
     * @param string $tablename Tabellenname der wp-einsatz-Tabelle
     *
     * @return array Die Spaltennamen
     */
    private function getWpeFelder($tablename)
    {
        global $wpdb; /** @var wpdb $wpdb */

        $felder = array();
        foreach ($wpdb->get_col("DESC " . $tablename, 0) as $column_name) {
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
     * @param string $tablename Tabellenname der wp-einsatz-Tabelle
     * @param array $feld_mapping Zuordnung von wp-einsatz-Feldern auf Einsatzverwaltungsfelder
     */
    private function import($tablename, $feld_mapping)
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $query = sprintf('SELECT ID,%s FROM %s ORDER BY %s', implode(array_keys($feld_mapping), ','), $tablename, self::EVW_TOOL_WPE_DATE_COLUMN);
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
                    $evw_terms = Core::getTerms();
                    if (array_key_exists($evw_feld_name, Core::getMetaFields())) {
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
                    } elseif (array_key_exists($evw_feld_name, Core::getPostFields())) {
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
}
