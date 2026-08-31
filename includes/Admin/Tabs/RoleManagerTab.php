<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin\Tabs;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleProfileRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Role Manager tab — multi-role selector, clone, reset.
 */
final class RoleManagerTab {

	private RoleProfileRepository $repository;

	public function __construct( RoleProfileRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Tab identifier.
	 */
	public static function id(): string {
		return 'role-manager';
	}

	/**
	 * Tab label.
	 */
	public static function label(): string {
		return __( 'Role Manager', 'dashboard-access-control' );
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		$roles       = wp_roles()->roles;
		$profiles    = $this->repository->get_all();
		$selected    = isset( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : '';
		$profile     = $selected ? $this->repository->get( $selected ) : [];

		echo '<div class="dac-role-manager">';
		echo '<h2>' . esc_html__( 'Role Profiles', 'dashboard-access-control' ) . '</h2>';
		echo '<p>' . esc_html__( 'Select a role to configure its access rules. Each role can have its own set of restrictions.', 'dashboard-access-control' ) . '</p>';

		// Role selector.
		echo '<div class="dac-role-selector">';
		echo '<label for="dac-role-select"><strong>' . esc_html__( 'Select Role:', 'dashboard-access-control' ) . '</strong></label> ';
		echo '<select id="dac-role-select" name="role">';
		echo '<option value="">' . esc_html__( '— Choose a Role —', 'dashboard-access-control' ) . '</option>';
		foreach ( $roles as $slug => $role_data ) {
			$has_profile = isset( $profiles[ $slug ] );
			$label       = $role_data['name'] . ( $has_profile ? ' ✓' : '' );
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $slug ),
				selected( $slug, $selected, false ),
				esc_html( $label )
			);
		}
		echo '</select> ';
		printf(
			'<a href="%s" class="button" id="dac-load-role">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() ) ),
			esc_html__( 'Load', 'dashboard-access-control' )
		);
		echo '</div>';

		// Profile form (shown when a role is selected).
		if ( $selected && ! empty( $profile ) ) {
			$this->render_profile_form( $selected, $profile );
		}

		echo '</div>';
	}

	/**
	 * Render the profile editing form for a role.
	 *
	 * @param string               $role_slug Role slug.
	 * @param array<string, mixed> $profile   Role profile data.
	 */
	private function render_profile_form( string $role_slug, array $profile ): void {
		$action = 'dac_save_role_profile';
		echo '<form method="post">';
		wp_nonce_field( $action, '_dac_nonce' );
		echo '<input type="hidden" name="dac_action" value="' . esc_attr( $action ) . '">';
		echo '<input type="hidden" name="dac_role" value="' . esc_attr( $role_slug ) . '">';

		$role_name = wp_roles()->roles[ $role_slug ]['name'] ?? $role_slug;
		echo '<h3>' . esc_html( $role_name ) . ' — ' . esc_html__( 'Profile', 'dashboard-access-control' ) . '</h3>';

		// Menu restrictions summary.
		$menus = $profile[ Constants::PROFILE_MENUS ] ?? [];
		$hidden_count = 0;
		foreach ( $menus as $menu ) {
			if ( ! empty( $menu['hidden'] ) ) {
				++$hidden_count;
			}
		}

		echo '<table class="form-table">';
		echo '<tr><th>' . esc_html__( 'Hidden Menus', 'dashboard-access-control' ) . '</th>';
		printf(
			'<td>%d / %d — <a href="%s">%s</a></td>',
			$hidden_count,
			count( $menus ),
			esc_url( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=menu-control&role=' . $role_slug ) ),
			esc_html__( 'Configure', 'dashboard-access-control' )
		);
		echo '</tr>';

		// Admin bar summary.
		$bar = $profile[ Constants::PROFILE_ADMIN_BAR ] ?? [];
		echo '<tr><th>' . esc_html__( 'Admin Bar', 'dashboard-access-control' ) . '</th>';
		$bar_status = [];
		if ( ! empty( $bar['hide_frontend'] ) ) {
			$bar_status[] = __( 'Hidden on frontend', 'dashboard-access-control' );
		}
		if ( ! empty( $bar['hide_backend'] ) ) {
			$bar_status[] = __( 'Hidden on backend', 'dashboard-access-control' );
		}
		if ( empty( $bar_status ) ) {
			$bar_status[] = __( 'Visible', 'dashboard-access-control' );
		}
		echo '<td>' . esc_html( implode( ', ', $bar_status ) ) . '</td>';
		echo '</tr>';

		// Restrictions summary.
		$restrictions = $profile[ Constants::PROFILE_RESTRICTIONS ] ?? [];
		$active_restrictions = array_filter( $restrictions );
		echo '<tr><th>' . esc_html__( 'Active Restrictions', 'dashboard-access-control' ) . '</th>';
		echo '<td>' . esc_html( count( $active_restrictions ) ) . ' / ' . esc_html( count( $restrictions ) ) . '</td>';
		echo '</tr>';

		echo '</table>';

		// Action buttons.
		echo '<p>';
		submit_button( __( 'Save Profile', 'dashboard-access-control' ), 'primary', 'submit', false );

		printf(
			' <a href="%s" class="button dac-reset-role" data-role="%s">%s</a>',
			esc_url( wp_nonce_url(
				admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() . '&dac_action=dac_reset_role&role=' . $role_slug ),
				'dac_reset_role_' . $role_slug
			) ),
			esc_attr( $role_slug ),
			esc_html__( 'Reset to Default', 'dashboard-access-control' )
		);
		echo '</p>';

		echo '</form>';
	}

	/**
	 * Handle form submissions.
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['dac_action'] ) || 'dac_save_role_profile' !== $_POST['dac_action'] ) {
			return;
		}

		if ( ! current_user_can( Constants::CAP_MANAGE_SETTINGS ) ) {
			return;
		}

		check_admin_referer( 'dac_save_role_profile', '_dac_nonce' );

		$role_slug = sanitize_text_field( wp_unslash( $_POST['dac_role'] ?? '' ) );
		if ( '' === $role_slug ) {
			return;
		}

		$roles = wp_roles()->roles;
		if ( ! isset( $roles[ $role_slug ] ) ) {
			return;
		}

		$profile = $this->repository->get( $role_slug );

		// ExclusionGuard check.
		$guard   = new \DashboardAccessControl\RoleAccess\ExclusionGuard( $this->repository, new \DashboardAccessControl\Support\Options() );
		$result  = $guard->check( $role_slug, $profile );
		if ( is_wp_error( $result ) ) {
			add_action( 'admin_notices', function () use ( $result ) {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html( $result->get_error_message() )
				);
			} );
			return;
		}

		$this->repository->save( $role_slug, $profile );

		add_action( 'admin_notices', function () use ( $role_name ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( __( 'Role profile saved.', 'dashboard-access-control' ) )
			);
		} );
	}

	/**
	 * Handle role reset.
	 */
	public function handle_reset(): void {
		if ( ! isset( $_GET['dac_action'] ) || 'dac_reset_role' !== $_GET['dac_action'] ) {
			return;
		}

		$role_slug = sanitize_text_field( wp_unslash( $_GET['role'] ?? '' ) );
		if ( '' === $role_slug ) {
			return;
		}

		check_admin_referer( 'dac_reset_role_' . $role_slug );

		$this->repository->delete( $role_slug );

		wp_safe_redirect( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() . '&reset=1' ) );
		exit;
	}
}
