<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Types\Report;

/**
 * Manages rewrite rules
 * @package abrain\Einsatzverwaltung
 */
class PermalinkController
{
    public function addRewriteRules()
    {
        global $wp_rewrite;
        if ($wp_rewrite->using_permalinks()) {
            $base = ltrim($wp_rewrite->front, '/') . Report::getRewriteSlug();
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
     * @param \WP_Query $query
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
