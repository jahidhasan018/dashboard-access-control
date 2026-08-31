<?php
declare(strict_types=1);

namespace DashboardAccessControl\Enforcement;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Layer 4 — REST API guard: blocks REST requests for hidden features.
 */
final class RestGuard {

	private RoleResolver $resolver;

	/**
	 * Map of REST route patterns → required menu slug.
	 *
	 * @var array<string, string>
	 */
	private const ROUTE_MAP = [
		'/wp/v2/posts'           => 'edit.php',
		'/wp/v2/pages'           => 'edit.php?post_type=page',
		'/wp/v2/comments'        => 'edit-comments.php',
		'/wp/v2/categories'      => 'edit-tags.php?taxonomy=category',
		'/wp/v2/tags'            => 'edit-tags.php?taxonomy=post_tag',
		'/wp/v2/media'           => 'upload.php',
		'/wp/v2/users'           => 'users.php',
		'/wp/v2/themes'          => 'themes.php',
		'/wp/v2/plugins'         => 'plugins.php',
		'/wp/v2/settings'        => 'options-general.php',
		'/wp/v2/block-editor'    => 'edit.php',
		'/wp/v2/template-parts'  => 'themes.php',
		'/wp/v2/templates'       => 'themes.php',
		'/wp/v2/global-styles'   => 'themes.php',
		'/wp/v2/patterns'        => 'themes.php',
		'/wp/v2/menu-items'      => 'nav-menus.php',
		'/wp/v2/menus'           => 'nav-menus.php',
		'/wp/v2/navigation'      => 'nav-menus.php',
	];

	public function __construct( RoleResolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Hook into rest_authentication_errors to check permissions.
	 */
	public function init(): void {
		add_filter( 'rest_authentication_errors', [ $this, 'check_rest_request' ], 1 );
	}

	/**
	 * Check if the current REST request should be blocked.
	 *
	 * @param mixed $result Authentication result.
	 * @return mixed
	 */
	public function check_rest_request( $result ) {
		// If already failed authentication, don't interfere.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! rest_doing_request() ) {
			return $result;
		}

		$request = rest_get_server()->get_request();
		$route   = $request->get_route();

		if ( '' === $route ) {
			return $result;
		}

		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return $result;
		}

		if ( $this->is_excluded( $user ) ) {
			return $result;
		}

		// Check against the route map.
		foreach ( self::ROUTE_MAP as $pattern => $menu_slug ) {
			if ( str_starts_with( $route, $pattern ) ) {
				if ( $this->resolver->is_hidden( $user, Constants::PROFILE_MENUS, $menu_slug ) ) {
					return new \WP_Error(
						'dac_rest_forbidden',
						__( 'You do not have permission to access this endpoint.', 'dashboard-access-control' ),
						[ 'status' => 403 ]
					);
				}
				break;
			}
		}

		return $result;
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
