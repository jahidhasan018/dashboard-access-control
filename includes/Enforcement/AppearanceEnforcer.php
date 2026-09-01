<?php
declare(strict_types=1);

namespace DashboardAccessControl\Enforcement;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enforces admin appearance settings per role by outputting custom CSS.
 */
final class AppearanceEnforcer {

	private RoleResolver $resolver;

	public function __construct( RoleResolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Hook into WordPress.
	 */
	public function init(): void {
		add_action( 'admin_head', [ $this, 'output_css' ] );
	}

	/**
	 * Output custom admin CSS for the current user's role.
	 */
	public function output_css(): void {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return;
		}

		if ( $this->is_excluded( $user ) ) {
			return;
		}

		$profile = $this->resolver->resolve( $user );
		$appear  = $profile[ Constants::PROFILE_APPEARANCE ] ?? [];

		if ( empty( $appear ) ) {
			return;
		}

		$css = [];

		// Sidebar background.
		$sidebar_bg = $appear[ Constants::APPEAR_SIDEBAR_BG ] ?? '';
		if ( '' !== $sidebar_bg ) {
			$css[] = sprintf( '#adminmenuback, #adminmenu, #adminmenuwrap { background-color: %s !important; }', esc_attr( $sidebar_bg ) );
		}

		// Sidebar text color.
		$sidebar_text = $appear[ Constants::APPEAR_SIDEBAR_TEXT ] ?? '';
		if ( '' !== $sidebar_text ) {
			$css[] = sprintf( '#adminmenu a, #adminmenu .wp-menu-name, #adminmenu .wp-menu-arrow { color: %s !important; }', esc_attr( $sidebar_text ) );
		}

		// Sidebar hover background.
		$sidebar_hover = $appear[ Constants::APPEAR_SIDEBAR_HOVER ] ?? '';
		if ( '' !== $sidebar_hover ) {
			$css[] = sprintf( '#adminmenu a:hover, #adminmenu li.current a, #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu { background-color: %s !important; }', esc_attr( $sidebar_hover ) );
		}

		// Sidebar text hover color.
		$sidebar_text_hover = $appear[ Constants::APPEAR_SIDEBAR_TEXT_HOVER ] ?? '';
		if ( '' !== $sidebar_text_hover ) {
			$css[] = sprintf( '#adminmenu a:hover, #adminmenu li.current a, #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu { color: %s !important; }', esc_attr( $sidebar_text_hover ) );
		}

		// Sidebar font size.
		$sidebar_font_size = $appear[ Constants::APPEAR_SIDEBAR_FONT_SIZE ] ?? '';
		if ( '' !== $sidebar_font_size && is_numeric( $sidebar_font_size ) ) {
			$css[] = sprintf( '#adminmenu a, #adminmenu .wp-menu-name { font-size: %dpx !important; }', (int) $sidebar_font_size );
		}

		// Sidebar width.
		$sidebar_width = $appear[ Constants::APPEAR_SIDEBAR_WIDTH ] ?? '';
		if ( '' !== $sidebar_width && is_numeric( $sidebar_width ) ) {
			$width = (int) $sidebar_width;
			$css[] = sprintf( '#adminmenuback, #adminmenu, #adminmenuwrap { width: %dpx !important; }', $width );
			$css[] = sprintf( '#wpcontent, #wpfooter { margin-left: %dpx !important; }', $width );
			$css[] = sprintf( '.folded #adminmenuback, .folded #adminmenu, .folded #adminmenuwrap { width: %dpx !important; }', $width );
		}

		// Sidebar text alignment.
		$sidebar_align = $appear[ Constants::APPEAR_SIDEBAR_ALIGN ] ?? '';
		if ( '' !== $sidebar_align && in_array( $sidebar_align, [ 'left', 'center', 'right' ], true ) ) {
			$css[] = sprintf( '#adminmenu .wp-menu-name { text-align: %s !important; }', esc_attr( $sidebar_align ) );
		}

		// Admin body background.
		$admin_bg = $appear[ Constants::APPEAR_ADMIN_BG ] ?? '';
		if ( '' !== $admin_bg ) {
			$css[] = sprintf( '#wpwrap { background-color: %s !important; }', esc_attr( $admin_bg ) );
			$css[] = sprintf( '#wpcontent { background-color: %s !important; }', esc_attr( $admin_bg ) );
		}

		// Body background image.
		$bg_image_id = $appear[ Constants::APPEAR_BODY_BG_IMAGE ] ?? 0;
		if ( $bg_image_id ) {
			$bg_url = wp_get_attachment_image_url( (int) $bg_image_id, 'full' );
			if ( $bg_url ) {
				$bg_size = $appear[ Constants::APPEAR_BODY_BG_SIZE ] ?? 'cover';
				$css[] = sprintf(
					'#wpwrap { background-image: url(%s) !important; background-size: %s !important; background-position: center center !important; background-repeat: no-repeat !important; }',
					esc_url( $bg_url ),
					esc_attr( $bg_size )
				);
			}
		}

		// Admin text color.
		$text_color = $appear[ Constants::APPEAR_ADMIN_TEXT_COLOR ] ?? '';
		if ( '' !== $text_color ) {
			$css[] = sprintf( '#wpwrap, #wpwrap p, #wpwrap label, #wpwrap th, #wpwrap td { color: %s !important; }', esc_attr( $text_color ) );
		}

		// Admin link color.
		$link_color = $appear[ Constants::APPEAR_ADMIN_LINK_COLOR ] ?? '';
		if ( '' !== $link_color ) {
			$css[] = sprintf( '#wpwrap a { color: %s !important; }', esc_attr( $link_color ) );
		}

		// Button color.
		$btn_color = $appear[ Constants::APPEAR_ADMIN_BTN_COLOR ] ?? '';
		if ( '' !== $btn_color ) {
			$css[] = sprintf( '.wp-core-ui .button-primary, .wp-core-ui .button-primary { background: %s !important; border-color: %s !important; }', esc_attr( $btn_color ), esc_attr( $btn_color ) );
		}

		// Button text color.
		$btn_text = $appear[ Constants::APPEAR_ADMIN_BTN_TEXT ] ?? '';
		if ( '' !== $btn_text ) {
			$css[] = sprintf( '.wp-core-ui .button-primary, .wp-core-ui .button-primary { color: %s !important; }', esc_attr( $btn_text ) );
		}

		// Border color.
		$border_color = $appear[ Constants::APPEAR_ADMIN_BORDER_COLOR ] ?? '';
		if ( '' !== $border_color ) {
			$css[] = sprintf( '.wp-core-ui .postbox, .wp-core-ui .form-table tr, .wp-core-ui .widefat, .nav-tab-wrapper { border-color: %s !important; }', esc_attr( $border_color ) );
		}

		if ( ! empty( $css ) ) {
			echo '<style id="dac-admin-appearance">' . "\n";
			echo wp_strip_all_tags( implode( "\n", $css ) );
			echo "\n" . '</style>' . "\n";
		}
	}

	/**
	 * Check if a user is excluded from enforcement.
	 */
	private function is_excluded( \WP_User $user ): bool {
		return $this->resolver->is_excluded( $user );
	}
}
