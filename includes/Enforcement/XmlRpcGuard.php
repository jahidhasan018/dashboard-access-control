<?php
declare(strict_types=1);

namespace DashboardAccessControl\Enforcement;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * XML-RPC guard — blocks XML-RPC access per role or globally.
 */
final class XmlRpcGuard {

	private RoleResolver $resolver;

	public function __construct( RoleResolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Hook into xmlrpc_enabled filter.
	 */
	public function init(): void {
		add_filter( 'xmlrpc_enabled', [ $this, 'check_xmlrpc' ] );
	}

	/**
	 * Check if XML-RPC should be enabled.
	 *
	 * @param bool $enabled Whether enabled.
	 * @return bool
	 */
	public function check_xmlrpc( bool $enabled ): bool {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return $enabled;
		}

		if ( $this->is_excluded( $user ) ) {
			return $enabled;
		}

		$profile  = $this->resolver->resolve( $user );
		$security = $profile[ Constants::PROFILE_SECURITY ] ?? [];

		if ( isset( $security['xmlrpc_enabled'] ) && ! $security['xmlrpc_enabled'] ) {
			return false;
		}

		return $enabled;
	}

	/**
	 * Check if a user is excluded from enforcement.
	 */
	private function is_excluded( \WP_User $user ): bool {
		$general  = get_option( Constants::OPT_GENERAL, [] );
		$excluded = $general[ Constants::GENERAL_EXCLUDE_ADMINS ] ?? true;

		if ( ! in_array( 'administrator', $user->roles, true ) ) {
			return false;
		}

		if ( ! $excluded ) {
			return false;
		}

		return (bool) apply_filters( 'dac_is_user_excluded', true, $user );
	}
}
