<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\Settings\MainPage;

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
     * Gibt den von WordPress mitgelieferten Colorpicker aus
     *
     * @param string $optionName Name der Einstellung
     * @param string $defaultValue Der Standardwert, der im Colorpicker angeboten werden soll
     */
    protected function echoColorPicker($optionName, $defaultValue)
    {
        printf(
            '<input type="text" name="%1$s" class="einsatzverwaltung-color-picker" value="%2$s" data-default-color="%3$s" />',
            esc_attr($optionName),
            esc_attr(get_option($optionName, $defaultValue)),
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
            '<label for="%1$s"><input type="checkbox" value="1" id="%1$s" name="%1$s"%2$s>%3$s</label>',
            esc_attr($checkboxId),
            checked($currentValue, '1', false),
            $text
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
        foreach ($options as $value => $label) {
            printf(
                '<label><input type="radio" name="%s" value="%s"%s>%s</label><br>',
                $name,
                $value,
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
        echo '<select name="' . $name . '">';
        foreach ($options as $value => $label) {
            echo '<option value="' . $value . '"' . ($selectedValue == $value ? ' selected="selected"' : '') . '>';
            echo $label . '</option>';
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
            $name,
            esc_textarea($currentValue)
        );
    }

    /**
     * Gibt ein Eingabefeld aus
     *
     * @since 1.0.0
     *
     * @param string $name Name des Parameters
     * @param string $description Beschreibungstext
     * @param string $value Wert, der im Eingabefeld stehen soll
     */
    protected function echoSettingsInput($name, $description, $value = '')
    {
        printf(
            '<input type="text" value="%2$s" id="%1$s" name="%1$s" /><p class="description">%3$s</p>',
            $name,
            (empty($value) ? self::$options->getOption($name) : $value),
            $description
        );
    }
}
