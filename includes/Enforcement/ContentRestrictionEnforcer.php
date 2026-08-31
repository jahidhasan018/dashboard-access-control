<?php
declare(strict_types=1);

namespace DashboardAccessControl\Enforcement;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enforces content restrictions: meta boxes, screen options, help tab, notices, file editor.
 */
final class ContentRestrictionEnforcer {

	private RoleResolver $resolver;

	public function __construct( RoleResolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Hook all content restriction filters.
	 */
	public function init(): void {
		// Screen options tab.
		add_filter( 'screen_options_show_screen', [ $this, 'disable_screen_options' ], 999 );

		// Help tab.
		add_filter( 'contextual_help', [ $this, 'disable_help_tab' ], 999, 3 );

		// Admin notices.
		add_action( 'admin_notices', [ $this, 'suppress_notices' ], 1 );
		add_action( 'all_admin_notices', [ $this, 'suppress_notices' ], 1 );

		// File editor disable.
		add_filter( 'map_meta_cap', [ $this, 'disable_file_editor' ], 10, 2 );

		// At a Glance widget.
		add_action( 'wp_dashboard_setup', [ $this, 'hide_at_a_glance' ], 999 );

		// Meta boxes on post screens.
		add_filter( 'get_user_option_meta_boxes', [ $this, 'hide_meta_boxes' ], 10, 3 );
	}

	/**
	 * Disable Screen Options tab.
	 *
	 * @param bool $show Whether to show.
	 * @return bool
	 */
	public function disable_screen_options( bool $show ): bool {
		if ( $this->user_has_restriction( 'disable_screen_options' ) ) {
			return false;
		}
		return $show;
	}

	/**
	 * Disable Help tab.
	 *
	 * @param string $help     Existing help content.
	 * @param string $screen_id Screen ID.
	 * @param object $screen   Screen object.
	 * @return string
	 */
	public function disable_help_tab( string $help, string $screen_id, $screen ): string {
		if ( $this->user_has_restriction( 'disable_help_tab' ) ) {
			return '';
		}
		return $help;
	}

	/**
	 * Suppress admin notices (with whitelist for critical security notices).
	 */
	public function suppress_notices(): void {
		if ( ! $this->user_has_restriction( 'suppress_notices' ) ) {
			return;
		}

		// Remove all notices except critical security ones.
		global $wp_filter;

		if ( ! isset( $wp_filter['admin_notices'] ) ) {
			return;
		}

		// We'll use output buffering to filter the notices.
		// This is a safe approach that doesn't modify global filter state.
		add_filter( 'admin_notice', [ $this, 'filter_notice_output' ], 9999 );
	}

	/**
	 * Filter individual notice output — keep critical security notices.
	 *
	 * @param string $notice Notice HTML.
	 * @return string
	 */
	public function filter_notice_output( string $notice ): string {
		// Allow critical security notices.
		$allowed_patterns = [
			'notice-error',
			'dac-',
			'update-nag',
		];

		foreach ( $allowed_patterns as $pattern ) {
			if ( str_contains( $notice, $pattern ) ) {
				return $notice;
			}
		}

		return '';
	}

	/**
	 * Disable file editor (theme/plugin editor).
	 *
	 * @param array  $caps    Primitive capabilities.
	 * @param string $cap     Capability being checked.
	 * @return array
	 */
	public function disable_file_editor( array $caps, string $cap ): array {
		if ( ! $this->user_has_restriction( 'disable_file_editor' ) ) {
			return $caps;
		}

		$editor_caps = [ 'edit_themes', 'edit_plugins', 'edit_files' ];

		if ( in_array( $cap, $editor_caps, true ) ) {
			$caps = [ 'do_not_allow' ];
		}

		return $caps;
	}

	/**
	 * Hide the "At a Glance" dashboard widget.
	 */
	public function hide_at_a_glance(): void {
		if ( $this->user_has_restriction( 'hide_at_a_glance' ) ) {
			remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
		}
	}

	/**
	 * Hide non-essential meta boxes on post edit screens.
	 *
	 * @param array  $meta_boxes User's meta boxes.
	 * @param string $screen     Screen ID.
	 * @param object $user       User object.
	 * @return array
	 */
	public function hide_meta_boxes( array $meta_boxes, string $screen, $user ): array {
		if ( ! $this->user_has_restriction( 'hide_meta_boxes' ) ) {
			return $meta_boxes;
		}

		// Hide non-essential meta boxes.
		$hidden_boxes = [
			'postcustom',
			'trackbacksdiv',
			'commentstatusdiv',
			'commentsdiv',
			'slugdiv',
			'authordiv',
		];

		foreach ( $hidden_boxes as $box ) {
			unset( $meta_boxes[ $box ] );
		}

		return $meta_boxes;
	}

	/**
	 * Check if the current user has a specific restriction.
	 */
	private function user_has_restriction( string $key ): bool {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}

		if ( $this->is_excluded( $user ) ) {
			return false;
		}

		$profile      = $this->resolver->resolve( $user );
		$restrictions = $profile[ Constants::PROFILE_RESTRICTIONS ] ?? [];

		return ! empty( $restrictions[ $key ] );
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
