<?php
declare(strict_types=1);

namespace DashboardAccessControl\Support;

use DashboardAccessControl\Core\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom capability registration helper.
 */
final class Capabilities {

	/**
	 * Register the plugin's custom capabilities on the administrator role.
	 * Called on activation and available for re-seeding if needed.
	 */
	public static function seed(): void {
		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( Constants::CAP_MANAGE_SETTINGS ) ) {
			$admin->add_cap( Constants::CAP_MANAGE_SETTINGS );
		}
	}

	/**
	 * Remove all plugin-specific capabilities from all roles.
	 * Used during uninstall when data deletion is opted in.
	 */
	public static function remove_all(): void {
		global $wp_roles;

		$caps_to_remove = [
			Constants::CAP_MANAGE_SETTINGS,
		];

		foreach ( $wp_roles->roles as $role_slug => $role_data ) {
			$role = get_role( $role_slug );
			if ( $role ) {
				foreach ( $caps_to_remove as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}
}
