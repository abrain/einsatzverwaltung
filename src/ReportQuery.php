<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Types\AlertingMethod;
use abrain\Einsatzverwaltung\Types\Unit;
use function count;
use function in_array;

/**
 * Class ReportQuery
 * @package abrain\Einsatzverwaltung
 */
class ReportQuery
{
    /**
     * @var int[]
     */
    private $alertingMethodIds;

    /**
     * Beinhaltet Post-IDs, die nicht im Ergebnis auftauchen sollen
     *
     * @var array
     */
    private $excludePostId;

    /**
     * The term IDs of the einsatzart taxonomy to filter for
     *
     * @var int[]
     */
    private $incidentTypeIds;

    /**
     * Zeigt an, ob als privat markierte Berichte mit abgefragt werden sollen
     *
     * @var bool
     */
    private $includePrivateReports;

    /**
     * Die maximale Anzahl an abzurufenden Berichten
     *
     * @var int
     */
    private $limit;

    /**
     * @var ReportStatus[]
     */
    private $onlyReportStatus;

    /**
     * Zeigt an, ob nur als besonders markierte Berichte abgefragt werden sollen
     *
     * @var bool
     */
    private $onlySpecialReports;

    /**
     * Gibt an, ob aufsteigend sortiert werden soll
     *
     * @var bool
     */
    private $orderAsc;

    /**
     * @var int[]
     */
    private $units;

    /**
     * @var int
     */
    private $year;

    /**
     * ReportQuery constructor.
     */
    public function __construct()
    {
        $this->resetQueryVars();
    }

    /**
     * Setzt die Abfragevariablen auf einen definierten Standardwert
     */
    public function resetQueryVars()
    {
        $this->excludePostId = array();
        $this->incidentTypeIds = [];
        $this->includePrivateReports = false;
        $this->limit = -1;
        $this->onlyReportStatus = [];
        $this->onlySpecialReports = false;
        $this->orderAsc = true;
        $this->units = [];
        $this->year = null;
    }

    /**
     * @return array
     */
    private function getDateQuery(): array
    {
        $dateQuery = array();

        if (is_numeric($this->year)) {
            if ($this->year < 0) {
                $currentYear = date('Y');
                $numberOfYears = abs(intval($this->year));
                for ($i = 0; $i < $numberOfYears && $i < $currentYear; $i++) {
                    $dateQuery[] = array('year' => $currentYear - $i);
                }
                $dateQuery['relation'] = 'OR';
            }

            if ($this->year > 0) {
                $dateQuery = array('year' => $this->year);
            }
        }

        return $dateQuery;
    }

    /**
     * @return array
     */
    private function getMetaQuery(): array
    {
        $metaQuery = [];

        if ($this->onlySpecialReports) {
            $metaQuery[] = ['key' => 'einsatz_special', 'value' => '1'];
        }

        if (!empty($this->onlyReportStatus)) {
            $conditions = [];
            if (in_array(ReportStatus::ACTUAL, $this->onlyReportStatus)) {
                $conditions[] = ['key' => 'einsatz_fehlalarm', 'value' => '0'];
                $conditions[] = ['key' => 'einsatz_fehlalarm', 'compare' => 'NOT EXISTS'];
            }
            if (in_array(ReportStatus::FALSE_ALARM, $this->onlyReportStatus)) {
                $conditions[] = ['key' => 'einsatz_fehlalarm', 'value' => '1'];
            }

            if (count($conditions) > 1) {
                $conditions['relation'] = 'OR';
                $metaQuery[] = $conditions;
            } else {
                $metaQuery[] = $conditions[0];
            }
        }

        if (count($metaQuery) > 1) {
            $metaQuery['relation'] = 'AND';
        }

        return $metaQuery;
    }

    /**
     * @return IncidentReport[]
     */
    public function getReports(): array
    {
        $postStatus = array('publish');
        if ($this->includePrivateReports) {
            $postStatus[] = 'private';
        }

        $postArgs = array(
            'date_query' => $this->getDateQuery(),
            'meta_query' => $this->getMetaQuery(),
            'tax_query' => $this->getTaxQuery(),
            'order' => $this->orderAsc ? 'ASC' : 'DESC',
            'orderby' => 'post_date',
            'post_status' => $postStatus,
            'post_type' => 'einsatz',
            'posts_per_page' => $this->limit,
        );

        if (!empty($this->excludePostId)) {
            $postArgs['post__not_in'] = $this->excludePostId;
        }

        $posts = get_posts($postArgs);

        // Make sure, array_map is satisfied
        if (empty($posts)) {
            $posts = array();
        }

        return array_map(function ($post) {
            return new IncidentReport($post);
        }, $posts);
    }

    /**
     * @return array
     */
    private function getTaxQuery(): array
    {
        $taxQuery = array();

        if (!empty($this->alertingMethodIds)) {
            $taxQuery[] = array('taxonomy' => AlertingMethod::getSlug(), 'terms' => $this->alertingMethodIds);
        }

        if (!empty($this->incidentTypeIds)) {
            $taxQuery[] = array('taxonomy' => 'einsatzart', 'terms' => $this->incidentTypeIds);
        }

        if (!empty($this->units)) {
            $taxQuery[] = array('taxonomy' => Unit::getSlug(), 'terms' => $this->units);
        }

        return $taxQuery;
    }

    /**
     * @param int[] $alertingMethodIds
     */
    public function setAlertingMethodIds(array $alertingMethodIds): void
    {
        $this->alertingMethodIds = $alertingMethodIds;
    }

    /**
     * @param array $postIds
     */
    public function setExcludePostIds(array $postIds)
    {
        $this->excludePostId = $postIds;
    }

    /**
     * @param int[] $incidentTypeIds
     */
    public function setIncidentTypeIds(array $incidentTypeIds)
    {
        $this->incidentTypeIds = $incidentTypeIds;
    }

    /**
     * @param bool $includePrivateReports
     */
    public function setIncludePrivateReports(bool $includePrivateReports)
    {
        $this->includePrivateReports = $includePrivateReports;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit)
    {
        if (is_numeric($limit)) {
            $this->limit = $limit;
        }
    }

    /**
     * Restrict the query to only contain reports with a certain status
     *
     * @param ReportStatus[] $onlyReportStatus
     */
    public function setOnlyReportStatus(array $onlyReportStatus): void
    {
        $this->onlyReportStatus = $onlyReportStatus;
    }

    /**
     * @param boolean $onlySpecialReports
     */
    public function setOnlySpecialReports(bool $onlySpecialReports)
    {
        $this->onlySpecialReports = $onlySpecialReports;
    }

    /**
     * @param boolean $orderAsc
     */
    public function setOrderAsc(bool $orderAsc)
    {
        $this->orderAsc = $orderAsc;
    }

    /**
     * @param int[] $units
     */
    public function setUnits(array $units)
    {
        $this->units = $units;
    }

    /**
     * @param int $year
     */
    public function setYear(int $year)
    {
        $this->year = $year;
    }
}
