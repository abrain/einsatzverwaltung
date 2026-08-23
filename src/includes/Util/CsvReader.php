<?php
namespace abrain\Einsatzverwaltung\Util;

use abrain\Einsatzverwaltung\Exceptions\FileReadException;
use function array_key_exists;
use function fclose;
use function feof;
use function fgetcsv;
use function fopen;
use function sprintf;

/**
 * Makes CSV files accessible line by line
 * @package abrain\Einsatzverwaltung\Util
 */
class CsvReader
{
    /**
     * @var string
     */
    private $delimiter;

    /**
     * @var string
     */
    private $enclosure;

    /**
     * @var string
     */
    private $filePath;

    /**
     * CsvReader constructor.
     *
     * @param string $filePath
     * @param string $delimiter
     * @param string $enclosure
     */
    public function __construct(string $filePath, string $delimiter, string $enclosure)
    {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->filePath = $filePath;
    }

    /**
     * @param int $numLines How many lines to read.
     * @param int[] $requestedColumnIndices Array of 0-based column indices which should be returned. Empty array gets all columns.
     * @param int $offset How many lines to skip before reading, default 0.
     * @param array $fieldMap Maps column index to column name. If provided, resulting lines will be indexed with the names. Default empty.
     *
     * @return string[][]
     * @throws FileReadException
     */
    public function getLines(int $numLines, array $requestedColumnIndices = [], int $offset = 0, array $fieldMap = []): array
    {
        $handle = fopen($this->filePath, 'r');
        if ($handle === false) {
            // translators: 1: file path
            $message = sprintf(__('Could not open file %s', 'einsatzverwaltung'), $this->filePath);
            throw new FileReadException($message);
        }

        try {
            // If an offset is defined, some lines should be skipped
            if ($offset > 0) {
                // The CSV parsing has to be used here as well, as line breaks could appear inside field delimiters
                $this->readLines($handle, $offset, []);
            }

            $lines = $this->readLines($handle, $numLines, $requestedColumnIndices);
        } finally {
            fclose($handle);
        }

        // In case a field map is provided, make the arrays for each line associative
        if (!empty($fieldMap)) {
            $columnIndices = empty($requestedColumnIndices) ? range(0, count($fieldMap) - 1) : $requestedColumnIndices;
            $associativeLines = [];
            foreach ($lines as $line) {
                $associativeLine = [];
                foreach ($columnIndices as $lineColumnIndex => $sourceColumnIndex) {
                    $associativeLine[$fieldMap[$sourceColumnIndex]] = $line[$lineColumnIndex];
                }
                $associativeLines[] = $associativeLine;
            }
            $lines = $associativeLines;
        }

        return $lines;
    }

    /**
     * @param resource $handle
     * @param int $numLines
     * @param array $columns
     *
     * @return array
     * @throws FileReadException
     */
    private function readLines($handle, int $numLines, array $columns): array
    {
        $linesRead = 0;
        $lines = array();
        while ($numLines === 0 || $linesRead < $numLines) {
            $line = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, '\\');

            // Error while reading, most likely EOF
            if ($line === false) {
                break;
            }

            $linesRead++;

            // Empty line in the file, skip this
            if ($line === [null]) {
                continue;
            }

            // Return entire line when all columns have been requested
            if (empty($columns)) {
                $lines[] = $line;
                continue;
            }

            // Return only the requested columns
            $filteredLine = array();
            foreach ($columns as $columnIndex) {
                // If the line has fewer columns than expected, the value will be an empty string
                $filteredLine[] = array_key_exists($columnIndex, $line) ? $line[$columnIndex] : '';
            }
            $lines[] = $filteredLine;
        }

        if (($numLines === 0 || $linesRead < $numLines) && feof($handle) === false) {
            throw new FileReadException(sprintf(
                // translators: 1: number of lines
                _n('Reading was aborted after %d line', 'Reading was aborted after %d lines', $linesRead, 'einsatzverwaltung'),
                $linesRead
            ));
        }

        return $lines;
    }
}
