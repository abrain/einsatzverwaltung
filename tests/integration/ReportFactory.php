<?php
namespace abrain\Einsatzverwaltung;

use WP_Error;
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
        'einsatz_incidentNumber' => '',
        'einsatz_mannschaft' => '',
        'einsatz_special' => 0,
        'einsatz_weight' => 1,
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
     * FIXME nicht gut, da unklar ist, ob Postmeta auch sonst wie gedacht mit Standardwerten befüllt wird
     *
     * @param array $args
     * @param array|null $generation_definitions
     * @param callable|null $callbacks
     *
     * @return array|WP_Error
     */
    public function generate_args($args = array(), $generation_definitions = null, &$callbacks = null) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- overridden method
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
     * Erzeugt mehrere Einsatzberichte für das angegebene Jahr. Dabei werden die Alarmzeitpunkte gleichmäßig über das
     * Jahr verteilt. Die ersten und letzten 3 Stunden des Jahres werden freigelassen, um in Tests vor bzw. nach allen
     * Einsatzberichten neue hinzufügen zu können.
     *
     * @param string $year
     * @param int $count
     * @param array $args
     * @param array $generationDefinitions
     *
     * @return array IDs der erzeugten Einsatzberichte
     */
    public function generateManyForYear($year, $count, $args = array(), $generationDefinitions = null)
    {
        if ($count < 1) {
            return array();
        }

        $dates = array();

        $firstDate = strtotime('1 January ' . $year . ' 03:00:00');
        $lastDate = strtotime($year == date('Y') ? '1 hour ago' : '31 December ' . $year . ' 20:59:59');

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
        foreach ($dates as $date) {
            $reportIds[] = $this->create(array_merge($args, array('post_date' => $date)), $generationDefinitions);
        }
        return $reportIds;
    }
}
