<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin;

use DashboardAccessControl\Core\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues admin CSS/JS only on plugin settings screens.
 */
final class Assets {

	/**
	 * Hook into WordPress admin.
	 */
	public function init(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue' ] );
	}

	/**
	 * Conditionally enqueue assets on the plugin's own settings page.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function maybe_enqueue( string $hook_suffix ): void {
		if ( ! str_contains( $hook_suffix, 'settings_page_' . Constants::MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'dac-admin',
			DAC_PLUGIN_URL . 'assets/css/admin.css',
			[],
			DAC_VERSION
		);

		wp_enqueue_script(
			'dac-admin',
			DAC_PLUGIN_URL . 'assets/js/admin.js',
			[],
			DAC_VERSION,
			true
		);
	}
}
