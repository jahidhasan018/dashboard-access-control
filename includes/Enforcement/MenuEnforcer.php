<?php
declare(strict_types=1);

namespace DashboardAccessControl\Enforcement;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Layer 1 — Visual hide: removes menu items from the admin sidebar.
 */
final class MenuEnforcer {

	private RoleResolver $resolver;

	/** @var array<string, string> Known core menu slugs → capabilities. */
	public const MENU_CAPABILITY_MAP = [
		'index.php'                 => '',
		'edit.php'                  => 'edit_posts',
		'edit.php?post_type=page'   => 'edit_pages',
		'edit.php?post_type=attachment' => 'edit_posts',
		'upload.php'                => 'upload_files',
		'edit-comments.php'         => 'moderate_comments',
		'theme-editor.php'          => 'edit_themes',
		'plugins.php'               => 'activate_plugins',
		'users.php'                 => 'list_users',
		'tools.php'                 => '',
		'options-general.php'       => 'manage_options',
		'edit-tags.php?taxonomy=category' => 'manage_categories',
		'edit-tags.php?taxonomy=post_tag' => 'manage_categories',
		'link-manager.php'          => 'manage_links',
		'edit.php?post_type=shop_order' => 'manage_woocommerce_orders',
		'woocommerce'               => 'manage_woocommerce',
	];

	public function __construct( RoleResolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Hook into admin_menu at priority 999 (late, after all menus registered).
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'enforce' ], 999 );
	}

	/**
	 * Remove menu items based on the current user's resolved profile.
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
		$menus   = $profile[ Constants::PROFILE_MENUS ] ?? [];

		foreach ( $menus as $menu ) {
			if ( empty( $menu['hidden'] ) ) {
				continue;
			}

			$slug = $menu['slug'] ?? '';
			if ( '' === $slug ) {
				continue;
			}

			remove_menu_page( $slug );

			// Also remove submenus that match.
			remove_submenu_page( $slug, $slug );
		}

		/**
		 * Action fired after menu enforcement.
		 *
		 * @param \WP_User $user    Current user.
		 * @param array    $profile Resolved profile.
		 */
		do_action( 'dac_after_enforce_menu', $user, $profile );
	}

	/**
	 * Get the capability map (filterable).
	 *
	 * @return array<string, string>
	 */
	public static function get_capability_map(): array {
		$map = self::MENU_CAPABILITY_MAP;

		/**
		 * Filter the menu slug → capability map.
		 *
		 * @param array<string, string> $map Map of menu slugs to capabilities.
		 */
		return apply_filters( 'dac_registered_menu_capability_map', $map );
	}

	/**
	 * Check if a user is excluded from enforcement.
	 * Bug 9 fix: delegated to RoleResolver::is_excluded() — was duplicated here with a raw get_option() call.
	 */
	private function is_excluded( \WP_User $user ): bool {
		return $this->resolver->is_excluded( $user );
	}
}
