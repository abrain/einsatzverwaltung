<?php
namespace abrain\Einsatzverwaltung\Settings\Pages;

use abrain\Einsatzverwaltung\Utilities;

/**
 * Capabilities settings page
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class Capabilities extends SubPage
{
    public function __construct()
    {
        parent::__construct('capabilities', 'Berechtigungen');
    }

    public function addSettingsFields()
    {
        add_settings_field(
            'einsatzvw_settings_caps_roles',
            'Rollen',
            array($this, 'echoFieldRoles'),
            $this->settingsApiPage,
            'einsatzvw_settings_caps'
        );
    }

    public function addSettingsSections()
    {
        add_settings_section(
            'einsatzvw_settings_caps',
            '',
            function () {
                echo '<p>Hier kann festgelegt werden, welche Benutzer die Einsatzberichte verwalten k&ouml;nnen.</p>';
            },
            $this->settingsApiPage
        );
    }

    /**
     * Gibt die Einstellmöglichkeiten für die Berechtigungen aus
     */
    public function echoFieldRoles()
    {
        echo '<fieldset>';
        $roles = get_editable_roles();
        if (empty($roles)) {
            echo "Es konnten keine Rollen gefunden werden.";
        } else {
            foreach ($roles as $roleSlug => $role) {
                // Administratoren haben immer Zugriff, deshalb ist keine Einstellung nötig
                if ('administrator' === $roleSlug) {
                    continue;
                }

                $this->echoSettingsCheckbox(
                    'einsatzvw_cap_roles_' . $roleSlug,
                    translate_user_role($role['name'])
                );
                echo '<br>';
            }
            echo '<p class="description">Die Benutzer mit den hier ausgew&auml;hlten Rollen haben alle Rechte, um die Einsatzberichte und die zugeh&ouml;rigen Eigenschaften (z.B. Einsatzarten) zu verwalten. Zu dieser Einstellungsseite und den Werkzeugen haben in jedem Fall nur Administratoren Zugang.</p>';
            echo '<p class="description">Die Berechtigungen können mit speziellen Plugins deutlich feingranularer eingestellt werden.</p>';
        }
        echo '</fieldset>';
    }

    public function registerSettings()
    {
        $roles = get_editable_roles();
        if (!empty($roles)) {
            foreach (array_keys($roles) as $roleSlug) {
                // Administratoren haben immer Zugriff, deshalb ist keine Einstellung nötig
                if ('administrator' === $roleSlug) {
                    continue;
                }

                register_setting(
                    'einsatzvw_settings_capabilities',
                    'einsatzvw_cap_roles_' . $roleSlug,
                    array(Utilities::class, 'sanitizeCheckbox')
                );
            }
        }
    }
}
