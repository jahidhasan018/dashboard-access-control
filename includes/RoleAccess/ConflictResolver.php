<?php
declare(strict_types=1);

namespace DashboardAccessControl\RoleAccess;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Merge multiple role profiles according to the configured conflict strategy.
 */
final class ConflictResolver {

	private Options $options;

	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Merge an array of role profiles into a single effective profile.
	 *
	 * @param array<int, array<string, mixed>> $profiles Profiles to merge.
	 * @return array<string, mixed>
	 */
	public function merge( array $profiles ): array {
		$general    = $this->options->get( Constants::OPT_GENERAL, [] );
		$strategy   = $general[ Constants::GENERAL_CONFLICT_STRATEGY ] ?? Constants::STRATEGY_LEAST_PRIVILEGE;
		$is_least   = ( Constants::STRATEGY_LEAST_PRIVILEGE === $strategy );

		$merged = [];

		// Merge menus: least privilege = hide if ANY role hides it.
		$all_menus = [];
		foreach ( $profiles as $profile ) {
			$menus = $profile[ Constants::PROFILE_MENUS ] ?? [];
			foreach ( $menus as $menu ) {
				$slug = $menu['slug'] ?? '';
				if ( '' === $slug ) {
					continue;
				}
				if ( ! isset( $all_menus[ $slug ] ) ) {
					$all_menus[ $slug ] = $menu;
				} else {
					$all_menus[ $slug ]['hidden'] = $is_least
						? ( $all_menus[ $slug ]['hidden'] || ! empty( $menu['hidden'] ) )
						: ( $all_menus[ $slug ]['hidden'] && ! empty( $menu['hidden'] ) );
				}
			}
		}
		$merged[ Constants::PROFILE_MENUS ] = array_values( $all_menus );

		// Merge widgets.
		$all_widgets = [];
		foreach ( $profiles as $profile ) {
			$widgets = $profile[ Constants::PROFILE_WIDGETS ] ?? [];
			foreach ( $widgets as $widget_id => $hidden ) {
				if ( ! isset( $all_widgets[ $widget_id ] ) ) {
					$all_widgets[ $widget_id ] = $hidden;
				} else {
					$all_widgets[ $widget_id ] = $is_least
						? ( $all_widgets[ $widget_id ] || $hidden )
						: ( $all_widgets[ $widget_id ] && $hidden );
				}
			}
		}
		$merged[ Constants::PROFILE_WIDGETS ] = $all_widgets;

		// Merge admin bar.
		$bar = [
			'hide_frontend' => false,
			'hide_backend'  => false,
			'removed_nodes' => [],
		];
		foreach ( $profiles as $profile ) {
			$pb = $profile[ Constants::PROFILE_ADMIN_BAR ] ?? [];
			if ( $is_least ) {
				$bar['hide_frontend'] = $bar['hide_frontend'] || ! empty( $pb['hide_frontend'] );
				$bar['hide_backend']  = $bar['hide_backend'] || ! empty( $pb['hide_backend'] );
			} else {
				$bar['hide_frontend'] = $bar['hide_frontend'] && ! empty( $pb['hide_frontend'] );
				$bar['hide_backend']  = $bar['hide_backend'] && ! empty( $pb['hide_backend'] );
			}
			$bar['removed_nodes'] = array_merge( $bar['removed_nodes'], $pb['removed_nodes'] ?? [] );
		}
		$bar['removed_nodes'] = array_unique( $bar['removed_nodes'] );
		$merged[ Constants::PROFILE_ADMIN_BAR ] = $bar;

		// Merge restrictions (OR for least privilege = if ANY restricts, restrict).
		$restrictions = [];
		foreach ( $profiles as $profile ) {
			$pr = $profile[ Constants::PROFILE_RESTRICTIONS ] ?? [];
			foreach ( $pr as $key => $value ) {
				if ( ! isset( $restrictions[ $key ] ) ) {
					$restrictions[ $key ] = $value;
				} else {
					$restrictions[ $key ] = $is_least
						? ( $restrictions[ $key ] || $value )
						: ( $restrictions[ $key ] && $value );
				}
			}
		}
		$merged[ Constants::PROFILE_RESTRICTIONS ] = $restrictions;

		// Merge security.
		$security = [ 'xmlrpc_enabled' => true ];
		foreach ( $profiles as $profile ) {
			$ps = $profile[ Constants::PROFILE_SECURITY ] ?? [];
			if ( isset( $ps['xmlrpc_enabled'] ) ) {
				$security['xmlrpc_enabled'] = $is_least
					? ( $security['xmlrpc_enabled'] && $ps['xmlrpc_enabled'] )
					: ( $security['xmlrpc_enabled'] || $ps['xmlrpc_enabled'] );
			}
		}
		$merged[ Constants::PROFILE_SECURITY ] = $security;

		return $merged;
	}
}
