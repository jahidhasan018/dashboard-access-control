<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin\Tabs;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleProfileRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Bar tab — hide on frontend/backend, remove specific nodes per role.
 */
final class AdminBarTab {

	private RoleProfileRepository $repository;

	/** @var array<string, string> Known toolbar node IDs → labels. */
	private const KNOWN_NODES = [
		'wp-admin'          => 'WP Admin (frontend)',
		'wp-logo'           => 'WordPress Logo',
		'site-name'         => 'Site Name',
		'updates'           => 'Updates',
		'comments'          => 'Comments',
		'new-content'       => 'Add New',
		'new-post'          => 'New Post',
		'new-page'          => 'New Page',
		'new-media'         => 'New Media',
		'new-link'          => 'New Link',
		'new-user'          => 'New User',
		'edit'              => 'Edit Site/Post',
		'view'              => 'View Site',
		'customize'         => 'Customize',
		'widgets'           => 'Widgets',
		'menus'             => 'Menus',
		'header'            => 'Header',
		'profile'           => 'Profile',
		'edit-profile'      => 'Edit My Profile',
		'logout'            => 'Log Out',
		'search'            => 'Search',
	];

	public function __construct( RoleProfileRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Tab identifier.
	 */
	public static function id(): string {
		return 'admin-bar';
	}

	/**
	 * Tab label.
	 */
	public static function label(): string {
		return __( 'Admin Bar', 'dashboard-access-control' );
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		echo '<div class="dac-admin-bar">';
		echo '<h2>' . esc_html__( 'Admin Bar Control', 'dashboard-access-control' ) . '</h2>';
		echo '<p class="dac-subtitle">' . esc_html__( 'Configure admin bar visibility per role. Hiding on backend does NOT block wp-admin access — it only hides the toolbar.', 'dashboard-access-control' ) . '</p>';

		$roles    = wp_roles()->roles;
		$selected = isset( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : '';

		// Role selector card wrapped in native GET form.
		echo '<form method="get" action="' . esc_url( admin_url( 'options-general.php' ) ) . '">';
		echo '<input type="hidden" name="page" value="' . esc_attr( Constants::MENU_SLUG ) . '">';
		echo '<input type="hidden" name="tab" value="' . esc_attr( self::id() ) . '">';
		echo '<div class="dac-card dac-role-selector">';
		echo '<div class="dac-card-header">';
		echo '<span class="dac-icon dac-icon-users"></span>';
		echo '<strong>' . esc_html__( 'Select Role', 'dashboard-access-control' ) . '</strong>';
		echo '</div>';
		echo '<div class="dac-card-body">';
		echo '<div class="dac-role-picker">';
		echo '<select id="dac-bar-role" name="role" class="dac-select">';
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
			'<button type="submit" class="button button-primary dac-btn-load" id="dac-load-bar-role">%s</button>',
			esc_html__( 'Load Rules', 'dashboard-access-control' )
		);
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</form>';

		if ( $selected ) {
			$this->render_bar_form( $selected );
		}

		echo '</div>';
	}

	/**
	 * Render the admin bar settings form for a role.
	 *
	 * @param string $role_slug Role slug.
	 */
	private function render_bar_form( string $role_slug ): void {
		$profile = $this->repository->get( $role_slug );
		$bar     = $profile[ Constants::PROFILE_ADMIN_BAR ] ?? [];
		$removed = $bar['removed_nodes'] ?? [];

		$role_name = wp_roles()->roles[ $role_slug ]['name'] ?? $role_slug;

		echo '<form method="post">';
		wp_nonce_field( 'dac_save_admin_bar', '_dac_nonce' );
		echo '<input type="hidden" name="dac_action" value="dac_save_admin_bar">';
		echo '<input type="hidden" name="dac_role" value="' . esc_attr( $role_slug ) . '">';

		echo '<h3>' . esc_html( $role_name ) . ' — ' . esc_html__( 'Admin Bar Settings', 'dashboard-access-control' ) . '</h3>';

		echo '<table class="form-table">';

		// Hide on frontend.
		echo '<tr><th>' . esc_html__( 'Hide on Frontend', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<label><input type="checkbox" name="dac_bar[hide_frontend]" value="1" %s> %s</label>',
			checked( ! empty( $bar['hide_frontend'] ), true, false ),
			esc_html__( 'Hide the admin bar on the frontend (site pages)', 'dashboard-access-control' )
		);
		echo '</td></tr>';

		// Hide on backend.
		echo '<tr><th>' . esc_html__( 'Hide on Backend', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<label><input type="checkbox" name="dac_bar[hide_backend]" value="1" %s> %s</label>',
			checked( ! empty( $bar['hide_backend'] ), true, false ),
			esc_html__( 'Hide the admin bar in wp-admin (does NOT block access)', 'dashboard-access-control' )
		);
		echo '</td></tr>';

		echo '</table>';

		// Node removal.
		echo '<h4>' . esc_html__( 'Remove Specific Toolbar Nodes', 'dashboard-access-control' ) . '</h4>';
		echo '<p>' . esc_html__( 'Select individual toolbar items to remove for this role.', 'dashboard-access-control' ) . '</p>';

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th style="width:40px;">' . esc_html__( 'Show', 'dashboard-access-control' ) . '</th>';
		echo '<th style="width:40px;">' . esc_html__( 'Hide', 'dashboard-access-control' ) . '</th>';
		echo '<th>' . esc_html__( 'Toolbar Node', 'dashboard-access-control' ) . '</th>';
		echo '<th>' . esc_html__( 'ID', 'dashboard-access-control' ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody>';

		foreach ( self::KNOWN_NODES as $node_id => $label ) {
			$is_removed = in_array( $node_id, $removed, true );
			printf(
				'<tr><td><input type="radio" name="dac_nodes[%s]" value="0" %s></td>',
				esc_attr( $node_id ),
				checked( $is_removed, false, false )
			);
			printf(
				'<td><input type="radio" name="dac_nodes[%s]" value="1" %s></td>',
				esc_attr( $node_id ),
				checked( $is_removed, true, false )
			);
			echo '<td>' . esc_html( $label ) . '</td>';
			printf( '<td><code>%s</code></td>', esc_html( $node_id ) );
			echo '</tr>';
		}

		echo '</tbody></table>';

		submit_button( __( 'Save Admin Bar Settings', 'dashboard-access-control' ), 'primary', 'submit', false );
		echo '</form>';
	}

	/**
	 * Handle form submission.
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['dac_action'] ) || 'dac_save_admin_bar' !== $_POST['dac_action'] ) {
			return;
		}

		if ( ! current_user_can( Constants::CAP_MANAGE_SETTINGS ) ) {
			return;
		}

		check_admin_referer( 'dac_save_admin_bar', '_dac_nonce' );

		$role_slug = sanitize_text_field( wp_unslash( $_POST['dac_role'] ?? '' ) );
		if ( '' === $role_slug ) {
			return;
		}

		$roles = wp_roles()->roles;
		if ( ! isset( $roles[ $role_slug ] ) ) {
			return;
		}

		$bar_data = $_POST['dac_bar'] ?? [];
		$nodes    = $_POST['dac_nodes'] ?? [];

		$removed_nodes = [];
		if ( is_array( $nodes ) ) {
			foreach ( $nodes as $node_id => $value ) {
				if ( '1' === $value ) {
					$removed_nodes[] = sanitize_text_field( wp_unslash( $node_id ) );
				}
			}
		}

		$profile = $this->repository->get( $role_slug );
		$profile[ Constants::PROFILE_ADMIN_BAR ] = [
			'hide_frontend'  => ! empty( $bar_data['hide_frontend'] ),
			'hide_backend'   => ! empty( $bar_data['hide_backend'] ),
			'removed_nodes'  => array_unique( $removed_nodes ),
		];
		$this->repository->save( $role_slug, $profile );

		wp_safe_redirect( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() . '&role=' . $role_slug . '&saved=1' ) );
		exit;
	}
}
