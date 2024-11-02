<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Types\Report;
use WP_Post;
use WP_Query;
use function add_action;
use function add_filter;

/**
 * Manages rewrite rules
 * @package abrain\Einsatzverwaltung
 */
class PermalinkController
{
    const DEFAULT_REPORT_PERMALINK = '%postname%';

    /**
     * @var string
     */
    private $reportPermalink;

    /**
     * @var string
     */
    private $reportRewriteSlug;

    private $rewriteTags = array(
        '%postname%',
        '%post_id%',
        '%postname_nosuffix%'
    );

    private $rewriteTagRegEx = array(
        '(?<name>[A-Za-z0-9_-]+)',
        '(?<id>[0-9]+)',
        '([A-Za-z0-9_-]+)'
    );

    /**
     * Register the actions and filters, that this class expects.
     */
    public function addHooks()
    {
        add_filter('option_einsatz_permalink', array(self::class, 'sanitizePermalink'));
        add_action('parse_query', array($this, 'einsatznummerMetaQuery'));
        add_filter('post_type_link', array($this, 'filterPostTypeLink'), 10, 4);
        add_filter('request', array($this, 'filterRequest'));
    }

    /**
     * @param Report $report
     */
    public function addRewriteRules(Report $report)
    {
        global $wp_rewrite;

        if ($wp_rewrite->using_permalinks()) {
            $this->reportPermalink = get_option('einsatz_permalink', self::DEFAULT_REPORT_PERMALINK);
            $this->reportRewriteSlug = $report->getRewriteSlug();

            // add rules for paginated year archive
            $base = $this->getRewriteBase();
            add_rewrite_rule(
                $base . '/(\d{4})/page/(\d{1,})/?$',
                'index.php?post_type=einsatz&year=$matches[1]&paged=$matches[2]',
                'top'
            );
            add_rewrite_rule($base . '/(\d{4})/?$', 'index.php?post_type=einsatz&year=$matches[1]', 'top');
        }

        add_rewrite_tag('%einsatznummer%', '([^&]+)');
    }

    /**
     * Builds the selector, the part of the URL that uniquely identifies a single report
     *
     * @param WP_Post $post
     * @param string $structure
     *
     * @return string
     */
    public function buildSelector(WP_Post $post, string $structure): string
    {
        $tagReplacements = array(
            $post->post_name,
            $post->ID,
            sanitize_title($post->post_title)
        );
        return str_replace($this->rewriteTags, $tagReplacements, $structure);
    }

    /**
     * @param WP_Query $query
     */
    public function einsatznummerMetaQuery(WP_Query $query)
    {
        $enr = $query->get('einsatznummer');
        if (!empty($enr)) {
            $query->set('post_type', Report::getSlug());
            $query->set('meta_key', 'einsatz_incidentNumber');
            $query->set('meta_value', $enr);
        }
    }

    /**
     * @param string $postLink
     * @param WP_Post $post
     * @param bool $leavename
     * @param bool $sample
     *
     * @return string
     */
    public function filterPostTypeLink(string $postLink, WP_Post $post, bool $leavename, bool $sample): string
    {
        global $wp_rewrite;

        // not our business
        if (empty($post) || get_post_type($post) !== Report::getSlug()) {
            return $postLink;
        }

        // there are cases that require the default "ugly" links
        if ($wp_rewrite->using_permalinks() === false || $sample === true || $leavename === true) {
            return $postLink;
        }

        // unpublished reports should also not be affected
        if (!in_array($post->post_status, array('publish', 'private'))) {
            return $postLink;
        }

        $selector = $this->buildSelector($post, $this->reportPermalink);
        return $this->getPermalink($selector);
    }

    /**
     * Modifies the query variables (in case a custom permalink for reports is used) to uniquely select a single report
     *
     * @param array $queryvars
     *
     * @return array
     */
    public function filterRequest(array $queryvars): array
    {
        global $wp_rewrite;

        // We don't mess with simple links
        if (!$wp_rewrite->using_permalinks()) {
            return $queryvars;
        }

        return $this->modifyQueryVars($queryvars, $this->reportPermalink);
    }

    /**
     * @return string
     */
    private function getRewriteBase(): string
    {
        global $wp_rewrite;
        return ltrim($wp_rewrite->front, '/') . $this->reportRewriteSlug;
    }

    /**
     * Returns the regular expression necessary to disassemble the selector (part of the URL specifying a single report)
     *
     * @param string $permalink
     *
     * @return string
     */
    public function getSelectorRegEx(string $permalink): string
    {
        $regex = str_replace($this->rewriteTags, $this->rewriteTagRegEx, $permalink);
        return '/^' . str_replace('/', '\/', $regex) . '$/';
    }

    /**
     * @param string $selector
     *
     * @return string
     */
    public function getPermalink(string $selector): string
    {
        $path = sprintf('%s/%s', $this->getRewriteBase(), $selector);
        return home_url(user_trailingslashit($path));
    }

    /**
     * Gibt den Link zu einem bestimmten Jahresarchiv zurück, berücksichtigt dabei die Permalink-Einstellungen
     *
     * @param string $year
     *
     * @return string
     */
    public function getYearArchiveLink(string $year): string
    {
        global $wp_rewrite;
        $link = get_post_type_archive_link(Report::getSlug());
        $link = ($wp_rewrite->using_permalinks() ? trailingslashit($link) : $link . '&year=') . $year;
        return user_trailingslashit($link);
    }

    /**
     * Modifies the query variables to uniquely select a single report
     *
     * @param array $queryVars
     * @param string $reportPermalink
     *
     * @return array
     */
    public function modifyQueryVars(array $queryVars, string $reportPermalink): array
    {
        // Do nothing, if the request is not about reports
        if (!array_key_exists('einsatz', $queryVars)) {
            return $queryVars;
        }

        // Do nothing, if we would only mimic the WordPress default behavior
        if ($reportPermalink === '%postname%') {
            return $queryVars;
        }

        preg_match($this->getSelectorRegEx($reportPermalink), $queryVars['einsatz'], $matches);

        // The selector does not match the permalink structure, do nothing
        if (empty($matches)) {
            return $queryVars;
        }

        if (strpos($reportPermalink, '%post_id%') !== false) {
            $queryVars['p'] = $matches['id'];
            unset($queryVars['einsatz']);
            unset($queryVars['name']);
        } elseif (strpos($reportPermalink, '%postname%') !== false) {
            $queryVars['name'] = $matches['name'];
            unset($queryVars['einsatz']);
        }

        return $queryVars;
    }

    /**
     * Ensures that a permalink contains a unique identifier for reports and that different parts of the permalink are
     * separated by dashes
     *
     * @param string $permalink
     *
     * @return string
     */
    public static function sanitizePermalink(string $permalink): string
    {
        preg_match('/^(%[a-z_]+%)(-(%[a-z_]+%))*$/', $permalink, $matches);
        if (empty($matches)) {
            return self::DEFAULT_REPORT_PERMALINK;
        }

        // permalinks must contain at least one unique identifier
        if (!in_array('%post_id%', $matches) && !in_array('%postname%', $matches)) {
            return self::DEFAULT_REPORT_PERMALINK;
        }

        return $permalink;
    }
}
