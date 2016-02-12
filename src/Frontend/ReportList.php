<?php
namespace abrain\Einsatzverwaltung\Frontend;

class ReportList
{
    public function getList($reports, $splitMonths)
    {
        $string = 'Es gibt ' . count($reports) . ' Berichte';
        return $string;
    }

    public function printList($reports, $splitMonths)
    {
        echo $this->getList($reports, $splitMonths);
    }
}