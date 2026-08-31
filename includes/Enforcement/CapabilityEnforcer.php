<?php
declare(strict_types=1);

namespace DashboardAccessControl\Enforcement;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Layer 2 — Capability revocation via user_has_cap filter.
 * This is the REAL gate — even if someone reaches a hidden page by URL,
 * they won't have the capability to do anything on it.
 */
final class CapabilityEnforcer {

	private RoleResolver $resolver;

	public function __construct( RoleResolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Hook into the user_has_cap filter.
	 */
	public function init(): void {
		add_filter( 'user_has_cap', [ $this, 'filter_caps' ], 10, 4 );
	}

	/**
	 * Filter user capabilities to revoke caps tied to hidden menus.
	 *
	 * @param array<int, bool> $user_caps    User's capabilities.
	 * @param array<int, bool> $required_caps Required caps.
	 * @param array<int, ...>  $args         [0] = cap name, [1] = user ID, etc.
	 * @return array<int, bool>
	 */
	public function filter_caps( array $user_caps, array $required_caps, array $args, \WP_User $user ): array {
		if ( ! $user || ! $user->exists() ) {
			return $user_caps;
		}

		if ( $this->is_excluded( $user ) ) {
			return $user_caps;
		}

		$profile = $this->resolver->resolve( $user );
		$menus   = $profile[ Constants::PROFILE_MENUS ] ?? [];

		$hidden_slugs = [];
		foreach ( $menus as $menu ) {
			if ( ! empty( $menu['hidden'] ) ) {
				$hidden_slugs[] = $menu['slug'] ?? '';
			}
		}

		if ( empty( $hidden_slugs ) ) {
			return $user_caps;
		}

		$cap_map      = MenuEnforcer::get_capability_map();
		$revoke_caps  = [];

		foreach ( $hidden_slugs as $slug ) {
			if ( isset( $cap_map[ $slug ] ) && '' !== $cap_map[ $slug ] ) {
				$revoke_caps[] = $cap_map[ $slug ];
			}
		}

		// Also revoke capabilities that are meta-cap equivalents for hidden items.
		foreach ( $revoke_caps as $cap ) {
			if ( isset( $user_caps[ $cap ] ) ) {
				$user_caps[ $cap ] = false;
			}
		}

		// Check meta caps (edit_post, delete_post, etc.) by checking the object's post type
		// against hidden menu slugs — if the post type's menu is hidden, revoke its caps.
		if ( isset( $args[0] ) && in_array( $args[0], [ 'edit_post', 'delete_post', 'read_post' ], true ) && isset( $args[1] ) ) {
			$post_id   = (int) $args[1];
			$post_type = get_post_type( $post_id );
			if ( $post_type ) {
				$type_menu = 'edit.php?post_type=' . $post_type;
				if ( in_array( $type_menu, $hidden_slugs, true ) || in_array( 'edit.php', $hidden_slugs, true ) ) {
					$user_caps[ $args[0] ] = false;
				}
			}
		}

		return $user_caps;
	}

	/**
	 * Check if a user is excluded from enforcement.
	 */
	private function is_excluded( \WP_User $user ): bool {
		$general  = get_option( Constants::OPT_GENERAL, [] );
		$excluded = $general[ Constants::GENERAL_EXCLUDE_ADMINS ] ?? true;

		if ( $excluded && in_array( 'administrator', $user->roles, true ) ) {
			return true;
		}

		return (bool) apply_filters( 'dac_is_user_excluded', $excluded, $user );
	}
}
