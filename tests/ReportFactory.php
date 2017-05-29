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
        'einsatz_location' => '',
        'einsatz_fehlalarm' => 0,
        'einsatz_incidentNumber' => '',
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

    public function generateManyForYear($year, $count, $args = array(), $generationDefinitions = null)
    {
        if ($count < 1) {
            return array();
        }

        $dates = array();

        $firstDate = strtotime('1 January ' . $year . ' 03:00:00');
        $lastDate = strtotime($year == date('Y') ? '24 hours ago' : '31 December ' . $year . ' 20:59:59');

        $dates[] = date('Y-m-d H:i:s', $firstDate);

        if ($count > 2) {
            $timestampDistance = ($lastDate - $firstDate) / ($count - 1);
            for ($i = 1; $i < $count - 1; $i++) {
                $dates[] = date('Y-m-d H:i:s', $firstDate + $timestampDistance * $i);
            }
        }

        if ($count > 1) {
            $dates[] = date('Y-m-d H:i:s', $lastDate);
        }

        $reportIds = array();
        foreach ($dates as $index => $date) {
            $reportIds[] = $this->create(array('post_date' => $date));
        }
        return $reportIds;
    }
}
