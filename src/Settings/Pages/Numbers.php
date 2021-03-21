<?php
namespace abrain\Einsatzverwaltung\Settings\Pages;

use abrain\Einsatzverwaltung\ReportNumberController;
use abrain\Einsatzverwaltung\Utilities;
use function add_settings_field;
use function esc_attr;
use function printf;

/**
 * Numbers settings page
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class Numbers extends SubPage
{
    public function __construct()
    {
        parent::__construct('numbers', __('Incident numbers', 'einsatzverwaltung'));
    }

    public function addSettingsFields()
    {
        add_settings_field(
            'einsatzvw_einsatznummer_auto',
            __('Mode', 'einsatzverwaltung'),
            array($this, 'echoFieldAuto'),
            $this->settingsApiPage,
            'einsatzvw_settings_numbers'
        );
        add_settings_field(
            'einsatzvw_einsatznummer_order',
            __('Order', 'einsatzverwaltung'),
            array($this, 'echoFieldOrder'),
            $this->settingsApiPage,
            'einsatzvw_settings_numbers'
        );
        add_settings_field(
            'einsatzvw_einsatznummer_digits',
            __('Number of digits', 'einsatzverwaltung'),
            array($this, 'echoFieldDigits'),
            $this->settingsApiPage,
            'einsatzvw_settings_numbers'
        );
        add_settings_field(
            'einsatzvw_einsatznummer_separator',
            __('Separator', 'einsatzverwaltung'),
            array($this, 'echoFieldSeparator'),
            $this->settingsApiPage,
            'einsatzvw_settings_numbers'
        );
    }

    public function addSettingsSections()
    {
        add_settings_section(
            'einsatzvw_settings_numbers',
            '',
            null,
            $this->settingsApiPage
        );
    }

    public function echoFieldAuto()
    {
        $this->echoSettingsCheckbox(
            'einsatzverwaltung_incidentnumbers_auto',
            __('Generate incident numbers automatically', 'einsatzverwaltung')
        );
        printf(
            '<p class="description">%s</p>',
            __('If deactivated, incident numbers can be maintained manually.', 'einsatzverwaltung')
        );
    }

    public function echoFieldDigits()
    {
        echo '<fieldset>';
        printf(
            '<input type="number" value="%2$s" size="2" id="%1$s" name="%1$s" min="1" />',
            'einsatzvw_einsatznummer_stellen',
            esc_attr(ReportNumberController::sanitizeNumberOfDigits(get_option('einsatzvw_einsatznummer_stellen')))
        );
        echo '</fieldset>';
        printf(
            '<p class="description">%s</p>',
            __('The sequential number gets padded with leading zeros until it has this length.', 'einsatzverwaltung')
        );
    }

    public function echoFieldOrder()
    {
        $this->echoSettingsCheckbox(
            'einsatzvw_einsatznummer_lfdvorne',
            __('Put the sequential number first', 'einsatzverwaltung')
        );
        printf(
            '<p class="description">%s</p>',
            __('By default, the year comes before the sequential number. Activate this option to reverse the order.', 'einsatzverwaltung')
        );
    }

    public function echoFieldSeparator()
    {
        echo '<fieldset>';
        $this->echoRadioButtons('einsatzvw_numbers_separator', [
            'none' => ['label' => _x('None', 'number separator selection', 'einsatzverwaltung')],
            'slash' => ['label' => __('Slash', 'einsatzverwaltung'), 'code' => '/'],
            'hyphen' => ['label' => __('Hyphen', 'einsatzverwaltung'), 'code' => '-'],
        ], ReportNumberController::DEFAULT_SEPARATOR);
        echo '</fieldset>';
        printf(
            '<p class="description">%s</p>',
            __('This character separates the year and the sequential number.', 'einsatzverwaltung')
        );
    }

    public function registerSettings()
    {
        register_setting(
            'einsatzvw_settings_numbers',
            'einsatzverwaltung_incidentnumbers_auto',
            array(Utilities::class, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_numbers',
            'einsatzvw_einsatznummer_stellen',
            array(ReportNumberController::class, 'sanitizeEinsatznummerStellen')
        );
        register_setting(
            'einsatzvw_settings_numbers',
            'einsatzvw_einsatznummer_lfdvorne',
            array(Utilities::class, 'sanitizeCheckbox')
        );
        register_setting(
            'einsatzvw_settings_numbers',
            'einsatzvw_numbers_separator',
            array(ReportNumberController::class, 'sanitizeSeparator')
        );
    }
}
