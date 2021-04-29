<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Exceptions\FileReadException;
use function array_key_exists;
use function fclose;
use function feof;
use function fgetcsv;
use function fopen;
use function is_array;
use function sprintf;

/**
 * Makes CSV files accessible line by line
 * @package abrain\Einsatzverwaltung\Import
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
     * @param int[] $columns Array of 0-based column indices which should be returned. Empty array gets all columns.
     * @param int $offset How many lines to skip before reading, default 0.
     *
     * @return string[][]
     * @throws FileReadException
     */
    public function getLines(int $numLines, array $columns = [], int $offset = 0): array
    {
        $handle = fopen($this->filePath, 'r');
        if ($handle === false) {
            $message = sprintf(__('Could not open file %s', 'einsatzverwaltung'), $this->filePath);
            throw new FileReadException($message);
        }

        // If an offset is defines, some lines should be skipped
        if ($offset > 0) {
            // The CSV parsing has to be used here as well, as line breaks could appear inside field delimiters
            $this->readLines($handle, $offset, []);
        }

        $lines = $this->readLines($handle, $numLines, $columns);

        fclose($handle);
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
            $line = fgetcsv($handle, 0, $this->delimiter, $this->enclosure);
            $linesRead++;

            // End of file reached?
            if ($line === false && feof($handle)) {
                break;
            }

            // Problem while reading the file
            if (empty($line)) {
                throw new FileReadException();
            }

            // Empty line in the file, skip this
            if (is_array($line) && $line[0] == null) {
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
                // If the line has less columns than expected, the value will be an empty string
                $filteredLine[] = array_key_exists($columnIndex, $line) ? $line[$columnIndex] : '';
            }
            $lines[] = $filteredLine;
        }

        return $lines;
    }
}
