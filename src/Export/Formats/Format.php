<?php
namespace abrain\Einsatzverwaltung\Export\Formats;

/**
 * Interface für Exportformate
 */
interface Format
{
    /**
     * Gibt den Namen von diesem Exportformat zurück.
     *
     * @return  string
     */
    public function getTitle(): string;

    /**
     * Gibt die Optionen im HTML-Format für dieses Exportformat aus.
     *
     * @return  void
     */
    public function renderOptions();

    /**
     * Erwartet Optionen zum Eingrenzen der zu exportierenden Einsatzberichte.
     * Die Inhalte im Array müssen überprüft werden, da es sich um Eingaben vom
     * Benutzer handelt.
     *
     * @param string $startDate Jahr und Monat im Format YYYY-MM oder 0
     * @param string $endDate Jahr und Monat im Format YYYY-MM oder 0
     *
     * @return  void
     */
    public function setFilters(string $startDate, string $endDate);
    
    /**
     * Erwartet ein Array mit Optionen zum Konfigurieren des Exportformats.
     * Die Inhalte im Array müssen überprüft werden, da es sich um Eingaben vom
     * Benutzer handelt.
     *
     * @param   array   $options
     * @return  void
     */
    public function setOptions(array $options);
    
    /**
     * Gibt den Namen der Datei zurück, die beim Export erstellt wird.
     *
     * @return  string
     */
    public function getFilename(): string;
    
    /**
     * Gibt den Inhalt der Datei, die beim Export erstellt wird, aus.
     *
     * @return  void
     */
    public function export();
}
