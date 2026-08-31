<?php
declare(strict_types=1);

namespace DashboardAccessControl\Enforcement;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Layer 4 — AJAX guard: blocks AJAX calls for hidden features.
 */
final class AjaxGuard {

	private RoleResolver $resolver;

	/**
	 * Map of AJAX action names → required feature slug.
	 * Empty slug means the action is always allowed.
	 *
	 * @var array<string, string>
	 */
	private const ACTION_MAP = [
		'heartbeat'                    => '',
		'wp_ajax_nopriv_autosave'      => '',
		'ajax-tag-search'              => '',
		'wp_ajax_inline_save'          => 'edit_posts',
		'wp_ajax_get-comments'         => 'edit_comments',
		'wp_ajax_delete-comment'       => 'edit_comments',
		'wp_ajax_approvecomment'       => 'edit_comments',
		'wp_ajax_unapprovecomment'     => 'edit_comments',
		'wp_ajax_spamcomment'          => 'edit_comments',
		'wp_ajax_trashcomment'         => 'edit_comments',
		'wp_ajax_editcomment'          => 'edit_comments',
		'wp_ajax-add-meta'             => 'edit_posts',
		'wp_ajax-stat1'                => '',
		'wp_ajax-widget-form'          => '',
		'wp_ajax_save-widget'          => 'edit_theme_options',
		'wp_ajax_save-menu'            => 'edit_theme_options',
		'wp_ajax_menu-get-post'        => 'edit_theme_options',
		'wp_ajax_install-plugin'       => 'install_plugins',
		'wp_ajax_activate-plugin'      => 'activate_plugins',
		'wp_ajax_delete-plugin'        => 'delete_plugins',
		'wp_ajax-update-plugin'        => 'update_plugins',
		'wp_ajax-install-theme'        => 'install_themes',
		'wp_ajax-delete-theme'         => 'delete_themes',
		'wp_ajax_update-theme'         => 'update_themes',
		'wp_ajax_press-this-save-post' => 'edit_posts',
		'wp_ajax_press-this-add-tag'   => 'edit_posts',
		'wp_ajax_fetch-list'           => '',
		'wp_ajax-ajax-tagcloud'        => '',
		'wp_ajax-color-picker'         => '',
		'wp_ajax_image-editor'         => 'edit_posts',
		'wp_ajax_set-post-thumbnail'   => 'edit_posts',
	];

	public function __construct( RoleResolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Hook into admin_init to check AJAX requests.
	 */
	public function init(): void {
		add_action( 'admin_init', [ $this, 'check_ajax_request' ] );
	}

	/**
	 * Check if the current AJAX request should be blocked.
	 */
	public function check_ajax_request(): void {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
		if ( '' === $action ) {
			return;
		}

		// Check the action map.
		$required_feature = self::ACTION_MAP[ $action ] ?? null;
		if ( null === $required_feature ) {
			// Unknown action — allow by default but log.
			return;
		}

		if ( '' === $required_feature ) {
			return; // Always allowed.
		}

		// Check if the user has the required capability via our enforcement.
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return;
		}

		if ( $this->is_excluded( $user ) ) {
			return;
		}

		// Check if the menu associated with this capability is hidden.
		$cap_map = MenuEnforcer::get_capability_map();
		$hidden  = false;

		foreach ( $cap_map as $slug => $cap ) {
			if ( $cap === $required_feature ) {
				if ( $this->resolver->is_hidden( $user, Constants::PROFILE_MENUS, $slug ) ) {
					$hidden = true;
					break;
				}
			}
		}

		if ( $hidden ) {
			wp_send_json_error(
				[
					'message' => __( 'You do not have permission to perform this action.', 'dashboard-access-control' ),
				],
				403
			);
			exit;
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
