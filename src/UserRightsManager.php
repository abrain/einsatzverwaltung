<?php
namespace abrain\Einsatzverwaltung;

use function add_role;
use function array_fill_keys;
use function array_merge;
use function get_role;
use function remove_role;
use function update_option;

/**
 * Class UserRightsManager
 * @package abrain\Einsatzverwaltung
 */
class UserRightsManager
{
    const ROLE_UPDATE_OPTION = 'einsatzverwaltung_update_roles';

    /**
     * All capabilities for reports.
     *
     * @var string[]
     */
    private static $capabilities = array(
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

    /**
     * Capabilities for the author role.
     *
     * @var string[]
     */
    private static $authorCaps = [
        'edit_einsatzberichte',
        'edit_published_einsatzberichte',
        'publish_einsatzberichte',
        'delete_einsatzberichte',
        'delete_published_einsatzberichte'
    ];

    /**
     * Capabilities for the contributor role.
     *
     * @var string[]
     */
    private static $contributorCaps = [
        'edit_einsatzberichte',
        'delete_einsatzberichte'
    ];

    /**
     * Updates the user roles if the flag for a user role update is set.
     */
    public function maybeUpdateRoles()
    {
        if (get_option(self::ROLE_UPDATE_OPTION, '0') === '1') {
            // Reset the flag right away, so no other process tries to update the roles as well
            update_option(self::ROLE_UPDATE_OPTION, '0');

            $this->updateRoles();
        }
    }

    /**
     * Updates the capabilities of the user roles. If the roles already exist, they have to be removed first.
     */
    public function updateRoles()
    {
        // Make sure, the administrator role has all the necessary capabilities
        $adminRole = get_role('administrator');
        if (!empty($adminRole)) {
            foreach (self::$capabilities as $capability) {
                $adminRole->add_cap($capability);
            }
        }

        remove_role('einsatzverwaltung_reports_contributor');
        add_role(
            'einsatzverwaltung_reports_contributor',
            _x('Incident Reports Contributor', 'User role', 'einsatzverwaltung'),
            array_merge(['read' => true], array_fill_keys(self::$contributorCaps, true))
        );

        remove_role('einsatzverwaltung_reports_author');
        add_role(
            'einsatzverwaltung_reports_author',
            _x('Incident Reports Author', 'User role', 'einsatzverwaltung'),
            array_merge(['read' => true, 'upload_files' => true], array_fill_keys(self::$authorCaps, true))
        );

        remove_role('einsatzverwaltung_reports_editor');
        add_role(
            'einsatzverwaltung_reports_editor',
            _x('Incident Reports Editor', 'User role', 'einsatzverwaltung'),
            array_merge(['read' => true, 'upload_files' => true], array_fill_keys(self::$capabilities, true))
        );

        remove_role('einsatzverwaltung_reportapi_draft');
        add_role(
            'einsatzverwaltung_reportapi_draft',
            _x('Incident Reports API (drafts)', 'User role', 'einsatzverwaltung'),
            ['edit_einsatzberichte' => true]
        );

        remove_role('einsatzverwaltung_reportapi_publish');
        add_role(
            'einsatzverwaltung_reportapi_publish',
            _x('Incident Reports API', 'User role', 'einsatzverwaltung'),
            array_fill_keys(['edit_einsatzberichte', 'publish_einsatzberichte'], true)
        );
    }
}
