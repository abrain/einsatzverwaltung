<?php
namespace abrain\Einsatzverwaltung;

use WP_User;

/**
 * Class UserRightsManager
 * @package abrain\Einsatzverwaltung
 */
class UserRightsManager
{
    public static $capabilities = array(
        'edit_einsatzberichte',
        'edit_private_einsatzberichte',
        'edit_published_einsatzberichte',
        'edit_others_einsatzberichte',
        'publish_einsatzberichte',
        'read_private_einsatzberichte',
        'delete_einsatzberichte',
        'delete_private_einsatzberichte',
        'delete_published_einsatzberichte',
        'delete_others_einsatzberichte'
    );

    private $capabilitiesUnit = array(
        'edit_evw_units',
        'edit_private_evw_units',
        'edit_published_evw_units',
        'edit_others_evw_units',
        'publish_evw_units',
        'read_private_evw_units',
        'delete_evw_units',
        'delete_private_evw_units',
        'delete_published_evw_units',
        'delete_others_evw_units'
    );

    /**
     * @param string $roleSlug
     *
     * @return bool
     */
    private function isRoleAllowedToEdit($roleSlug)
    {
        if ($roleSlug === 'administrator') {
            return true;
        }

        return get_option('einsatzvw_cap_roles_' . $roleSlug, '0') === '1';
    }

    /**
     * Prüft und vergibt Benutzerrechte zur Laufzeit
     *
     * @param array $allcaps Effektive Nutzerrechte
     * @param array $caps Die angefragten Nutzerrechte
     * @param array $args Zusätzliche Parameter wie Objekt-ID
     * @param WP_User $user Benutzerobjekt
     *
     * @return array Die gefilterten oder erweiterten Nutzerrechte
     */
    public function userHasCap($allcaps, $caps, $args, $user)
    {
        $allCapabilities = array_merge(self::$capabilities, $this->capabilitiesUnit);
        $requestedCaps = array_intersect($allCapabilities, $caps);

        // Wenn es nicht um Berechtigungen aus der Einsatzverwaltung geht, können wir uns den Rest sparen
        if (count($requestedCaps) == 0) {
            return $allcaps;
        }

        // Wenn der Benutzer mindestens einer berechtigten Rolle zugeordnet ist, werden die Berechtigungen erteilt
        $allowedUserRoles = array_filter($user->roles, array($this, 'isRoleAllowedToEdit'));
        if (count($allowedUserRoles) > 0) {
            foreach ($requestedCaps as $requestedCap) {
                $allcaps[$requestedCap] = 1;
            }
        }

        return $allcaps;
    }
}
