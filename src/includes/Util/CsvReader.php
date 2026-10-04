<?php
namespace abrain\Einsatzverwaltung\Util;

use abrain\Einsatzverwaltung\Exceptions\FileReadException;
use function array_key_exists;
use function fclose; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Used for temp file
use function feof;
use function fgetcsv;
use function fopen; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Used for temp file
use function rewind;
use function fwrite; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Used for temp file
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
        $handle = $this->openFile();

        try {
            // If an offset is defined, some lines should be skipped
            if ($offset > 0) {
                // The CSV parsing has to be used here as well, as line breaks could appear inside field delimiters
                $this->readLines($handle, $offset, []);
            }

            $lines = $this->readLines($handle, $numLines, $requestedColumnIndices);
        } finally {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Used for temp file
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
     * Read file with the WP_Filesystem methods and copy the contents into an in-memory stream.
     *
     * @throws FileReadException
     */
    private function openFile()
    {
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if (!$wp_filesystem->exists($this->filePath) || !$wp_filesystem->is_readable($this->filePath)) {
            throw new FileReadException(sprintf(
                // translators: 1: file path
                __('Could not open file %s', 'einsatzverwaltung'),
                $this->filePath
            ));
        }

        $content = $wp_filesystem->get_contents($this->filePath);
        if ($content === false) {
            throw new FileReadException(sprintf(
                // translators: 1: file path
                __('Could not read file %s', 'einsatzverwaltung'),
                $this->filePath
            ));
        }

        $handle = fopen('php://temp/', 'r+');
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Used for temp file
        fwrite($handle, $content);
        rewind($handle);

        return $handle;
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
