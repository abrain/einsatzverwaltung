<?php
namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Types\Unit;
use function esc_html;
use function esc_url;
use function get_post;
use function get_the_title;
use function printf;
use function url_to_postid;

/**
 * Defines the look and feel of the list table of the Units in the admin area
 * @package abrain\Einsatzverwaltung\Admin
 */
class UnitListTable
{
    /**
     * Adjusts which columns are available
     *
     * @param array $columns
     *
     * @return array
     */
    public function filterColumns($columns)
    {
        // Remove the columns for the external URL and the info page column, as we cannot override their content.
        unset($columns['unit_exturl']);
        unset($columns['unit_pid']);

        // Add a separate column in which we can combine the content of the two removed columns above
        $columns['unit_linking'] = __('Linking', 'einsatzverwaltung');
        return $columns;
    }

    /**
     * Prints the column content
     *
     * @param string $columnName
     * @param int $postId
     */
    public function filterColumnContent($columnName, $postId)
    {
        // We only want to change a specific column
        if ($columnName !== 'unit_linking') {
            return;
        }

        $unit = get_post($postId);
        $url = Unit::getInfoUrl($unit);
        // If no info URL is set, there's nothing to do
        if (empty($url)) {
            return;
        }

        // Check if it is a local link after all so we can display the post title
        $linkTitle = __('External URL', 'einsatzverwaltung');
        $postId = url_to_postid($url);
        if ($postId !== 0) {
            $title = get_the_title($postId);
            $linkTitle = empty($title) ? __('Internal URL', 'einsatzverwaltung') : $title;
        }

        printf('<a href="%1$s">%2$s</a>', esc_url($url), esc_html($linkTitle));
    }
}
