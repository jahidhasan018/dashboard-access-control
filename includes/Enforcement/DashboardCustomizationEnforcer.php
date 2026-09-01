<?php
declare(strict_types=1);

namespace DashboardAccessControl\Enforcement;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enforces dashboard customization settings: screen options, help tab,
 * full-width layout, and widget dragging.
 */
final class DashboardCustomizationEnforcer {

	private RoleResolver $resolver;

	public function __construct( RoleResolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Hook all dashboard customization filters.
	 */
	public function init(): void {
		add_action( 'admin_head', [ $this, 'apply_customizations' ] );
	}

	/**
	 * Apply all dashboard customization styles and scripts.
	 */
	public function apply_customizations(): void {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return;
		}

		if ( $this->is_excluded( $user ) ) {
			return;
		}

		$profile  = $this->resolver->resolve( $user );
		$dash     = $profile[ Constants::PROFILE_DASHBOARD ] ?? [];

		if ( empty( $dash ) ) {
			return;
		}

		$remove_screen = ! empty( $dash[ Constants::DASH_REMOVE_SCREEN_OPTIONS ] );
		$remove_help   = ! empty( $dash[ Constants::DASH_REMOVE_HELP_TAB ] );
		$full_width    = ! empty( $dash[ Constants::DASH_FULL_WIDTH ] );
		$no_drag       = ! empty( $dash[ Constants::DASH_DISABLE_DRAGGING ] );

		if ( ! $remove_screen && ! $remove_help && ! $full_width && ! $no_drag ) {
			return;
		}

		$css = '';
		$js  = '';

		// Full-width dashboard.
		if ( $full_width ) {
			$css .= '
				#dashboard-widgets .postbox-container { width: 100% !important; }
				#dashboard-widgets .metabox-holder { display: block !important; }
				#dashboard-widgets .metabox-holder .postbox-container { float: none !important; }
				.wrap { max-width: 100% !important; padding-right: 20px; }
			';
		}

		// Disable widget dragging.
		if ( $no_drag ) {
			$css .= '
				.wp-core-ui .postbox .handlediv,
				.wp-core-ui .postbox .handle-order-higher,
				.wp-core-ui .postbox .handle-order-lower { display: none !important; }
				.wp-core-ui .postbox .hndle { cursor: default !important; }
			';
			$js .= '
				jQuery(window).on("load", function() {
					if (jQuery(".meta-box-sortables").length) {
						jQuery(".meta-box-sortables").sortable("destroy");
					}
				});
			';
		}

		if ( '' !== $css ) {
			printf( '<style id="dac-dashboard-customization">%s</style>' . "\n", $css );
		}

		if ( '' !== $js ) {
			printf( '<script id="dac-dashboard-customization-js">%s</script>' . "\n", $js );
		}

		// Screen Options removal — use filter.
		if ( $remove_screen ) {
			add_filter( 'screen_options_show_screen', '__return_false' );
		}

		// Help tab removal — remove all help tabs on current screen.
		if ( $remove_help ) {
			$screen = get_current_screen();
			if ( is_object( $screen ) && method_exists( $screen, 'remove_help_tabs' ) ) {
				$screen->remove_help_tabs();
			}
		}
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
