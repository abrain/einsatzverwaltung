<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Types\Report;
use WP_Post;
use WP_Query;

/**
 * Manages rewrite rules
 * @package abrain\Einsatzverwaltung
 */
class PermalinkController
{
    const DEFAULT_REPORT_PERMALINK = '%postname%';

    public function addRewriteRules()
    {
        global $wp_rewrite;
        if ($wp_rewrite->using_permalinks()) {
            // add rules for paginated year archive
            $base = ltrim($wp_rewrite->front, '/') . Report::getRewriteSlug();
            add_rewrite_rule(
                $base . '/(\d{4})/page/(\d{1,})/?$',
                'index.php?post_type=einsatz&year=$matches[1]&paged=$matches[2]',
                'top'
            );
            add_rewrite_rule($base . '/(\d{4})/?$', 'index.php?post_type=einsatz&year=$matches[1]', 'top');

            // if the custom permalink contains a slash, the rewrite tag %einsatz% has to allow for slashes
            $permalinkStructure = get_option('einsatz_permalink', self::DEFAULT_REPORT_PERMALINK);
            if (strpos($permalinkStructure, '/') !== false) {
                $postType = get_post_type_object(Report::SLUG);
                remove_rewrite_tag("%$postType->name%");
                add_rewrite_tag(
                    "%$postType->name%",
                    '(.+?)',
                    $postType->query_var ? "{$postType->query_var}=" : "post_type=$postType->name&name="
                );
            }
        }

        add_rewrite_tag('%einsatznummer%', '([^&]+)');
    }

    /**
     * Builds the selector, the part of the URL that uniquely identifies a single report
     *
     * @param WP_Post $post
     *
     * @return string
     */
    public function buildSelector(WP_Post $post)
    {
        // TODO build link based on option 'einsatz_permalink'
        return $post->ID . '-seotitle';
    }

    /**
     * @param WP_Query $query
     */
    public function einsatznummerMetaQuery($query)
    {
        $enr = $query->get('einsatznummer');
        if (!empty($enr)) {
            $query->set('post_type', Report::SLUG);
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
    public function filterPostTypeLink($postLink, WP_Post $post, $leavename, $sample)
    {
        global $wp_rewrite;

        // not our business
        if (empty($post) || get_post_type($post) !== Report::SLUG) {
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

        $path = sprintf('%s/%s', Report::getRewriteSlug(), $this->buildSelector($post));
        return home_url(user_trailingslashit($path));
    }

    /**
     * Modifies the query variables (in case a custom permalink for reports is used) to uniquely select a single report
     *
     * @param array $queryvars
     *
     * @return mixed
     */
    public function filterRequest($queryvars)
    {
        global $wp_rewrite;

        // We don't mess with simple links
        if (!$wp_rewrite->using_permalinks()) {
            return $queryvars;
        }

        if (!array_key_exists('einsatz', $queryvars)) {
            return $queryvars;
        }

        error_log($queryvars['einsatz']);
        preg_match($this->getSelectorRegEx(), $queryvars['einsatz'], $matches);
        error_log(print_r($matches, true));
        if (empty($matches)) {
            return $queryvars;
        }

        $queryvars['p'] = $matches[1];
        unset($queryvars['einsatz']);
        unset($queryvars['name']);

        return $queryvars;
    }

    /**
     * Returns the regular expression necessary to disassemble the selector (part of the URL specifying a single report)
     *
     * @return string
     */
    public function getSelectorRegEx()
    {
        // TODO construct RegEx depending on permalink structure
        return '/^(\d+)-.*$/';
    }

    /**
     * Gibt den Link zu einem bestimmten Jahresarchiv zurück, berücksichtigt dabei die Permalink-Einstellungen
     *
     * @param string $year
     *
     * @return string
     */
    public static function getYearArchiveLink($year)
    {
        global $wp_rewrite;
        $link = get_post_type_archive_link(Report::SLUG);
        $link = ($wp_rewrite->using_permalinks() ? trailingslashit($link) : $link . '&year=') . $year;
        return user_trailingslashit($link);
    }
}
