<?php
declare(strict_types=1);

namespace DashboardAccessControl\Enforcement;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Layer 3 — Route guard: blocks direct URL access to hidden pages.
 */
final class RouteGuard {

	private RoleResolver $resolver;

	public function __construct( RoleResolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Hook into current_screen and admin_init for route checking.
	 */
	public function init(): void {
		add_action( 'current_screen', [ $this, 'check_screen' ] );
		add_action( 'admin_init', [ $this, 'check_admin_init' ] );
	}

	/**
	 * Check access on current_screen (fires after screen is set up).
	 *
	 * @param \WP_Screen $screen Current screen object.
	 */
	public function check_screen( \WP_Screen $screen ): void {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return;
		}

		if ( $this->is_excluded( $user ) ) {
			return;
		}

		$page = $screen->id ?? '';
		if ( '' === $page ) {
			return;
		}

		// Map screen IDs to menu slugs for comparison.
		$slug = $this->screen_to_slug( $page );
		if ( '' === $slug ) {
			return;
		}

		if ( $this->resolver->is_hidden( $user, Constants::PROFILE_MENUS, $slug ) ) {
			$this->deny_access();
		}
	}

	/**
	 * Check on admin_init for non-screen requests (e.g., direct admin.php?page= calls).
	 */
	public function check_admin_init(): void {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return;
		}

		if ( $this->is_excluded( $user ) ) {
			return;
		}

		if ( ! isset( $_GET['page'] ) ) {
			return;
		}

		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		if ( '' === $page ) {
			return;
		}

		// Check if this page slug is hidden.
		$slug = $this->page_to_slug( $page );
		if ( '' === $slug ) {
			return;
		}

		if ( $this->resolver->is_hidden( $user, Constants::PROFILE_MENUS, $slug ) ) {
			$this->deny_access();
		}
	}

	/**
	 * Deny access with a clean message or redirect.
	 */
	private function deny_access(): void {
		$general   = get_option( Constants::OPT_GENERAL, [] );
		$redirect  = $general['redirect_on_denied'] ?? false;

		if ( $redirect ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		wp_die(
			esc_html__( 'You do not have permission to access this page.', 'dashboard-access-control' ),
			esc_html__( 'Access Denied', 'dashboard-access-control' ),
			[ 'response' => 403 ]
		);
	}

	/**
	 * Convert a WP_Screen ID to a menu slug for matching.
	 */
	private function screen_to_slug( string $screen_id ): string {
		// Direct matches.
		$map = [
			'toplevel_page_plugins'                  => 'plugins.php',
			'toplevel_page_edit'                     => 'edit.php',
			'toplevel_page_upload'                   => 'upload.php',
			'toplevel_page-users'                    => 'users.php',
			'toplevel_page_tools'                    => 'tools.php',
			'toplevel_page_options-general'          => 'options-general.php',
			'toplevel_page_comments'                 => 'edit-comments.php',
			'toplevel_page_themes'                   => 'themes.php',
		];

		if ( isset( $map[ $screen_id ] ) ) {
			return $map[ $screen_id ];
		}

		// Custom post type screens: post_page_{post_type}.
		if ( str_starts_with( $screen_id, 'post_page_' ) ) {
			$post_type = str_replace( 'post_page_', '', $screen_id );
			return 'edit.php?post_type=' . $post_type;
		}

		// Taxonomy screens: edit-{taxonomy}.
		if ( str_starts_with( $screen_id, 'edit-' ) ) {
			$taxonomy = str_replace( 'edit-', '', $screen_id );
			return 'edit-tags.php?taxonomy=' . $taxonomy;
		}

		// submenu_match: {parent}_{slug}.
		$parts = explode( '_', $screen_id, 3 );
		if ( count( $parts ) >= 2 ) {
			return $parts[1] . '.php';
		}

		return $screen_id;
	}

	/**
	 * Convert a page query var to a menu slug for matching.
	 */
	private function page_to_slug( string $page ): string {
		// Map known page slugs.
		$known = [
			'plugins'             => 'plugins.php',
			'themes'              => 'themes.php',
			'editor'              => 'theme-editor.php',
			'plugin-editor'       => 'plugin-editor.php',
			'users'               => 'users.php',
			'profile'             => 'profile.php',
			'tools'               => 'tools.php',
			'import'              => 'import.php',
			'export'              => 'export.php',
			'export-personal-data' => 'export-personal-data.php',
			'erase-personal-data'  => 'erase-personal-data.php',
			'general'             => 'options-general.php',
			'reading'             => 'options-reading.php',
			'writing'             => 'options-writing.php',
			'permalink'           => 'options-permalink.php',
			'media'               => 'options-media.php',
			'comments'            => 'edit-comments.php',
		];

		if ( isset( $known[ $page ] ) ) {
			return $known[ $page ];
		}

		return $page . '.php';
	}

	/**
	 * Check if a user is excluded from enforcement.
	 * Bug 9 fix: delegated to RoleResolver::is_excluded() — was duplicated here with a raw get_option() call.
	 */
	private function is_excluded( \WP_User $user ): bool {
		return $this->resolver->is_excluded( $user );
	}
}
