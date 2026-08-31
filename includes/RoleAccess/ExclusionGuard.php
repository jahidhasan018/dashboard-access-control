<?php
declare(strict_types=1);

namespace DashboardAccessControl\RoleAccess;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prevents locking out the last administrator.
 */
final class ExclusionGuard {

	private RoleProfileRepository $repository;
	private Options $options;

	public function __construct( RoleProfileRepository $repository, Options $options ) {
		$this->repository = $repository;
		$this->options    = $options;
	}

	/**
	 * Check if saving a profile would lock out the last admin.
	 *
	 * @param string $role_slug The role being saved.
	 * @param array  $profile   The proposed profile.
	 * @return true|\WP_Error True if safe, WP_Error if it would lock out.
	 */
	public function check( string $role_slug, array $profile ): true|\WP_Error {
		$general  = $this->options->get( Constants::OPT_GENERAL, [] );
		$excluded = $general[ Constants::GENERAL_EXCLUDE_ADMINS ] ?? true;

		// If admins are excluded from restrictions, check if this is the admin role.
		if ( $excluded && 'administrator' === $role_slug ) {
			$menus = $profile[ Constants::PROFILE_MENUS ] ?? [];
			$restrictions = $profile[ Constants::PROFILE_RESTRICTIONS ] ?? [];

			// Check for full dashboard lockdown.
			$all_hidden = true;
			foreach ( $menus as $menu ) {
				if ( empty( $menu['hidden'] ) ) {
					$all_hidden = false;
					break;
				}
			}

			if ( $all_hidden && ! empty( $menus ) ) {
				return new \WP_Error(
					'dac_lockout_admin',
					__( 'You cannot hide all admin menu items for the administrator role.', 'dashboard-access-control' )
				);
			}

			// Check for file editor disable (dangerous for sole admin).
			if ( ! empty( $restrictions['disable_file_editor'] ) ) {
				$admin_count = $this->count_users_with_role( 'administrator' );
				if ( $admin_count <= 1 ) {
					return new \WP_Error(
						'dac_lockout_editor',
						__( 'You cannot disable the file editor as the only administrator.', 'dashboard-access-control' )
					);
				}
			}
		}

		// Always check if the role being saved is the only role with admins.
		if ( 'administrator' === $role_slug ) {
			$admin_count = $this->count_users_with_role( 'administrator' );
			if ( $admin_count <= 1 ) {
				$user = wp_get_current_user();
				if ( in_array( 'administrator', $user->roles, true ) ) {
					$menus = $profile[ Constants::PROFILE_MENUS ] ?? [];
					$hidden_count = 0;
					foreach ( $menus as $menu ) {
						if ( ! empty( $menu['hidden'] ) ) {
							++$hidden_count;
						}
					}

					if ( $hidden_count > 0 ) {
						return new \WP_Error(
							'dac_lockout_self',
							__( 'You cannot restrict admin menu items for yourself as the only administrator.', 'dashboard-access-control' )
						);
					}
				}
			}
		}

		return true;
	}

	/**
	 * Count users with a specific role.
	 */
	private function count_users_with_role( string $role ): int {
		$users = get_users( [ 'role' => $role ] );
		return count( $users );
	}
}
