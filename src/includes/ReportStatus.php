<?php
namespace abrain\Einsatzverwaltung;

/**
 * An enum to represent the status of a Report
 * @package abrain\Einsatzverwaltung
 */
abstract class ReportStatus
{
    /**
     * There was a real emergency that had to be dealt with
     */
    const ACTUAL = 0;

    /**
     * The alarm turned out to be unnecessary
     */
    const FALSE_ALARM = 1;
}
