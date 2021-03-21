<?php
namespace abrain\Einsatzverwaltung\Export;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Export\Formats\Csv;
use abrain\Einsatzverwaltung\Export\Formats\Excel;
use abrain\Einsatzverwaltung\Export\Formats\Json;

/**
 * Werkzeug für den Export von Einsatzberichten in verschiedenen Formaten
 */
class Tool
{
    const EVW_TOOL_EXPORT_SLUG = 'einsatzvw-tool-export';

    /**
     * @var Formats\Format[]
     */
    private $formats = array();

    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->loadFormats();
    }

    /**
     * Fügt das Werkzeug zum Menü hinzu
     */
    public function addToolToMenu()
    {
        add_management_page(
            'Einsatzberichte exportieren',
            'Einsatzberichte exportieren',
            'export',
            self::EVW_TOOL_EXPORT_SLUG,
            array($this, 'renderToolPage')
        );
    }

    /**
     * @param string $hook
     */
    public function enqueueAdminScripts($hook)
    {
        if ($hook !== 'tools_page_einsatzvw-tool-export') {
            return;
        }

        wp_enqueue_script(
            'einsatzverwaltung-export',
            Core::$scriptUrl . 'export.js',
            array('jquery'),
            Core::VERSION
        );
    }

    /**
     * Bietet die zu exportierenden Einsatzberichte als Download an.
     */
    public function startExport()
    {
        if (current_user_can('export') && @$_GET['page'] == self::EVW_TOOL_EXPORT_SLUG &&
            @$_GET['download'] == true) {
            $format = @$this->formats[$_GET['format']];
 
            if ($format) {
                $startDate = @$_GET['export_filters']['start_date'];
                $endDate = @$_GET['export_filters']['end_date'];
                $exportOptions = stripslashes_deep((array)@$_GET['export_options'][$_GET['format']]);

                $format->setFilters($startDate, $endDate);
                $format->setOptions($exportOptions);

                header('Content-Description: File Transfer');
                header('Content-Disposition: attachment; filename=' . $format->getFilename());
                $format->export();
                die();
            }
        }
    }

    private function loadFormats()
    {
        $this->formats['csv'] = new Csv();
        $this->formats['excel'] = new Excel();
        $this->formats['json'] = new Json();
    }

    /**
     * Generiert den Inhalt der Werkzeugseite
     */
    public function renderToolPage()
    {
        echo '<div class="wrap">';
        echo '<h1>' . 'Einsatzberichte exportieren' . '</h1>';
        echo '<p>Dieses Werkzeug exportiert Einsatzberichte in verschiedenen Formaten.</p>'; ?>
<form method="get" id="export-form">
    <input type="hidden" name="page" value="einsatzvw-tool-export">
    <input type="hidden" name="download" value="true">
    <h2>Wähle, welche Einsatzberichte du exportieren möchtest</h2>
    <fieldset>
        <legend class="screen-reader-text">Wähle, welche Einsatzberichte du exportieren möchtest</legend>
        <ul id="export-filters">
            <li>
                <fieldset>
                    <legend class="screen-reader-text">Zeitraum:</legend>
                    <label for="post-start-date" class="label-responsive">Alarmzeit von:</label>
                    <select name="export_filters[start_date]" id="post-start-date">
                        <option value="0">— Auswählen —</option>
                        <?php $this->renderDateOptions(); ?>
                    </select>
                    <label for="post-end-date" class="label-responsive">bis:</label>
                    <select name="export_filters[end_date]" id="post-end-date">
                        <option value="0">— Auswählen —</option>
                        <?php $this->renderDateOptions(); ?>
                    </select>
                </fieldset>
            </li>
        </ul>
    </fieldset>
    <p class="description">Wird kein Zeitraum ausgew&auml;hlt, werden alle Einsatzberichte exportiert.</p>

    <h2>Wähle, in welches Format du exportieren möchtest</h2>
    <fieldset>
        <legend class="screen-reader-text">Wähle, in welches Format du exportieren möchtest</legend>
        <?php foreach ($this->formats as $formatKey => $format) {
            ?>
            <p>
                <label>
                    <input type="radio" name="format" value="<?php echo $formatKey; ?>">
                    <?php echo $format->getTitle(); ?>
                </label>
            </p>
            <ul id="<?php echo $formatKey; ?>-options" class="export-options export-filters" style="display: none;">
                <?php $format->renderOptions(); ?>
            </ul>
            <?php
        } ?>
    </fieldset>

        <?php submit_button('Export-Datei herunterladen'); ?>
</form>

        <?php
        echo '</div>';
    }

    private function renderDateOptions()
    {
        global $wpdb, $wp_locale;

        $months = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
            FROM $wpdb->posts
            WHERE post_type = %s AND post_status = 'publish'
            ORDER BY post_date DESC
        ", 'einsatz'));

        $monthCount = count($months);
        if (!$monthCount || (1 == $monthCount && 0 == $months[0]->month)) {
            return;
        }

        foreach ($months as $date) {
            if (0 == $date->year) {
                continue;
            }

            $month = zeroise($date->month, 2);
            $value = $date->year . '-' . $month;
            $text = $wp_locale->get_month($month) . ' ' . $date->year;
            echo '<option value="' . $value . '">' . $text . '</option>';
        }
    }
}
