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
		// Bug 11 fix: Initialize from first profile instead of hardcoding false/empty,
		// and use intersection for removed_nodes under most_permissive strategy.
		$bar = [
			'hide_frontend' => null,
			'hide_backend'  => null,
			'removed_nodes' => null,
		];
		foreach ( $profiles as $profile ) {
			$pb       = $profile[ Constants::PROFILE_ADMIN_BAR ] ?? [];
			$pb_front = ! empty( $pb['hide_frontend'] );
			$pb_back  = ! empty( $pb['hide_backend'] );
			$pb_nodes = (array) ( $pb['removed_nodes'] ?? [] );

			if ( null === $bar['hide_frontend'] ) {
				$bar['hide_frontend'] = $pb_front;
			} else {
				$bar['hide_frontend'] = $is_least
					? ( $bar['hide_frontend'] || $pb_front )
					: ( $bar['hide_frontend'] && $pb_front );
			}

			if ( null === $bar['hide_backend'] ) {
				$bar['hide_backend'] = $pb_back;
			} else {
				$bar['hide_backend'] = $is_least
					? ( $bar['hide_backend'] || $pb_back )
					: ( $bar['hide_backend'] && $pb_back );
			}

			if ( null === $bar['removed_nodes'] ) {
				$bar['removed_nodes'] = $pb_nodes;
			} else {
				$bar['removed_nodes'] = $is_least
					? array_unique( array_merge( $bar['removed_nodes'], $pb_nodes ) )
					: array_values( array_intersect( $bar['removed_nodes'], $pb_nodes ) );
			}
		}
		$bar['hide_frontend'] = $bar['hide_frontend'] ?? false;
		$bar['hide_backend']  = $bar['hide_backend'] ?? false;
		$bar['removed_nodes'] = $bar['removed_nodes'] ?? [];
		$merged[ Constants::PROFILE_ADMIN_BAR ] = $bar;

		// Merge restrictions (OR for least privilege = if ANY restricts, restrict).
		$restrictions = [];
		foreach ( $profiles as $profile ) {
			$pr = $profile[ Constants::PROFILE_RESTRICTIONS ] ?? [];
			foreach ( $pr as $key => $value ) {
				if ( ! isset( $restrictions[ $key ] ) ) {
					$restrictions[ $key ] = (bool) $value;
				} else {
					$restrictions[ $key ] = $is_least
						? ( $restrictions[ $key ] || ! empty( $value ) )
						: ( $restrictions[ $key ] && ! empty( $value ) );
				}
			}
		}
		$merged[ Constants::PROFILE_RESTRICTIONS ] = $restrictions;

		// Merge security.
		// Bug 11 fix: Initialize from first profile instead of hardcoding xmlrpc_enabled => true.
		$security = [];
		foreach ( $profiles as $profile ) {
			$ps = $profile[ Constants::PROFILE_SECURITY ] ?? [];
			foreach ( $ps as $key => $value ) {
				$bool_val = (bool) $value;
				if ( ! isset( $security[ $key ] ) ) {
					$security[ $key ] = $bool_val;
				} else {
					// For xmlrpc_enabled: true = enabled (permissive), false = disabled (restricted).
					// least privilege = false if any is false (AND).
					// most permissive = true if any is true (OR).
					$security[ $key ] = $is_least
						? ( $security[ $key ] && $bool_val )
						: ( $security[ $key ] || $bool_val );
				}
			}
		}
		if ( ! isset( $security['xmlrpc_enabled'] ) ) {
			$security['xmlrpc_enabled'] = true;
		}
		$merged[ Constants::PROFILE_SECURITY ] = $security;

		return $merged;
	}
}
