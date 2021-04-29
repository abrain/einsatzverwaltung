<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

/**
 * Abstraction for sources that import from a file
 * @package abrain\Einsatzverwaltung\Import\Sources
 */
abstract class FileSource extends AbstractSource
{
    /**
     * The MIME type of the file that is required for the import
     * @var string
     */
    protected $mimeType;

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }
}
