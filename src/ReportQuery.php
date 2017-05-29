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
     * Gibt ob aufsteigend sortiert werden soll
     *
     * @var bool
     */
    private $orderAsc;

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
    public function getReports()
    {
        $postStatus = array('publish');
        if ($this->includePrivateReports) {
            $postStatus[] = 'private';
        }

        // Abfrage der Metainformationen zusammenbasteln
        $metaQuery = array();
        if ($this->onlySpecialReports) {
            $metaQuery[] = array('key' => 'einsatz_special', 'value' => '1');
        }

        // Abfrage basierend auf Datumsparametern zusammenbasteln
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

        $postArgs = array(
            'order' => $this->orderAsc ? 'ASC' : 'DESC',
            'orderby' => 'post_date',
            'post_status' => $postStatus,
            'post_type' => 'einsatz',
            'posts_per_page' => $this->limit,
        );

        if (!empty($metaQuery)) {
            $postArgs['meta_query'] = $metaQuery;
        }

        if (!empty($dateQuery)) {
            $postArgs['date_query'] = $dateQuery;
        }

        if (!empty($this->excludePostId)) {
            $postArgs['post__not_in'] = $this->excludePostId;
        }

        $posts = get_posts($postArgs);

        $reports = array();
        foreach ($posts as $post) {
            $reports[] = new IncidentReport($post);
        }

        return $reports;
    }

    /**
     * @param array $postIds
     */
    public function setExcludePostIds($postIds)
    {
        $this->excludePostId = $postIds;
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
     * @param int $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }
}
