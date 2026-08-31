<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the admin menu page and renders the active tab.
 */
final class SettingsPage {

	private Options $options;

	/** @var array<string, array{label: string, callback: callable}> */
	private array $tabs = [];

	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Hook into WordPress admin.
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	/**
	 * Register the top-level settings page under Settings.
	 */
	public function register_menu(): void {
		$this->register_tabs();

		add_options_page(
			__( 'Dashboard Access Control', 'dashboard-access-control' ),
			__( 'Access Control', 'dashboard-access-control' ),
			Constants::CAP_MANAGE_SETTINGS,
			Constants::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Register the default tabs.
	 */
	private function register_tabs(): void {
		$this->tabs = [];

		// Core tabs registered here.
		$this->tabs[ RoleManagerTab::id() ] = [
			'label'    => RoleManagerTab::label(),
			'callback' => function () {
				$tab = new \DashboardAccessControl\Admin\Tabs\RoleManagerTab(
					new \DashboardAccessControl\RoleAccess\RoleProfileRepository( $this->options )
				);
				$tab->render();
			},
		];

		$this->tabs[ MenuControlTab::id() ] = [
			'label'    => MenuControlTab::label(),
			'callback' => function () {
				$tab = new \DashboardAccessControl\Admin\Tabs\MenuControlTab(
					new \DashboardAccessControl\RoleAccess\RoleProfileRepository( $this->options )
				);
				$tab->render();
			},
		];

		$this->tabs['general'] = [
			'label'    => __( 'General', 'dashboard-access-control' ),
			'callback' => [ $this, 'render_general_tab' ],
		];

		/**
		 * Filter the settings tabs.
		 *
		 * @param array<string, array{label: string, callback: callable}> $tabs Registered tabs.
		 */
		$this->tabs = apply_filters( 'dac_settings_tabs', $this->tabs );
	}

	/**
	 * Render the settings page wrapper and active tab.
	 */
	public function render_page(): void {
		if ( ! current_user_can( Constants::CAP_MANAGE_SETTINGS ) ) {
			return;
		}

		// Render admin notices before the page content.
		Notices::render_all();

		$active_tab = $this->get_active_tab();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		$this->render_tab_navigation( $active_tab );
		echo '<div class="dac-tab-content">';
		$this->render_active_tab( $active_tab );
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Get the currently active tab from query string.
	 */
	private function get_active_tab(): string {
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		return array_key_exists( $tab, $this->tabs ) ? $tab : array_key_first( $this->tabs );
	}

	/**
	 * Render tab navigation links.
	 */
	private function render_tab_navigation( string $active_tab ): void {
		echo '<nav class="nav-tab-wrapper">';
		foreach ( $this->tabs as $id => $tab ) {
			$url    = admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . $id );
			$active = ( $id === $active_tab ) ? ' nav-tab-active' : '';
			printf(
				'<a href="%s" class="nav-tab%s">%s</a>',
				esc_url( $url ),
				esc_attr( $active ),
				esc_html( $tab['label'] )
			);
		}
		echo '</nav>';
	}

	/**
	 * Call the active tab's render callback.
	 */
	private function render_active_tab( string $active_tab ): void {
		if ( isset( $this->tabs[ $active_tab ]['callback'] ) ) {
			call_user_func( $this->tabs[ $active_tab ]['callback'] );
		}
	}

	/**
	 * Default "General" tab placeholder.
	 */
	public function render_general_tab(): void {
		echo '<p>';
		echo esc_html__( 'Welcome to Dashboard Access Control. Use the tabs above to configure role-based access rules.', 'dashboard-access-control' );
		echo '</p>';
	}
}
