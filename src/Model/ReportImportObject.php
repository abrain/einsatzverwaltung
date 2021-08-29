<?php
namespace abrain\Einsatzverwaltung\Model;

use abrain\Einsatzverwaltung\Types\Report;
use DateTimeImmutable;
use DateTimeZone;
use function get_date_from_gmt;

/**
 * Generates arguments for inserting a new Report, based on its properties
 */
class ReportImportObject
{
    /**
     * @var string
     */
    private $content;

    /**
     * @var DateTimeImmutable
     */
    private $endTime;

    /**
     * @var string
     */
    private $location;

    /**
     * @var DateTimeImmutable
     */
    private $startDate;

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
        $this->startDate = $startDate;
    }

    /**
     * @param bool $publish
     *
     * @return array
     */
    public function getInsertArgs(bool $publish = false): array
    {
        $args = array(
            'post_type' => Report::getSlug(),
            'post_title' => $this->title,
            'meta_input' => array()
        );

        $postDateUTC = $this->startDate->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        if ($publish) {
            $args['post_status'] = 'publish';
            $args['post_date'] = get_date_from_gmt($postDateUTC);
            $args['post_date_gmt'] = $postDateUTC;
        } else {
            $args['post_status'] = 'draft';
            $args['meta_input']['_einsatz_timeofalerting'] = get_date_from_gmt($postDateUTC);
        }

        if ($this->endTime) {
            $endDateUTC = $this->endTime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $args['meta_input']['einsatz_einsatzende'] = get_date_from_gmt($endDateUTC);
        }

        if (!empty($this->content)) {
            $args['post_content'] = $this->content;
        }

        if ($this->location) {
            $args['meta_input']['einsatz_einsatzort'] = $this->location;
        }

        return $args;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @param DateTimeImmutable $endTime
     */
    public function setEndTime(DateTimeImmutable $endTime): void
    {
        $this->endTime = $endTime;
    }

    /**
     * @param string $location
     */
    public function setLocation(string $location): void
    {
        $this->location = $location;
    }
}
