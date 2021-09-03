<?php
namespace abrain\Einsatzverwaltung\DataAccess;

use abrain\Einsatzverwaltung\Model\ReportInsertObject;
use abrain\Einsatzverwaltung\Types\IncidentType;
use abrain\Einsatzverwaltung\Types\Report;
use DateTimeImmutable;
use DateTimeZone;
use WP_Error;
use WP_Term;
use function get_date_from_gmt;
use function get_term_by;
use function is_wp_error;
use function wp_insert_post;
use function wp_insert_term;

/**
 * Inserts incident reports into the database
 */
class ReportInserter
{
    /**
     * @var bool
     */
    private $publishReports;

    /**
     * @param bool $publishReports If the reports should be published. Will remain drafts if false.
     */
    public function __construct(bool $publishReports = false)
    {
        $this->publishReports = $publishReports;
    }

    /**
     * Generates arguments for inserting a new Report, based on the import object's properties
     *
     * @param ReportInsertObject $reportImportObject
     *
     * @return array|WP_Error
     */
    public function getInsertArgs(ReportInsertObject $reportImportObject)
    {
        $args = array(
            'post_type' => Report::getSlug(),
            'post_title' => $reportImportObject->getTitle(),
            'meta_input' => array(),
            'tax_input' => array()
        );

        $postDateUTC = $this->getUtcDateString($reportImportObject->getStartDateTime());

        if ($this->publishReports) {
            $args['post_status'] = 'publish';
            $args['post_date'] = get_date_from_gmt($postDateUTC);
            $args['post_date_gmt'] = $postDateUTC;
        } else {
            $args['post_status'] = 'draft';
            $args['meta_input']['_einsatz_timeofalerting'] = get_date_from_gmt($postDateUTC);
        }

        $endTime = $reportImportObject->getEndDateTime();
        if (!empty($endTime)) {
            $endDateUTC = $this->getUtcDateString($endTime);
            $args['meta_input']['einsatz_einsatzende'] = get_date_from_gmt($endDateUTC);
        }

        $content = $reportImportObject->getContent();
        if (!empty($content)) {
            $args['post_content'] = $content;
        }

        $keyword = $reportImportObject->getKeyword();
        if (!empty($keyword)) {
            $termId = $this->getOrCreateIncidentTypeTerm($keyword);
            if (is_wp_error($termId)) {
                return $termId;
            }
            $args['tax_input'][IncidentType::getSlug()] = [$termId];
        }

        $location = $reportImportObject->getLocation();
        if (!empty($location)) {
            $args['meta_input']['einsatz_einsatzort'] = $location;
        }

        return $args;
    }

    /**
     * @param string $keyword
     *
     * @return int|WP_Error
     */
    private function getOrCreateIncidentTypeTerm(string $keyword)
    {
        $term = get_term_by('name', $keyword, IncidentType::getSlug());

        if ($term instanceof WP_Term) {
            return $term->term_id;
        } elseif (is_wp_error($term)) {
            return $term;
        }

        // The term does not yet exist, create it
        $newTerm = wp_insert_term($keyword, IncidentType::getSlug());
        return is_wp_error($newTerm) ? $newTerm : intval($newTerm['term_id']);
    }

    /**
     * @param DateTimeImmutable $dateTime
     *
     * @return string
     */
    private function getUtcDateString(DateTimeImmutable $dateTime): string
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * @param ReportInsertObject $importObject
     *
     * @return int|WP_Error The post ID on success, WP_Error on failure.
     */
    public function insertReport(ReportInsertObject $importObject)
    {
        $insertArgs = $this->getInsertArgs($importObject);
        if (is_wp_error($insertArgs)) {
            return $insertArgs;
        }

        return wp_insert_post($insertArgs, true);
    }
}
