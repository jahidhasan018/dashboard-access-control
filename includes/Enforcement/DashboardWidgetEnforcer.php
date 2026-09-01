<?php
declare(strict_types=1);

namespace DashboardAccessControl\Enforcement;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard widget enforcer — Layer 1.
 * Removes widgets from the dashboard based on role profile.
 */
final class DashboardWidgetEnforcer {

	private RoleResolver $resolver;

	public function __construct( RoleResolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Hook into wp_dashboard_setup at late priority.
	 */
	public function init(): void {
		add_action( 'wp_dashboard_setup', [ $this, 'enforce' ], 999 );
	}

	/**
	 * Remove dashboard widgets based on the current user's resolved profile.
	 */
	public function enforce(): void {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return;
		}

		if ( $this->is_excluded( $user ) ) {
			return;
		}

		$profile = $this->resolver->resolve( $user );
		$widgets = $profile[ Constants::PROFILE_WIDGETS ] ?? [];

		if ( empty( $widgets ) ) {
			return;
		}

		global $wp_meta_boxes;

		foreach ( $widgets as $widget_id => $hidden ) {
			if ( ! $hidden ) {
				continue;
			}
			$widget_id = (string) $widget_id;

			// Remove from all dashboard contexts and priorities.
			foreach ( [ 'normal', 'side', 'column3', 'column4' ] as $context ) {
				foreach ( [ 'core', 'high', 'default', 'low' ] as $priority ) {
					if ( isset( $wp_meta_boxes['dashboard'][ $context ][ $priority ][ $widget_id ] ) ) {
						unset( $wp_meta_boxes['dashboard'][ $context ][ $priority ][ $widget_id ] );
					}
				}
				remove_meta_box( $widget_id, 'dashboard', $context );
			}
		}
	}

	/**
	 * Check if a user is excluded from enforcement.
	 * Bug 9 fix: delegated to RoleResolver::is_excluded() — was duplicated here with a raw get_option() call.
	 */
	private function is_excluded( \WP_User $user ): bool {
		return $this->resolver->is_excluded( $user );
	}
}
