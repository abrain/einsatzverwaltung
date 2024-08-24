<?php

namespace abrain\Einsatzverwaltung\Types;

use WP_Term;
use function __;
use function esc_html;
use function esc_url;
use function get_permalink;
use function get_term_meta;
use function get_the_title;
use function sprintf;
use function url_to_postid;

/**
 * Interface CustomTaxonomy
 * @package abrain\Einsatzverwaltung\Types
 */
abstract class CustomTaxonomy implements CustomType
{
    /**
     * Retrieve the URL to more info about a given term. This can be a permalink to an internal page or an external URL.
     *
     * @param WP_Term $term
     * @param string $extUrlKey The termmeta key for the external URL
     * @param string $pidKey The termmeta key for the post ID
     *
     * @return string A URL or an empty string
     */
    public static function getInfoUrlForTerm(WP_Term $term, string $extUrlKey, string $pidKey): string
    {
        // The external URL takes precedence over an internal page
        $extUrl = get_term_meta($term->term_id, $extUrlKey, true);
        if (!empty($extUrl)) {
            return $extUrl;
        }

        // Figure out if an internal page has been assigned
        $pageid = get_term_meta($term->term_id, $pidKey, true);
        if (empty($pageid)) {
            return '';
        }

        // Try to get the permalink of this page
        $pageUrl = get_permalink($pageid);
        if ($pageUrl === false) {
            return '';
        }

        return $pageUrl;
    }

    /**
     * Returns an HTML link for a given URL. The link text for internal URLs is the post title, otherwise it is just
     * 'External URL'.
     *
     * @param string $url
     *
     * @return string
     */
    public static function getUrlColumnContent(string $url): string
    {
        // Check if it is a local link after all, so we can display the post title
        $linkTitle = __('External URL', 'einsatzverwaltung');
        $postId = url_to_postid($url);
        if ($postId !== 0) {
            $title = get_the_title($postId);
            $linkTitle = empty($title) ? __('Internal URL', 'einsatzverwaltung') : $title;
        }

        return sprintf('<a href="%1$s">%2$s</a>', esc_url($url), esc_html($linkTitle));
    }
}
