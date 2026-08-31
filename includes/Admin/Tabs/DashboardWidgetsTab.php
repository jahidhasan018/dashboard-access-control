<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin\Tabs;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleProfileRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard Widgets tab — show/hide widgets per role.
 */
final class DashboardWidgetsTab {

	private RoleProfileRepository $repository;

	/** @var array<int, array{id: string, name: string, callback: string}> */
	private static array $captured_widgets = [];

	public function __construct( RoleProfileRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Tab identifier.
	 */
	public static function id(): string {
		return 'dashboard-widgets';
	}

	/**
	 * Tab label.
	 */
	public static function label(): string {
		return __( 'Dashboard Widgets', 'dashboard-access-control' );
	}

	/**
	 * Capture registered dashboard widgets at late priority.
	 * This runs after all widgets have been registered by core and other plugins.
	 */
	public function capture_widgets(): void {
		global $wp_meta_boxes;

		$dashboard = $wp_meta_boxes['dashboard'] ?? [];
		$normal    = $dashboard['normal']['default'] ?? [];

		$widgets = [];
		foreach ( $normal as $id => $widget ) {
			$widgets[] = [
				'id'       => $id,
				'name'     => $widget['title'] ?? $id,
				'callback' => is_callable( $widget['callback'] ) ? 'yes' : 'no',
			];
		}

		/**
		 * Filter the captured dashboard widget list.
		 *
		 * @param array<int, array{id: string, name: string, callback: string}> $widgets Widget list.
		 */
		self::$captured_widgets = apply_filters( 'dac_dashboard_widget_registry', $widgets );
	}

	/**
	 * Get captured widgets.
	 *
	 * @return array<int, array{id: string, name: string, callback: string}>
	 */
	public static function get_captured_widgets(): array {
		return self::$captured_widgets;
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		echo '<div class="dac-widgets-control">';
		echo '<h2>' . esc_html__( 'Dashboard Widget Control', 'dashboard-access-control' ) . '</h2>';
		echo '<p>' . esc_html__( 'Select a role, then choose which dashboard widgets to hide for that role.', 'dashboard-access-control' ) . '</p>';

		$roles    = wp_roles()->roles;
		$selected = isset( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : '';

		// Role selector.
		echo '<div class="dac-role-selector">';
		echo '<label for="dac-widget-role"><strong>' . esc_html__( 'Select Role:', 'dashboard-access-control' ) . '</strong></label> ';
		echo '<select id="dac-widget-role" name="role">';
		echo '<option value="">' . esc_html__( '— Choose a Role —', 'dashboard-access-control' ) . '</option>';
		foreach ( $roles as $slug => $role_data ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $slug ),
				selected( $slug, $selected, false ),
				esc_html( $role_data['name'] )
			);
		}
		echo '</select> ';
		printf(
			'<a href="%s" class="button" id="dac-load-widget-role">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() ) ),
			esc_html__( 'Load', 'dashboard-access-control' )
		);
		echo '</div>';

		if ( $selected ) {
			$this->render_widget_list( $selected );
		}

		echo '</div>';
	}

	/**
	 * Render the widget list with checkboxes for a role.
	 *
	 * @param string $role_slug Role slug.
	 */
	private function render_widget_list( string $role_slug ): void {
		$profile = $this->repository->get( $role_slug );
		$widgets = $profile[ Constants::PROFILE_WIDGETS ] ?? [];

		$role_name = wp_roles()->roles[ $role_slug ]['name'] ?? $role_slug;

		echo '<form method="post">';
		wp_nonce_field( 'dac_save_dashboard_widgets', '_dac_nonce' );
		echo '<input type="hidden" name="dac_action" value="dac_save_dashboard_widgets">';
		echo '<input type="hidden" name="dac_role" value="' . esc_attr( $role_slug ) . '">';

		echo '<h3>' . esc_html( $role_name ) . ' — ' . esc_html__( 'Widget Visibility', 'dashboard-access-control' ) . '</h3>';

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th style="width:40px;">' . esc_html__( 'Show', 'dashboard-access-control' ) . '</th>';
		echo '<th style="width:40px;">' . esc_html__( 'Hide', 'dashboard-access-control' ) . '</th>';
		echo '<th>' . esc_html__( 'Widget', 'dashboard-access-control' ) . '</th>';
		echo '<th>' . esc_html__( 'ID', 'dashboard-access-control' ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody>';

		$captured = self::$captured_widgets;
		if ( empty( $captured ) ) {
			echo '<tr><td colspan="4">' . esc_html__( 'No widgets captured. Visit the dashboard first.', 'dashboard-access-control' ) . '</td></tr>';
		} else {
			foreach ( $captured as $widget ) {
				$id     = $widget['id'];
				$name   = $widget['name'];
				$hidden = ! empty( $widgets[ $id ] );
				printf(
					'<tr><td><input type="radio" name="dac_widgets[%s]" value="0" %s></td>',
					esc_attr( $id ),
					checked( $hidden, false, false )
				);
				printf(
					'<td><input type="radio" name="dac_widgets[%s]" value="1" %s></td>',
					esc_attr( $id ),
					checked( $hidden, true, false )
				);
				echo '<td>' . esc_html( $name ) . '</td>';
				printf( '<td><code>%s</code></td>', esc_html( $id ) );
				echo '</tr>';
			}
		}

		echo '</tbody></table>';

		submit_button( __( 'Save Widget Rules', 'dashboard-access-control' ), 'primary', 'submit', false );
		echo '</form>';
	}

	/**
	 * Handle form submission.
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['dac_action'] ) || 'dac_save_dashboard_widgets' !== $_POST['dac_action'] ) {
			return;
		}

		if ( ! current_user_can( Constants::CAP_MANAGE_SETTINGS ) ) {
			return;
		}

		check_admin_referer( 'dac_save_dashboard_widgets', '_dac_nonce' );

		$role_slug = sanitize_text_field( wp_unslash( $_POST['dac_role'] ?? '' ) );
		if ( '' === $role_slug ) {
			return;
		}

		$roles = wp_roles()->roles;
		if ( ! isset( $roles[ $role_slug ] ) ) {
			return;
		}

		$raw_widgets = $_POST['dac_widgets'] ?? [];
		if ( ! is_array( $raw_widgets ) ) {
			$raw_widgets = [];
		}

		$widgets = [];
		foreach ( $raw_widgets as $id => $value ) {
			$id   = sanitize_text_field( wp_unslash( $id ) );
			$hide = ( '1' === $value );
			if ( $hide ) {
				$widgets[ $id ] = true;
			}
		}

		$profile = $this->repository->get( $role_slug );
		$profile[ Constants::PROFILE_WIDGETS ] = $widgets;
		$this->repository->save( $role_slug, $profile );

		wp_safe_redirect( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() . '&role=' . $role_slug . '&saved=1' ) );
		exit;
	}
}
