<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin\Tabs;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleProfileRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content Restrictions tab — meta boxes, screen options, help tab, notices, file editor.
 */
final class ContentRestrictionsTab {

	private RoleProfileRepository $repository;

	public function __construct( RoleProfileRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Tab identifier.
	 */
	public static function id(): string {
		return 'content-restrictions';
	}

	/**
	 * Tab label.
	 */
	public static function label(): string {
		return __( 'Content Restrictions', 'dashboard-access-control' );
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		echo '<div class="dac-content-restrictions">';
		echo '<h2>' . esc_html__( 'Content & UI Restrictions', 'dashboard-access-control' ) . '</h2>';
		echo '<p class="dac-subtitle">' . esc_html__( 'Hide or disable specific admin UI elements per role.', 'dashboard-access-control' ) . '</p>';

		$roles    = wp_roles()->roles;
		$selected = isset( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : '';

		// Role selector.
		echo '<div class="dac-card dac-role-selector">';
		echo '<div class="dac-card-header">';
		echo '<span class="dac-icon dac-icon-users"></span>';
		echo '<strong>' . esc_html__( 'Select Role', 'dashboard-access-control' ) . '</strong>';
		echo '</div>';
		echo '<div class="dac-card-body">';
		echo '<div class="dac-role-picker">';
		echo '<select id="dac-restrict-role" name="role" class="dac-select">';
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
			'<button type="button" class="button button-primary dac-btn-load" id="dac-load-restrict-role">%s</button>',
			esc_html__( 'Load Rules', 'dashboard-access-control' )
		);
		echo '</div>';
		echo '</div>';
		echo '</div>';

		if ( $selected ) {
			$this->render_restrictions_form( $selected );
		}

		echo '</div>';
	}

	/**
	 * Render the restrictions form for a role.
	 *
	 * @param string $role_slug Role slug.
	 */
	private function render_restrictions_form( string $role_slug ): void {
		$profile = $this->repository->get( $role_slug );
		$restrictions = $profile[ Constants::PROFILE_RESTRICTIONS ] ?? [];
		$role_name = wp_roles()->roles[ $role_slug ]['name'] ?? $role_slug;

		echo '<form method="post">';
		wp_nonce_field( 'dac_save_content_restrictions', '_dac_nonce' );
		echo '<input type="hidden" name="dac_action" value="dac_save_content_restrictions">';
		echo '<input type="hidden" name="dac_role" value="' . esc_attr( $role_slug ) . '">';

		echo '<h3>' . esc_html( $role_name ) . ' — ' . esc_html__( 'Restrictions', 'dashboard-access-control' ) . '</h3>';

		// ── Screen Elements ────────────────────────────────────────────────
		echo '<div class="dac-card">';
		echo '<div class="dac-card-header">';
		echo '<span class="dac-icon dac-icon-screen"></span>';
		echo '<strong>' . esc_html__( 'Screen Elements', 'dashboard-access-control' ) . '</strong>';
		echo '</div>';
		echo '<div class="dac-card-body">';
		echo '<table class="form-table">';

		$this->render_toggle_row(
			__( 'Hide "At a Glance" Widget', 'dashboard-access-control' ),
			'dac_restrictions[hide_at_a_glance]',
			! empty( $restrictions['hide_at_a_glance'] ),
			__( 'Removes the "At a Glance" summary widget from the dashboard.', 'dashboard-access-control' )
		);

		$this->render_toggle_row(
			__( 'Hide Admin Notices', 'dashboard-access-control' ),
			'dac_restrictions[suppress_notices]',
			! empty( $restrictions['suppress_notices'] ),
			__( 'Suppresses admin notices. Critical security notices are still shown.', 'dashboard-access-control' )
		);

		echo '</table>';
		echo '</div>';
		echo '</div>';

		// ── Tabs & Help ────────────────────────────────────────────────────
		echo '<div class="dac-card">';
		echo '<div class="dac-card-header">';
		echo '<span class="dac-icon dac-icon-tabs"></span>';
		echo '<strong>' . esc_html__( 'Tabs & Help', 'dashboard-access-control' ) . '</strong>';
		echo '</div>';
		echo '<div class="dac-card-body">';
		echo '<table class="form-table">';

		$this->render_toggle_row(
			__( 'Disable Screen Options Tab', 'dashboard-access-control' ),
			'dac_restrictions[disable_screen_options]',
			! empty( $restrictions['disable_screen_options'] ),
			__( 'Hides the Screen Options tab on all admin screens.', 'dashboard-access-control' )
		);

		$this->render_toggle_row(
			__( 'Disable Help Tab', 'dashboard-access-control' ),
			'dac_restrictions[disable_help_tab]',
			! empty( $restrictions['disable_help_tab'] ),
			__( 'Hides the Help tab on all admin screens.', 'dashboard-access-control' )
		);

		echo '</table>';
		echo '</div>';
		echo '</div>';

		// ── Security Restrictions ──────────────────────────────────────────
		echo '<div class="dac-card">';
		echo '<div class="dac-card-header">';
		echo '<span class="dac-icon dac-icon-lock"></span>';
		echo '<strong>' . esc_html__( 'Security', 'dashboard-access-control' ) . '</strong>';
		echo '</div>';
		echo '<div class="dac-card-body">';
		echo '<table class="form-table">';

		$this->render_toggle_row(
			__( 'Disable File Editor', 'dashboard-access-control' ),
			'dac_restrictions[disable_file_editor]',
			! empty( $restrictions['disable_file_editor'] ),
			__( 'Disables the Theme and Plugin file editors. Requires file editing capabilities.', 'dashboard-access-control' )
		);

		$this->render_toggle_row(
			__( 'Hide Meta Boxes', 'dashboard-access-control' ),
			'dac_restrictions[hide_meta_boxes]',
			! empty( $restrictions['hide_meta_boxes'] ),
			__( 'Hides non-essential meta boxes on post edit screens.', 'dashboard-access-control' )
		);

		echo '</table>';
		echo '</div>';
		echo '</div>';

		submit_button( __( 'Save Content Restrictions', 'dashboard-access-control' ), 'primary', 'submit', false );
		echo '</form>';
	}

	/**
	 * Render a single toggle row.
	 */
	private function render_toggle_row( string $label, string $name, bool $checked, string $description ): void {
		echo '<tr>';
		echo '<th scope="row">' . esc_html( $label ) . '</th>';
		echo '<td>';
		printf(
			'<label class="dac-toggle"><input type="checkbox" name="%s" value="1" class="dac-toggle-input" %s /><span class="dac-toggle-slider"></span></label>',
			esc_attr( $name ),
			checked( $checked, true, false )
		);
		if ( $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td>';
		echo '</tr>';
	}

	/**
	 * Handle form submission.
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['dac_action'] ) || 'dac_save_content_restrictions' !== $_POST['dac_action'] ) {
			return;
		}

		if ( ! current_user_can( Constants::CAP_MANAGE_SETTINGS ) ) {
			return;
		}

		check_admin_referer( 'dac_save_content_restrictions', '_dac_nonce' );

		$role_slug = sanitize_text_field( wp_unslash( $_POST['dac_role'] ?? '' ) );
		if ( '' === $role_slug ) {
			return;
		}

		$roles = wp_roles()->roles;
		if ( ! isset( $roles[ $role_slug ] ) ) {
			return;
		}

		$raw = $_POST['dac_restrictions'] ?? [];
		if ( ! is_array( $raw ) ) {
			$raw = [];
		}

		$restrictions = [
			'hide_meta_boxes'        => ! empty( $raw['hide_meta_boxes'] ),
			'disable_screen_options' => ! empty( $raw['disable_screen_options'] ),
			'disable_help_tab'       => ! empty( $raw['disable_help_tab'] ),
			'suppress_notices'       => ! empty( $raw['suppress_notices'] ),
			'hide_at_a_glance'       => ! empty( $raw['hide_at_a_glance'] ),
			'disable_file_editor'    => ! empty( $raw['disable_file_editor'] ),
		];

		$profile = $this->repository->get( $role_slug );
		$profile[ Constants::PROFILE_RESTRICTIONS ] = $restrictions;
		$this->repository->save( $role_slug, $profile );

		wp_safe_redirect( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() . '&role=' . $role_slug . '&saved=1' ) );
		exit;
	}
}
