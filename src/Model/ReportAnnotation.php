<?php
namespace abrain\Einsatzverwaltung\Model;

/**
 * Vermerk für Einsatzberichte
 *
 * @package abrain\Einsatzverwaltung\Model
 */
class ReportAnnotation
{
    /**
     * Bezeichner für das Icon. Nutzt Font Awesome, für z. B. fa-camera wird nur camera angegeben.
     *
     * @var string
     */
    private $icon;

    /**
     * @var string
     */
    private $identifier;

    /**
     * Dieser Text wird als Tooltip angezeigt, wenn der Vermerk aktiv ist.
     *
     * @var string
     */
    private $labelWhenActive;

    /**
     * Dieser Text wird als Tooltip angezeigt, wenn der Vermerk inaktiv ist.
     *
     * @var string
     */
    private $labelWhenInactive;

    /**
     * Der Key, mit dem der Zustand der Annotation aus Postmeta abgefragt werden kann.
     *
     * @var string
     */
    private $metaKey;

    /**
     * @var string Der Name des Vermerks.
     */
    private $name;

    /**
     * ReportAnnotation constructor.
     *
     * @param string $identifier
     * @param string $name
     * @param string $metaKey
     * @param string $icon
     * @param string $labelWhenActive
     * @param string $labelWhenInactive
     */
    public function __construct($identifier, $name, $metaKey, $icon, $labelWhenActive, $labelWhenInactive)
    {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->metaKey = $metaKey;
        $this->icon = $icon;
        $this->labelWhenActive = $labelWhenActive;
        $this->labelWhenInactive = $labelWhenInactive;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getLabelWhenActive()
    {
        return $this->labelWhenActive;
    }

    /**
     * @return string
     */
    public function getLabelWhenInactive()
    {
        return $this->labelWhenInactive;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param IncidentReport $report
     *
     * @return bool
     */
    public function getStateForReport($report)
    {
        $get_post_meta = get_post_meta($report->getPostId(), $this->metaKey, true);
        return 1 == $get_post_meta;
    }
}
