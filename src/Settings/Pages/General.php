<?php
namespace abrain\Einsatzverwaltung\Settings\Pages;

use abrain\Einsatzverwaltung\Frontend\AnnotationIconBar;
use abrain\Einsatzverwaltung\ReportQuery;
use abrain\Einsatzverwaltung\Utilities;

/**
 * General settings page
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class General extends SubPage
{
    public function __construct()
    {
        parent::__construct('general', __('General', 'einsatzverwaltung'));

        add_filter('pre_update_option_einsatzvw_category', array($this, 'maybeCategoryChanged'), 10, 2);
        add_filter('pre_update_option_einsatzvw_loop_only_special', array($this, 'maybeCategorySpecialChanged'), 10, 2);
    }

    public function addSettingsFields()
    {
        add_settings_field(
            'einsatzvw_einsatznummer_mainloop',
            'Einsatzbericht als Beitrag',
            array($this, 'echoFieldMainloop'),
            $this->settingsApiPage,
            'einsatzvw_settings_general'
        );
        add_settings_field(
            'einsatzvw_settings_listannotations',
            __('Annotations', 'einsatzverwaltung'),
            array($this, 'echoFieldAnnotations'),
            $this->settingsApiPage,
            'einsatzvw_settings_general'
        );
    }

    public function addSettingsSections()
    {
        add_settings_section(
            'einsatzvw_settings_general',
            '',
            null,
            $this->settingsApiPage
        );
    }

    /**
     * Gibt die Einstellmöglichkeit aus, ob und wie Einsatzberichte zusammen mit anderen Beiträgen ausgegeben werden
     * sollen
     */
    public function echoFieldMainloop()
    {
        echo '<fieldset>';
        $this->echoSettingsCheckbox(
            'einsatzvw_show_einsatzberichte_mainloop',
            'Einsatzberichte zwischen den regul&auml;ren WordPress-Beitr&auml;gen anzeigen'
        );
        echo '<p class="description">L&auml;sst die Einsatzberichte z.B. auf der Startseite, im Widget &quot;Letzte Beitr&auml;ge&quot; oder auch im Beitragsfeed erscheinen</p>';

        echo '<p><label for="einsatzvw_category">';
        echo 'Davon unabh&auml;ngig Einsatzberichte immer in folgender Kategorie anzeigen:';
        echo '&nbsp;</label>';
        $categoryId = intval(get_option('einsatzvw_category', -1));
        wp_dropdown_categories(array(
            'show_option_none' => '- keine -',
            'hide_empty' => false,
            'name' => 'einsatzvw_category',
            'selected' => $categoryId,
            'orderby' => 'name',
            'hierarchical' => true
        ));
        echo '</p>';


        $this->echoSettingsCheckbox(
            'einsatzvw_loop_only_special',
            'Nur als besonders markierte Einsatzberichte zwischen den regul&auml;ren WordPress-Beitr&auml;gen bzw. in der Kategorie anzeigen.'
        );
        echo '<p class="description">Mit dieser Einstellung gelten die beiden oberen Einstellungen nur f&uuml;r als besonders markierte Einsatzberichte.</p>';
        echo '</fieldset>';
    }

    public function echoFieldAnnotations()
    {
        echo '<fieldset>';
        echo '<p>Farbe f&uuml;r inaktive Vermerke:</p>';
        $this->echoColorPicker('einsatzvw_list_annotations_color_off', AnnotationIconBar::DEFAULT_COLOR_OFF);
        echo '<p class="description">Diese Farbe wird f&uuml;r die Symbole von inaktiven Vermerken verwendet, die von aktiven werden in der Textfarbe Deines Themes dargestellt.</p>';
        echo '</fieldset>';
    }

    /**
     * Prüft, ob sich die Kategorie der Einsatzberichte ändert und veranlasst gegebenenfalls ein Erneuern der
     * Kategoriezuordnung
     *
     * @param mixed $newValue The new, unserialized option value.
     * @param mixed $oldValue The old option value.
     *
     * @return string Der zu speichernde Wert
     */
    public function maybeCategoryChanged($newValue, $oldValue): string
    {
        // Nur Änderungen sind interessant
        if ($newValue == $oldValue) {
            return $newValue;
        }

        $reportQuery = new ReportQuery();
        $reportQuery->setIncludePrivateReports(true);
        $reports = $reportQuery->getReports();

        // Wenn zuvor eine Kategorie gesetzt war, müssen die Einsatzberichte aus dieser entfernt werden
        if (!empty($oldValue) && $oldValue != -1) {
            foreach ($reports as $report) {
                Utilities::removePostFromCategory($report->getPostId(), $oldValue);
            }
        }

        // Wenn eine neue Kategorie gesetzt wird, müssen Einsatzberichte dieser zugeordnet werden
        if ($newValue != -1) {
            $onlySpecialInCategory = self::$options->isOnlySpecialInLoop();
            foreach ($reports as $report) {
                if (!$onlySpecialInCategory || $report->isSpecial()) {
                    $report->addToCategory((int)$newValue);
                }
            }
        }

        return $newValue;
    }

    /**
     * Prüft, ob sich die Beschränkung, nur als besonders markierte Einsatzberichte der Kategorie zuzuordnen, ändert
     * und veranlasst gegebenenfalls ein Erneuern der Kategoriezuordnung
     *
     * @param mixed $newValue The new, unserialized option value.
     * @param mixed $oldValue The old option value.
     *
     * @return string Der zu speichernde Wert
     */
    public function maybeCategorySpecialChanged($newValue, $oldValue): string
    {
        // Nur Änderungen sind interessant
        if ($newValue == $oldValue) {
            return $newValue;
        }

        // Ohne gesetzte Kategorie brauchen wir nicht weitermachen
        $categoryId = self::$options->getEinsatzberichteCategory();
        if (-1 === $categoryId) {
            return $newValue;
        }

        $reportQuery = new ReportQuery();
        $reportQuery->setIncludePrivateReports(true);
        $reports = $reportQuery->getReports();

        // Wenn die Einstellung abgewählt wurde, werden alle Einsatzberichte zur Kategorie hinzugefügt
        if ($newValue == 0) {
            foreach ($reports as $report) {
                $report->addToCategory($categoryId);
            }
        }

        // Wenn die Einstellung aktiviert wurde, werden nur die als besonders markierten Einsatzberichte zur Kategorie
        // hinzugefügt, alle anderen daraus entfernt
        if ($newValue == 1) {
            foreach ($reports as $report) {
                if ($report->isSpecial()) {
                    $report->addToCategory($categoryId);
                } else {
                    Utilities::removePostFromCategory($report->getPostId(), $categoryId);
                }
            }
        }

        return $newValue;
    }

    public function registerSettings()
    {
        register_setting(
            'einsatzvw_settings_general',
            'einsatzvw_show_einsatzberichte_mainloop',
            array(Utilities::class, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_general',
            'einsatzvw_category',
            'intval'
        );
        register_setting(
            'einsatzvw_settings_general',
            'einsatzvw_loop_only_special',
            array(Utilities::class, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_general',
            'einsatzvw_list_annotations_color_off',
            'sanitize_hex_color'
        );
    }
}
