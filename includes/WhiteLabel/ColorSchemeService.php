<?php
declare(strict_types=1);

namespace DashboardAccessControl\WhiteLabel;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Color scheme service — register and apply custom admin color schemes.
 */
final class ColorSchemeService {

	private Options $options;

	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Hook into WordPress to register color schemes.
	 */
	public function init(): void {
		add_action( 'admin_init', [ $this, 'register_color_schemes' ] );
		add_action( 'admin_head', [ $this, 'apply_color_scheme' ] );
	}

	/**
	 * Register custom admin color schemes based on saved settings.
	 */
	public function register_color_schemes(): void {
		$settings = $this->options->get( Constants::OPT_WHITE_LABEL, [] );
		$schemes  = $settings['color_schemes'] ?? [];

		foreach ( $schemes as $scheme ) {
			$slug  = $scheme['slug'] ?? '';
			$name  = $scheme['name'] ?? '';
			$color = $scheme['color'] ?? '#2271b1';

			if ( '' === $slug || '' === $name ) {
				continue;
			}

			wp_admin_css_color(
				'dac_' . $slug,
				$name,
				DAC_PLUGIN_URL . 'assets/css/color-schemes/' . $slug . '.css',
				$color
			);
		}
	}

	/**
	 * Apply the selected color scheme for the current user.
	 */
	public function apply_color_scheme(): void {
		$settings = $this->options->get( Constants::OPT_WHITE_LABEL, [] );
		$scheme   = $settings['active_color_scheme'] ?? '';

		if ( '' === $scheme ) {
			return;
		}

		$user_id    = get_current_user_id();
		$chosen     = get_user_meta( $user_id, 'admin_color', true );
		$scheme_key = 'dac_' . $scheme;

		// Only override if the user hasn't explicitly chosen a different scheme.
		if ( $chosen !== $scheme_key ) {
			add_filter( 'get_user_option_admin_color', function () use ( $scheme ) {
				return 'dac_' . $scheme;
			} );
		}
	}
}
