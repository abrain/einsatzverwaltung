<?php
namespace abrain\Einsatzverwaltung;

use WP_UnitTest_Factory_For_Post;

/**
 * Factoryklasse für Einsatzberichte in Unittests
 *
 * @package abrain\Einsatzverwaltung
 */
class ReportFactory extends WP_UnitTest_Factory_For_Post
{
    private $defaultMetaInput = array(
        'einsatz_einsatzende' => '',
        'einsatz_einsatzleiter' => '',
        'einsatz_einsatzort' => '',
        'einsatz_fehlalarm' => 0,
        'einsatz_mannschaft' => '',
        'einsatz_special' => 0,
    );

    /**
     * ReportFactory constructor.
     *
     * @param object $factory Global factory that can be used to create other objects on the system
     */
    public function __construct($factory = null)
    {
        parent::__construct($factory);
        $this->default_generation_definitions['post_type'] = 'einsatz';
    }

    /**
     * Sorgt dafür, dass die zusätzlichen Angaben (postmeta) einen Standardwert haben
     *
     * @param array $args
     * @param array|null $generation_definitions
     * @param callable|null $callbacks
     *
     * @return array|\WP_Error
     */
    public function generate_args($args = array(), $generation_definitions = null, &$callbacks = null)
    {
        $generatedArgs = parent::generate_args($args, $generation_definitions, $callbacks);

        if (is_wp_error($generatedArgs)) {
            return $generatedArgs;
        }

        if (!array_key_exists('meta_input', $generatedArgs)) {
            $generatedArgs['meta_input'] = array();
        }

        $generatedArgs['meta_input'] = wp_parse_args($generatedArgs['meta_input'], $this->defaultMetaInput);

        return $generatedArgs;
    }

    /**
     * @param $args
     * @return int|\WP_Error
     */
    public function create_object($args)
    {
        $post = parent::create_object($args);

        if (is_wp_error($post) || 0 === $post) {
            return $post;
        }

        // meta_input ist erst ab WP 4.4 nutzbar
        if (version_compare(bloginfo('version'), '4.4', '<')) {
            foreach ($this->defaultMetaInput as $metaKey => $metaValue) {
                add_post_meta((int) $post, $metaKey, $metaValue);
            }
        }

        return $post;
    }
}
