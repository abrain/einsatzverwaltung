<?php
namespace abrain\Einsatzverwaltung\Model;

use DateTimeImmutable;

/**
 * Represents the properties of an incident report that should be inserted into the database
 */
class ReportInsertObject
{
    /**
     * @var string
     */
    private $content;

    /**
     * @var DateTimeImmutable|null
     */
    private $endDateTime;

    /**
     * @var string
     */
    private $keyword;

    /**
     * @var string
     */
    private $location;

    /**
     * @var string[]
     */
    private $resources;

    /**
     * @var DateTimeImmutable
     */
    private $startDateTime;

    /**
     * @var string
     */
    private $title;

    /**
     * @param DateTimeImmutable $startDate
     * @param string $title
     */
    public function __construct(DateTimeImmutable $startDate, string $title)
    {
        $this->title = $title;
        if (empty($title)) {
            $this->title = __('Incident', 'einsatzverwaltung');
        }
        $this->startDateTime = $startDate;
        $this->content = '';
        $this->keyword = '';
        $this->location = '';
        $this->resources = [];
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getEndDateTime(): ?DateTimeImmutable
    {
        return $this->endDateTime;
    }

    /**
     * @return string
     */
    public function getKeyword(): string
    {
        return $this->keyword;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @return string[]
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getStartDateTime(): DateTimeImmutable
    {
        return $this->startDateTime;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @param DateTimeImmutable $endDateTime
     */
    public function setEndDateTime(DateTimeImmutable $endDateTime): void
    {
        $this->endDateTime = $endDateTime;
    }

    /**
     * @param string $keyword
     */
    public function setKeyword(string $keyword): void
    {
        $this->keyword = $keyword;
    }

    /**
     * @param string $location
     */
    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    /**
     * @param string[] $resources
     */
    public function setResources(array $resources): void
    {
        $this->resources = $resources;
    }
}
