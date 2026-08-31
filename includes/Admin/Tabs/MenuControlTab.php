<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin\Tabs;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleProfileRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Menu Control tab — list admin menus, configure show/hide per role.
 */
final class MenuControlTab {

	private RoleProfileRepository $repository;

	/** @var array<int, array{0: string, 1: string, 2: string, 3: string, 4: string}> */
	private static array $captured_menu = [];

	/** @var array<string, array<int, array{0: string, 1: string, 2: string}>> */
	private static array $captured_submenu = [];

	public function __construct( RoleProfileRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Tab identifier.
	 */
	public static function id(): string {
		return 'menu-control';
	}

	/**
	 * Tab label.
	 */
	public static function label(): string {
		return __( 'Menu Control', 'dashboard-access-control' );
	}

	/**
	 * Capture the menu globals late on admin_menu.
	 * Hook at priority 9999 so we see the final state after all plugins add their menus.
	 */
	public function capture_menu(): void {
		global $menu, $submenu;
		self::$captured_menu    = $menu ?? [];
		self::$captured_submenu = $submenu ?? [];
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		echo '<div class="dac-menu-control">';
		echo '<h2>' . esc_html__( 'Admin Menu Control', 'dashboard-access-control' ) . '</h2>';
		echo '<p>' . esc_html__( 'Select a role, then choose which menu items to hide. Hidden items are also blocked at the capability, route, and AJAX/REST layers.', 'dashboard-access-control' ) . '</p>';

		$roles    = wp_roles()->roles;
		$selected = isset( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : '';

		// Role selector.
		echo '<div class="dac-role-selector">';
		echo '<label for="dac-menu-role"><strong>' . esc_html__( 'Select Role:', 'dashboard-access-control' ) . '</strong></label> ';
		echo '<select id="dac-menu-role" name="role">';
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
			'<a href="%s" class="button" id="dac-load-menu-role">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() ) ),
			esc_html__( 'Load', 'dashboard-access-control' )
		);
		echo '</div>';

		if ( $selected ) {
			$this->render_menu_list( $selected );
		}

		echo '</div>';
	}

	/**
	 * Render the menu item list with checkboxes for a role.
	 *
	 * @param string $role_slug Role slug.
	 */
	private function render_menu_list( string $role_slug ): void {
		$profile = $this->repository->get( $role_slug );
		$menus   = $profile[ Constants::PROFILE_MENUS ] ?? [];
		$hidden  = [];
		foreach ( $menus as $menu ) {
			if ( ! empty( $menu['hidden'] ) ) {
				$hidden[] = $menu['slug'] ?? '';
			}
		}

		$role_name = wp_roles()->roles[ $role_slug ]['name'] ?? $role_slug;

		echo '<form method="post">';
		wp_nonce_field( 'dac_save_menu_control', '_dac_nonce' );
		echo '<input type="hidden" name="dac_action" value="dac_save_menu_control">';
		echo '<input type="hidden" name="dac_role" value="' . esc_attr( $role_slug ) . '">';

		echo '<h3>' . esc_html( $role_name ) . ' — ' . esc_html__( 'Menu Visibility', 'dashboard-access-control' ) . '</h3>';

		echo '<table class="widefat striped dac-menu-table">';
		echo '<thead><tr>';
		echo '<th style="width:40px;">' . esc_html__( 'Show', 'dashboard-access-control' ) . '</th>';
		echo '<th style="width:40px;">' . esc_html__( 'Hide', 'dashboard-access-control' ) . '</th>';
		echo '<th>' . esc_html__( 'Menu Item', 'dashboard-access-control' ) . '</th>';
		echo '<th>' . esc_html__( 'Slug', 'dashboard-access-control' ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody>';

		if ( empty( self::$captured_menu ) ) {
			echo '<tr><td colspan="4">' . esc_html__( 'No menu items captured. Visit any admin page first.', 'dashboard-access-control' ) . '</td></tr>';
		} else {
			foreach ( self::$captured_menu as $menu_item ) {
				$slug   = $menu_item[2] ?? '';
				$label  = $menu_item[3] ?? '';
				$hidden_status = in_array( $slug, $hidden, true );
				$this->render_menu_row( $slug, $label, $hidden_status, $role_slug, 0 );
			}
		}

		echo '</tbody></table>';

		submit_button( __( 'Save Menu Rules', 'dashboard-access-control' ), 'primary', 'submit', false );
		echo '</form>';
	}

	/**
	 * Render a single menu row.
	 */
	private function render_menu_row( string $slug, string $label, bool $is_hidden, string $role_slug, int $depth ): void {
		$indent = str_repeat( '&mdash; ', $depth );
		printf(
			'<tr><td><input type="radio" name="dac_menus[%s][hidden]" value="0" %s></td>',
			esc_attr( $slug ),
			checked( $is_hidden, false, false )
		);
		printf(
			'<td><input type="radio" name="dac_menus[%s][hidden]" value="1" %s></td>',
			esc_attr( $slug ),
			checked( $is_hidden, true, false )
		);
		printf(
			'<td>%s%s</td>',
			$indent,
			esc_html( $label )
		);
		printf(
			'<td><code>%s</code></td>',
			esc_html( $slug )
		);
		echo '</tr>';

		// Render submenus.
		if ( isset( self::$captured_submenu[ $slug ] ) ) {
			foreach ( self::$captured_submenu[ $slug ] as $sub ) {
				$sub_slug  = $sub[2] ?? '';
				$sub_label = $sub[0] ?? '';
				$sub_hidden = in_array( $sub_slug, $this->get_hidden_slugs( $role_slug ), true );
				$this->render_menu_row( $sub_slug, $sub_label, $sub_hidden, $role_slug, $depth + 1 );
			}
		}
	}

	/**
	 * Get all hidden slugs for a role.
	 *
	 * @return string[]
	 */
	private function get_hidden_slugs( string $role_slug ): array {
		$profile = $this->repository->get( $role_slug );
		$menus   = $profile[ Constants::PROFILE_MENUS ] ?? [];
		$hidden  = [];
		foreach ( $menus as $menu ) {
			if ( ! empty( $menu['hidden'] ) ) {
				$hidden[] = $menu['slug'] ?? '';
			}
		}
		return $hidden;
	}

	/**
	 * Handle form submission.
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['dac_action'] ) || 'dac_save_menu_control' !== $_POST['dac_action'] ) {
			return;
		}

		if ( ! current_user_can( Constants::CAP_MANAGE_SETTINGS ) ) {
			return;
		}

		check_admin_referer( 'dac_save_menu_control', '_dac_nonce' );

		$role_slug = sanitize_text_field( wp_unslash( $_POST['dac_role'] ?? '' ) );
		if ( '' === $role_slug ) {
			return;
		}

		$roles = wp_roles()->roles;
		if ( ! isset( $roles[ $role_slug ] ) ) {
			return;
		}

		$raw_menus = $_POST['dac_menus'] ?? [];
		if ( ! is_array( $raw_menus ) ) {
			$raw_menus = [];
		}

		$menus = [];
		foreach ( $raw_menus as $slug => $data ) {
			$slug  = sanitize_text_field( wp_unslash( $slug ) );
			$hidden = ( '1' === ( $data['hidden'] ?? '0' ) );
			$menus[] = [
				'slug'   => $slug,
				'hidden' => $hidden,
				'label'  => '',
				'icon'   => '',
			];
		}

		$profile = $this->repository->get( $role_slug );
		$profile[ Constants::PROFILE_MENUS ] = $menus;

		// ExclusionGuard check.
		$guard  = new \DashboardAccessControl\RoleAccess\ExclusionGuard( $this->repository, new \DashboardAccessControl\Support\Options() );
		$result = $guard->check( $role_slug, $profile );
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

		wp_safe_redirect( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() . '&role=' . $role_slug . '&saved=1' ) );
		exit;
	}
}
