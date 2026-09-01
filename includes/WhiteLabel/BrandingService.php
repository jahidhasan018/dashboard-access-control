<?php
declare(strict_types=1);

namespace DashboardAccessControl\WhiteLabel;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleResolver;
use DashboardAccessControl\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Branding service — logo, footer, "Howdy", favicon, version number hiding.
 * All filters are per-role based on the current user's resolved profile.
 */
final class BrandingService {

	private Options $options;
	private RoleResolver $resolver;

	public function __construct( Options $options, RoleResolver $resolver ) {
		$this->options  = $options;
		$this->resolver = $resolver;
	}

	/**
	 * Hook all branding filters into WordPress.
	 */
	public function init(): void {
		// Admin footer text.
		add_filter( 'admin_footer_text', [ $this, 'admin_footer_text' ] );
		add_filter( 'update_footer', [ $this, 'admin_footer_version' ], 11 );

		// Admin bar "Howdy" replacement.
		add_filter( 'admin_bar_menu', [ $this, 'replace_howdy' ], 999 );

		// Favicon.
		add_action( 'admin_head', [ $this, 'output_favicon' ] );
		add_action( 'login_head', [ $this, 'output_favicon' ] );

		// Hide WP version number.
		add_filter( 'admin_head', [ $this, 'hide_version_number' ] );
	}

	/**
	 * Replace the admin footer text.
	 *
	 * @param string $text Default footer text.
	 * @return string
	 */
	public function admin_footer_text( string $text ): string {
		$settings = $this->get_settings();
		$footer   = $settings['footer_text'] ?? '';

		if ( '' !== $footer ) {
			return wp_kses_post( $footer );
		}

		return $text;
	}

	/**
	 * Replace the footer version text.
	 *
	 * @param string $text Default version text.
	 * @return string
	 */
	public function admin_footer_version( string $text ): string {
		$settings = $this->get_settings();

		if ( ! empty( $settings['hide_version'] ) ) {
			return '';
		}

		return $text;
	}

	/**
	 * Replace "Howdy" text in the admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public function replace_howdy( \WP_Admin_Bar $wp_admin_bar ): void {
		$settings = $this->get_settings();
		$howdy   = $settings['howdy_text'] ?? '';

		if ( '' === $howdy ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return;
		}

		$node = $wp_admin_bar->get_node( 'my-account' );
		if ( ! $node ) {
			return;
		}

		$display_name = esc_html( $user->display_name );
		$new_title    = esc_html( $howdy ) . ', ' . $display_name;

		$wp_admin_bar->add_menu(
			[
				'id'    => 'my-account',
				'title' => $new_title . '<img class="avatar avatar-26" width="26" height="26" src="' . esc_url( get_avatar_url( $user->ID, [ 'size' => 26 ] ) ) . '" alt="' . $display_name . '" />',
			]
		);
	}

	/**
	 * Output custom favicon in admin and login pages.
	 */
	public function output_favicon(): void {
		$settings = $this->get_settings();
		$favicon_id = $settings['favicon_id'] ?? 0;

		if ( ! $favicon_id ) {
			return;
		}

		$favicon_url = wp_get_attachment_image_url( (int) $favicon_id, 'full' );
		if ( ! $favicon_url ) {
			return;
		}

		printf(
			'<link rel="icon" href="%s" />' . "\n",
			esc_url( $favicon_url )
		);
	}

	/**
	 * Hide WP version number via CSS.
	 */
	public function hide_version_number(): void {
		$settings = $this->get_settings();

		if ( empty( $settings['hide_version'] ) ) {
			return;
		}

		echo '<style id="dac-hide-version">';
		echo '#adminmenu .wp-submenu-wrap, .update-nag, .plugin-update-tr, .auto-update-available { display: none !important; }';
		echo '</style>' . "\n";
	}

	/**
	 * Get the white label settings for the current user.
	 *
	 * @return array<string, mixed>
	 */
	private function get_settings(): array {
		$user     = wp_get_current_user();
		$settings = $this->options->get( Constants::OPT_WHITE_LABEL, [] );

		if ( ! $user || ! $user->exists() ) {
			return $settings;
		}

		// Check for per-role overrides.
		$profile = $this->resolver->resolve( $user );
		$role_wl = $profile[ Constants::PROFILE_WHITE_LABEL ] ?? [];

		if ( ! empty( $role_wl ) ) {
			$settings = wp_parse_args( $role_wl, $settings );
		}

		/**
		 * Filter white label settings per context.
		 *
		 * @param array<string, mixed> $settings Branding settings.
		 */
		return apply_filters( 'dac_white_label_settings', $settings );
	}
}
