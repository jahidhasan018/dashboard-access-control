<?php
declare(strict_types=1);

namespace DashboardAccessControl\RoleAccess;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Given a WP_User, resolve the effective merged rule set across all their roles.
 */
final class RoleResolver {

	private RoleProfileRepository $repository;
	private ConflictResolver $conflict_resolver;

	public function __construct( RoleProfileRepository $repository, ConflictResolver $conflict_resolver ) {
		$this->repository       = $repository;
		$this->conflict_resolver = $conflict_resolver;
	}

	/**
	 * Get the effective profile for a user.
	 *
	 * @param \WP_User $user User object.
	 * @return array<string, mixed> Merged profile.
	 */
	public function resolve( \WP_User $user ): array {
		$role_profiles = $this->repository->get_all();
		$user_roles    = $user->roles;

		$relevant = [];
		foreach ( $user_roles as $role_slug ) {
			if ( isset( $role_profiles[ $role_slug ] ) ) {
				$relevant[] = $role_profiles[ $role_slug ];
			}
		}

		if ( empty( $relevant ) ) {
			return $this->empty_profile();
		}

		if ( 1 === count( $relevant ) ) {
			return $relevant[0];
		}

		return $this->conflict_resolver->merge( $relevant );
	}

	/**
	 * Check if a specific feature is hidden for a user.
	 */
	public function is_hidden( \WP_User $user, string $section, string $slug ): bool {
		$profile = $this->resolve( $user );

		if ( Constants::PROFILE_MENUS === $section ) {
			$menus = $profile[ Constants::PROFILE_MENUS ] ?? [];
			foreach ( $menus as $menu ) {
				if ( ( $menu['slug'] ?? '' ) === $slug && ! empty( $menu['hidden'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get an empty profile (no restrictions).
	 *
	 * @return array<string, mixed>
	 */
	private function empty_profile(): array {
		return [
			Constants::PROFILE_MENUS       => [],
			Constants::PROFILE_WIDGETS     => [],
			Constants::PROFILE_ADMIN_BAR   => [
				'hide_frontend'  => false,
				'hide_backend'   => false,
				'removed_nodes'  => [],
			],
			Constants::PROFILE_RESTRICTIONS => [
				'hide_meta_boxes'    => false,
				'disable_screen_options' => false,
				'disable_help_tab'   => false,
				'suppress_notices'   => false,
				'hide_at_a_glance'   => false,
				'disable_file_editor' => false,
			],
			Constants::PROFILE_SECURITY    => [
				'xmlrpc_enabled' => true,
			],
		];
	}
}
