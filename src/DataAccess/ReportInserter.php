<?php
namespace abrain\Einsatzverwaltung\DataAccess;

use abrain\Einsatzverwaltung\Model\ReportInsertObject;
use abrain\Einsatzverwaltung\Types\ExtEinsatzmittel;
use abrain\Einsatzverwaltung\Types\IncidentType;
use abrain\Einsatzverwaltung\Types\Report;
use abrain\Einsatzverwaltung\Types\Vehicle;
use DateTimeImmutable;
use DateTimeZone;
use WP_Error;
use WP_Term;
use function array_diff;
use function array_map;
use function array_merge;
use function array_values;
use function get_date_from_gmt;
use function get_term;
use function get_term_by;
use function get_terms;
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
            'meta_input' => array('einsatz_special' => 0),
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
            $incidentTypeTerm = $this->getOrCreateIncidentTypeTerm($keyword);
            if (is_wp_error($incidentTypeTerm)) {
                return $incidentTypeTerm;
            }
            $args['tax_input'][IncidentType::getSlug()] = [$incidentTypeTerm->term_id];
        }

        $location = $reportImportObject->getLocation();
        if (!empty($location)) {
            $args['meta_input']['einsatz_einsatzort'] = $location;
        }

        $resources = $reportImportObject->getResources();
        if (!empty($resources)) {
            $args['tax_input'][ExtEinsatzmittel::getSlug()] = [];
            $args['tax_input'][Vehicle::getSlug()] = [];

            $resourceTerms = $this->getResourceTerms($resources);
            if (is_wp_error($resourceTerms)) {
                return $resourceTerms;
            }
            foreach ($resourceTerms as $resourceTerm) {
                $args['tax_input'][$resourceTerm->taxonomy][] = $resourceTerm->term_id;
            }
        }

        return $args;
    }

    /**
     * @param string $keyword
     *
     * @return WP_Error|WP_Term
     */
    private function getOrCreateIncidentTypeTerm(string $keyword)
    {
        $taxonomy = IncidentType::getSlug();
        $term = get_term_by('name', $keyword, $taxonomy);

        if ($term !== false) {
            return $term;
        }

        $termsByAlias = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'meta_query' => [
                ['key' => 'altname', 'value' => $keyword]
            ]
        ]);
        if (!empty($termsByAlias)) {
            return $termsByAlias[0];
        }

        // The term does not yet exist, create it
        $newTerm = wp_insert_term($keyword, $taxonomy);
        return is_wp_error($newTerm) ? $newTerm : get_term($newTerm['term_id'], $taxonomy);
    }

    /**
     * @param string[] $names
     *
     * @return WP_Error|WP_Term[]
     */
    private function getResourceTerms(array $names)
    {
        $resourcesByName = get_terms([
            'name' => $names,
            'taxonomy' => [Vehicle::getSlug(), ExtEinsatzmittel::getSlug()],
            'hide_empty' => false
        ]);

        if (is_wp_error($resourcesByName)) {
            return $resourcesByName;
        }

        // Determine which resources have been found by name and continue with the rest
        $resourceNames = array_map(function ($resource) {
            return $resource->name;
        }, $resourcesByName);
        $remainingNames = array_values(array_diff($names, $resourceNames));

        if (empty($remainingNames)) {
            return $resourcesByName;
        }

        $resourcesByAlias = get_terms([
            'taxonomy' => [Vehicle::getSlug(), ExtEinsatzmittel::getSlug()],
            'hide_empty' => false,
            'meta_query' => [
                ['key' => 'altname', 'compare' => 'IN', 'value' => $remainingNames]
            ]
        ]);
        if (is_wp_error($resourcesByAlias)) {
            return $resourcesByAlias;
        }

        return array_merge($resourcesByName, $resourcesByAlias);
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
