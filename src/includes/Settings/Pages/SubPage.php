<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\Settings\MainPage;
use function esc_html;
use function sprintf;

/**
 * Base class for a sub page of the plugin settings
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
abstract class SubPage
{
    /**
     * @var string
     */
    public $identifier;

    /**
     * FIXME provisorisch
     * @var Options
     */
    public static $options;

    /**
     * @var string
     */
    public $settingsApiPage;

    /**
     * @var string
     */
    public $title;

    /**
     * SubPage constructor.
     * @param $identifier
     * @param $title
     */
    public function __construct($identifier, $title)
    {
        $this->identifier = $identifier;
        $this->settingsApiPage = MainPage::EVW_SETTINGS_SLUG . '-' . $identifier;
        $this->title = $title;
    }

    abstract public function addSettingsFields();
    abstract public function addSettingsSections();
    abstract public function registerSettings();

    /**
     * Override this method to do some preparations right before the page is rendered
     */
    public function beforeContent()
    {
    }

    /**
     * Gibt den von WordPress mitgelieferten Colorpicker aus
     *
     * @param string $optionName Name der Einstellung
     * @param string $defaultValue Der Standardwert, der im Colorpicker angeboten werden soll
     */
    protected function echoColorPicker($optionName, $defaultValue)
    {
        $value = get_option($optionName, $defaultValue);
        $sanitizedValue = sanitize_hex_color($value);
        if (empty($sanitizedValue)) {
            $sanitizedValue = $defaultValue;
        }
        printf(
            '<input type="text" name="%s" class="einsatzverwaltung-color-picker" value="%s" data-default-color="%s" />',
            esc_attr($optionName),
            esc_attr($sanitizedValue),
            esc_attr($defaultValue)
        );
    }

    /**
     * Gibt eine Checkbox auf der Einstellungsseite aus
     *
     * @param string $checkboxId Id der Option
     * @param string $text Beschriftung der Checkbox
     * @param bool $defaultValue Standardwert für Option, falls diese nicht existiert
     */
    protected function echoSettingsCheckbox($checkboxId, $text, $defaultValue = false)
    {
        $currentValue = get_option($checkboxId, $defaultValue);
        printf(
            '<label><input type="checkbox" value="1" id="%1$s" name="%1$s"%2$s>%3$s</label>',
            esc_attr($checkboxId),
            checked($currentValue, '1', false),
            esc_html($text)
        );
    }

    /**
     * @param string $name Name der Option
     * @param array $options Array aus Wert/Label-Paaren
     * @param string $defaultValue Standardwert für Option, falls diese nicht existiert
     */
    protected function echoRadioButtons($name, $options, $defaultValue)
    {
        $currentValue = get_option($name, $defaultValue);
        foreach ($options as $value => $option) {
            if ($value === $defaultValue) {
                // translators: 1: label of the default option
                $label = esc_html(sprintf(__('%s (default)', 'einsatzverwaltung'), $option['label']));
            } else {
                $label = esc_html($option['label']);
            }
            if (array_key_exists('code', $option) && !empty($option['code'])) {
                $label .= sprintf('<code>%s</code>', esc_html($option['code']));
            }
            printf(
                '<label><input type="radio" name="%s" value="%s"%s>%s</label><br>',
                esc_attr($name),
                esc_attr($value),
                checked($value, $currentValue, false),
                $label
            );
        }
    }

    /**
     * Generiert eine Auswahlliste
     *
     * @param string $name Name des Parameters
     * @param array $options Array aus Wert/Label-Paaren
     * @param string $selectedValue Vorselektierter Wert
     */
    protected function echoSelect($name, $options, $selectedValue)
    {
        printf('<select name="%s">', esc_attr($name));
        foreach ($options as $value => $label) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($value),
                selected($value, $selectedValue),
                esc_html($label)
            );
        }
        echo '</select>';
    }

    /**
     * @param string $name Name der Option
     */
    protected function echoTextarea($name)
    {
        $currentValue = get_option($name, '');
        printf(
            '<p><textarea name="%s" class="large-text" rows="10" cols="50">%s</textarea></p>',
            esc_attr($name),
            esc_textarea($currentValue)
        );
    }

    /**
     * Gibt ein Eingabefeld aus
     *
     * @since 1.0.0
     *
     * @param string $name Name des Parameters
     * @param string $value Wert, der im Eingabefeld stehen soll
     * @param int $size Number of characters visible at the same time
     *
     */
    protected function echoSettingsInput($name, $value, $size = 20)
    {
        printf(
            '<input type="text" value="%2$s" id="%1$s" name="%1$s" size="%3$d" />',
            esc_attr($name),
            esc_attr($value),
            esc_attr($size)
        );
    }

    /**
     * Diese Methode kann von Implementierungen dieser Klasse überschrieben werden, um Inhalte auszugeben, bevor das
     * Formular gerendert wird.
     */
    public function echoStaticContent()
    {
    }

    /**
     * Diese Methode kann von Implementierungen dieser Klasse überschrieben werden, wenn sie keine Formularelemente
     * ausgeben und damit keinen Button zum Speichern benötigen
     *
     * @return bool
     */
    public function hasForm(): bool
    {
        return true;
    }
}
