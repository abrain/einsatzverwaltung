<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Model\IncidentReport;

/**
 * Class ReportQuery
 * @package abrain\Einsatzverwaltung
 */
class ReportQuery
{
    /**
     * Beinhaltet Post-IDs, die nicht im Ergebnis auftauchen sollen
     *
     * @var array
     */
    private $excludePostId;

    /**
     * Die Term-ID der Einsatzart, nach der gefiltert werden soll
     *
     * @var int
     */
    private $incidentTypeId = 0;

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
        $this->initQueryVars();
    }

    /**
     * Setzt die Abfragevariablen auf einen definierten Standardwert
     */
    private function initQueryVars()
    {
        $this->excludePostId = array();
        $this->includePrivateReports = false;
        $this->limit = -1;
        $this->onlySpecialReports = false;
        $this->orderAsc = true;
    }

    /**
     * @return array
     */
    private function getDateQuery()
    {
        $dateQuery = array();

        if (is_numeric($this->year)) {
            if ($this->year < 0) {
                $currentYear = date('Y');
                for ($i = 0; $i < abs(intval($this->year)) && $i < $currentYear; $i++) {
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
    private function getMetaQuery()
    {
        $metaQuery = array();

        if ($this->onlySpecialReports) {
            $metaQuery[] = array('key' => 'einsatz_special', 'value' => '1');
        }

        if (!empty($this->units)) {
            $unitSubQuery = array('relation' => 'OR');
            foreach ($this->units as $unit) {
                $unitSubQuery[] = array('key' => '_evw_unit', 'value' => $unit);
            }
            $metaQuery[] = $unitSubQuery;
        }

        return $metaQuery;
    }

    /**
     * @return IncidentReport[]
     */
    public function getReports()
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

        return array_map(function ($post) {
            return new IncidentReport($post);
        }, $posts);
    }

    /**
     * @return array
     */
    private function getTaxQuery()
    {
        $taxQuery = array();

        if (!empty($this->incidentTypeId)) {
            $taxQuery[] = array('taxonomy' => 'einsatzart', 'terms' => array($this->incidentTypeId));
        }

        return $taxQuery;
    }

    /**
     * @param array $postIds
     */
    public function setExcludePostIds($postIds)
    {
        $this->excludePostId = $postIds;
    }

    /**
     * @param int $incidentTypeId
     */
    public function setIncidentTypeId($incidentTypeId)
    {
        $this->incidentTypeId = $incidentTypeId;
    }

    /**
     * @param bool $includePrivateReports
     */
    public function setIncludePrivateReports($includePrivateReports)
    {
        $this->includePrivateReports = $includePrivateReports;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        if (is_numeric($limit)) {
            $this->limit = $limit;
        }
    }

    /**
     * @param boolean $onlySpecialReports
     */
    public function setOnlySpecialReports($onlySpecialReports)
    {
        $this->onlySpecialReports = $onlySpecialReports;
    }

    /**
     * @param boolean $orderAsc
     */
    public function setOrderAsc($orderAsc)
    {
        $this->orderAsc = $orderAsc;
    }

    /**
     * @param int[] $units
     */
    public function setUnits($units)
    {
        $this->units = $units;
    }

    /**
     * @param int $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }
}
