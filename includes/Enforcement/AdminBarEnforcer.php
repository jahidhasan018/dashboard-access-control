<?php
declare(strict_types=1);

namespace DashboardAccessControl\Enforcement;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin bar enforcer — controls visibility per role.
 *
 * Front-end: uses show_admin_bar() + body class.
 * Back-end: hides via CSS body class — never blocks dashboard access.
 * Node removal: removes specific admin bar nodes via admin_bar_menu hook.
 */
final class AdminBarEnforcer {

	private RoleResolver $resolver;

	public function __construct( RoleResolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Hook into WordPress.
	 */
	public function init(): void {
		// Front-end admin bar control.
		add_action( 'wp', [ $this, 'handle_frontend' ] );
		add_action( 'wp_before_admin_bar_render', [ $this, 'handle_backend_css' ], 1 );

		// Node removal.
		add_action( 'admin_bar_menu', [ $this, 'remove_nodes' ], 999 );
	}

	/**
	 * Hide admin bar on front-end per role.
	 */
	public function handle_frontend(): void {
		if ( is_admin() ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return;
		}

		if ( $this->is_excluded( $user ) ) {
			return;
		}

		$profile = $this->resolver->resolve( $user );
		$bar     = $profile[ Constants::PROFILE_ADMIN_BAR ] ?? [];

		if ( ! empty( $bar['hide_frontend'] ) ) {
			show_admin_bar( false );
		}
	}

	/**
	 * Add body class to hide admin bar on back-end via CSS.
	 * This does NOT block wp-admin access — only hides the toolbar visually.
	 */
	public function handle_backend_css(): void {
		if ( ! is_admin() ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return;
		}

		if ( $this->is_excluded( $user ) ) {
			return;
		}

		$profile = $this->resolver->resolve( $user );
		$bar     = $profile[ Constants::PROFILE_ADMIN_BAR ] ?? [];

		if ( ! empty( $bar['hide_backend'] ) ) {
			add_action( 'admin_body_class', [ $this, 'add_hide_body_class' ] );
			add_action( 'wp_head', [ $this, 'hide_admin_bar_css' ], 999 );
		}
	}

	/**
	 * Add body class for backend admin bar hiding.
	 *
	 * @param string $classes Existing body classes.
	 * @return string
	 */
	public function add_hide_body_class( string $classes ): string {
		return $classes . ' dac-hide-admin-bar';
	}

	/**
	 * Output CSS to hide the admin bar on back-end.
	 */
	public function hide_admin_bar_css(): void {
		echo '<style>#adminmenuback, #adminmenubox, #adminbar { display: none !important; } body { margin-top: 0 !important; padding-top: 0 !important; }</style>' . "\n";
	}

	/**
	 * Remove specific admin bar nodes per role.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public function remove_nodes( \WP_Admin_Bar $wp_admin_bar ): void {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return;
		}

		if ( $this->is_excluded( $user ) ) {
			return;
		}

		$profile = $this->resolver->resolve( $user );
		$bar     = $profile[ Constants::PROFILE_ADMIN_BAR ] ?? [];
		$removed = $bar['removed_nodes'] ?? [];

		if ( empty( $removed ) ) {
			return;
		}

		foreach ( $removed as $node_id ) {
			$wp_admin_bar->remove_menu( $node_id );
		}
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
