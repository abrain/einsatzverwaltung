<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Exceptions\ImportCheckException;
use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ReportNumberController;
use function array_count_values;
use function array_key_exists;
use function esc_html;
use function filter_input;
use function in_array;
use function is_string;
use function sprintf;
use const FILTER_SANITIZE_STRING;
use const INPUT_POST;

/**
 * Helper to map fields of an import source to the fields of the plugin
 * @package abrain\Einsatzverwaltung\Import
 */
class MappingHelper
{
    /**
     * @param AbstractSource $source
     * @param array $ownFields Internal field names
     *
     * @return array
     * @throws ImportCheckException
     */
    public function getMapping(AbstractSource $source, array $ownFields)
    {
        $mapping = [];

        foreach ($source->getFields() as $sourceField) {
            $ownField = filter_input(INPUT_POST, $source->getInputName($sourceField), FILTER_SANITIZE_STRING);

            // Skip source fields that are not mapped to a field
            if (empty($ownField) || !is_string($ownField) || $ownField === '-') {
                continue;
            }

            if (!array_key_exists($ownField, $ownFields)) {
                throw new ImportCheckException(sprintf(__('Unknown field: %s', 'einsatzverwaltung'), $ownField));
            }

            $mapping[$sourceField] = $ownField;
        }

        // The source may give a mandatory mapping for certain fields
        foreach ($source->getAutoMatchFields() as $sourceFieldAuto => $ownFieldAuto) {
            $mapping[$sourceFieldAuto] = $ownFieldAuto;
        }

        return $mapping;
    }

    /**
     * Prüft, ob das Mapping stimmig ist und gibt Warnungen oder Fehlermeldungen aus
     *
     * @param array $mapping Das zu prüfende Mapping
     * @param AbstractSource $source
     *
     * @throws ImportCheckException
     */
    public function validateMapping(array $mapping, AbstractSource $source)
    {
        // Pflichtfelder prüfen
        if (!in_array('post_date', $mapping)) {
            throw new ImportCheckException('Pflichtfeld Alarmzeit wurde nicht zugeordnet');
        }

        $unmatchableFields = $source->getUnmatchableFields();
        $autoMatchFields = $source->getAutoMatchFields();
        if (ReportNumberController::isAutoIncidentNumbers()) {
            $unmatchableFields[] = 'einsatz_incidentNumber';
        }
        foreach ($unmatchableFields as $unmatchableField) {
            if (in_array($unmatchableField, $mapping) && !in_array($unmatchableField, $autoMatchFields)) {
                throw new ImportCheckException(sprintf(
                    'Feld %s kann nicht f&uuml;r ein zu importierendes Feld als Ziel angegeben werden',
                    esc_html($unmatchableField)
                ));
            }
        }

        // Mehrfache Zuweisungen prüfen
        foreach (array_count_values($mapping) as $ownField => $count) {
            if ($count > 1) {
                throw new ImportCheckException(sprintf(
                    'Feld %s kann nicht f&uuml;r mehr als ein zu importierendes Feld als Ziel angegeben werden',
                    IncidentReport::getFieldLabel($ownField)
                ));
            }
        }
    }
}
