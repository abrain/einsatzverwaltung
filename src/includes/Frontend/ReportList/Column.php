<?php
namespace abrain\Einsatzverwaltung\Frontend\ReportList;

/**
 * Represents a column in the report list
 *
 * @package abrain\Einsatzverwaltung\Frontend\ReportList
 */
class Column
{
    /**
     * Unique identifier of the column.
     *
     * @var string
     */
    private $identifier;

    /**
     * Name shown in the header of the table.
     *
     * @var string
     */
    private $name;

    /**
     * Longer name, usually shown on the Settings page (optional).
     * @var string
     */
    private $longName;

    /**
     * Whether browsers should be forbidden to add a line break after whitespace in the content of the column.
     *
     * @var bool
     */
    private $noWrap;

    /**
     * Column constructor.
     *
     * @param string $identifier
     * @param string $name
     * @param string $longName
     * @param bool $noWrap
     */
    public function __construct($identifier, $name, $longName = '', $noWrap = false)
    {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->longName = $longName;
        $this->noWrap = $noWrap;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getNameForSettings(): string
    {
        if (empty($this->longName)) {
            return $this->name;
        }

        return $this->longName;
    }

    /**
     * @return bool
     */
    public function isNoWrap(): bool
    {
        return $this->noWrap;
    }
}
