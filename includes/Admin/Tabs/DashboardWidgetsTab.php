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

	/** @var array<string, string> Core dashboard widget IDs → default names. */
	public const CORE_WIDGETS = [
		'dashboard_right_now'      => 'At a Glance',
		'dashboard_activity'       => 'Activity',
		'dashboard_quick_press'    => 'Quick Draft',
		'dashboard_primary'        => 'WordPress Events and News',
		'dashboard_site_health'    => 'Site Health Status',
	];

	/** @var array<string, array{id: string, name: string, callback: string}> */
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
	 * Capture registered dashboard widgets at late priority on wp_dashboard_setup.
	 */
	public function capture_widgets(): void {
		global $wp_meta_boxes;

		$dashboard = $wp_meta_boxes['dashboard'] ?? [];
		$widgets   = [];

		foreach ( [ 'normal', 'side', 'column3', 'column4' ] as $context ) {
			if ( empty( $dashboard[ $context ] ) || ! is_array( $dashboard[ $context ] ) ) {
				continue;
			}
			foreach ( [ 'core', 'high', 'default', 'low' ] as $priority ) {
				if ( empty( $dashboard[ $context ][ $priority ] ) || ! is_array( $dashboard[ $context ][ $priority ] ) ) {
					continue;
				}
				foreach ( $dashboard[ $context ][ $priority ] as $id => $widget ) {
					$id_str = (string) $id;
					$widgets[ $id_str ] = [
						'id'       => $id_str,
						'name'     => ! empty( $widget['title'] ) ? wp_strip_all_tags( (string) $widget['title'] ) : $id_str,
						'callback' => is_callable( $widget['callback'] ?? null ) ? 'yes' : 'no',
					];
				}
			}
		}

		if ( ! empty( $widgets ) ) {
			self::$captured_widgets = $widgets;
			$stored = get_option( 'dac_available_widgets', [] );
			if ( $stored !== $widgets ) {
				update_option( 'dac_available_widgets', $widgets );
			}
		}
	}

	/**
	 * Get captured widgets, discovering widgets from Core and all active plugins.
	 *
	 * @return array<int, array{id: string, name: string, callback: string}>
	 */
	public static function get_captured_widgets(): array {
		global $wp_meta_boxes;

		// If dashboard widgets haven't been setup yet, load dashboard file and trigger setup
		if ( ! did_action( 'wp_dashboard_setup' ) ) {
			if ( file_exists( ABSPATH . 'wp-admin/includes/dashboard.php' ) ) {
				require_once ABSPATH . 'wp-admin/includes/dashboard.php';
			}
			// Trigger setup so Core and all installed plugins register their dashboard widgets
			if ( function_exists( 'wp_dashboard_setup' ) ) {
				wp_dashboard_setup();
			} else {
				do_action( 'wp_dashboard_setup' );
			}
		}

		$widgets = [];

		// Core default widgets as baseline
		foreach ( self::CORE_WIDGETS as $id => $name ) {
			$widgets[ $id ] = [
				'id'       => $id,
				'name'     => $name,
				'callback' => 'yes',
			];
		}

		// Inspect $wp_meta_boxes['dashboard'] across all contexts and priorities
		$dashboard = $wp_meta_boxes['dashboard'] ?? [];
		foreach ( [ 'normal', 'side', 'column3', 'column4' ] as $context ) {
			if ( empty( $dashboard[ $context ] ) || ! is_array( $dashboard[ $context ] ) ) {
				continue;
			}
			foreach ( [ 'core', 'high', 'default', 'low' ] as $priority ) {
				if ( empty( $dashboard[ $context ][ $priority ] ) || ! is_array( $dashboard[ $context ][ $priority ] ) ) {
					continue;
				}
				foreach ( $dashboard[ $context ][ $priority ] as $id => $box ) {
					$id_str = (string) $id;
					$title  = ! empty( $box['title'] ) ? wp_strip_all_tags( (string) $box['title'] ) : $id_str;
					$widgets[ $id_str ] = [
						'id'       => $id_str,
						'name'     => $title,
						'callback' => is_callable( $box['callback'] ?? null ) ? 'yes' : 'no',
					];
				}
			}
		}

		// Merge stored widgets from database (captured on dashboard)
		$stored = get_option( 'dac_available_widgets', [] );
		if ( is_array( $stored ) ) {
			foreach ( $stored as $id => $w ) {
				if ( is_array( $w ) && isset( $w['id'] ) ) {
					$widgets[ (string) $w['id'] ] = $w;
				}
			}
		}

		// Merge runtime captured widgets if any
		foreach ( self::$captured_widgets as $id => $w ) {
			$widgets[ (string) $id ] = $w;
		}

		// Save updated list so we remember all detected plugin widgets
		if ( ! empty( $widgets ) && $widgets !== $stored ) {
			update_option( 'dac_available_widgets', $widgets );
		}

		/**
		 * Filter the captured dashboard widget list.
		 *
		 * @param array<int, array{id: string, name: string, callback: string}> $widgets Widget list.
		 */
		return apply_filters( 'dac_dashboard_widget_registry', array_values( $widgets ) );
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		echo '<div class="dac-widgets-control">';
		echo '<h2>' . esc_html__( 'Dashboard Widget Control', 'dashboard-access-control' ) . '</h2>';
		echo '<p class="dac-subtitle">' . esc_html__( 'Select a role, then toggle visibility for each dashboard widget. Hide widgets from WordPress Core or third-party plugins.', 'dashboard-access-control' ) . '</p>';

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
		echo '<select id="dac-widget-role" name="role" class="dac-select">';
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
			'<button type="submit" class="button button-primary dac-btn-load" id="dac-load-widget-role">%s</button>',
			esc_html__( 'Load Rules', 'dashboard-access-control' )
		);
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</form>';

		if ( $selected ) {
			$this->render_widget_list( $selected );
		}

		echo '</div>';
	}

	/**
	 * Render the widget list matching the Admin Menu Control design.
	 *
	 * @param string $role_slug Role slug.
	 */
	private function render_widget_list( string $role_slug ): void {
		$profile = $this->repository->get( $role_slug );
		$widgets = $profile[ Constants::PROFILE_WIDGETS ] ?? [];

		$role_name = wp_roles()->roles[ $role_slug ]['name'] ?? $role_slug;

		echo '<form method="post" id="dac-widgets-form">';
		wp_nonce_field( 'dac_save_dashboard_widgets', '_dac_nonce' );
		echo '<input type="hidden" name="dac_action" value="dac_save_dashboard_widgets">';
		echo '<input type="hidden" name="dac_role" value="' . esc_attr( $role_slug ) . '">';

		// Toolbar matching Menu Control styling
		echo '<div class="dac-card dac-menu-toolbar">';
		echo '<div class="dac-toolbar-left">';
		printf(
			'<span class="dac-role-badge">%s: <strong>%s</strong></span>',
			esc_html__( 'Editing rules for', 'dashboard-access-control' ),
			esc_html( $role_name )
		);
		echo '</div>';
		echo '<div class="dac-toolbar-right">';
		echo '<div class="dac-search-box">';
		echo '<input type="text" id="dac-menu-search" placeholder="' . esc_attr__( 'Search dashboard widgets...', 'dashboard-access-control' ) . '" class="dac-search-input" />';
		echo '</div>';
		printf(
			'<button type="button" class="button dac-btn-sm dac-btn-danger" id="dac-hide-all">%s</button>',
			esc_html__( 'Hide All', 'dashboard-access-control' )
		);
		printf(
			'<button type="button" class="button dac-btn-sm dac-btn-success" id="dac-show-all">%s</button>',
			esc_html__( 'Show All', 'dashboard-access-control' )
		);
		echo '</div>';
		echo '</div>';

		// Widget list container
		echo '<div class="dac-menu-accordion" id="dac-menu-accordion">';
		$total_count  = 0;
		$hidden_count = 0;

		$captured = self::get_captured_widgets();
		if ( empty( $captured ) ) {
			echo '<div class="dac-empty-state">';
			echo '<span class="dac-icon dac-icon-warning"></span>';
			echo '<p>' . esc_html__( 'No dashboard widgets found.', 'dashboard-access-control' ) . '</p>';
			echo '</div>';
		} else {
			foreach ( $captured as $widget ) {
				$id        = (string) $widget['id'];
				$name      = (string) $widget['name'];
				$is_hidden = ! empty( $widgets[ $id ] );

				++$total_count;
				if ( $is_hidden ) {
					++$hidden_count;
				}

				$badge_class = $is_hidden ? 'dac-badge-hidden' : 'dac-badge-visible';
				$badge_text  = $is_hidden ? __( 'Hidden', 'dashboard-access-control' ) : __( 'Visible', 'dashboard-access-control' );

				echo '<div class="dac-accordion-item' . ( $is_hidden ? ' dac-item-hidden' : '' ) . '" data-slug="' . esc_attr( $id ) . '" data-label="' . esc_attr( strtolower( $name ) ) . '">';
				
				echo '<div class="dac-accordion-header">';
				echo '<div class="dac-accordion-left">';
				echo '<span class="dac-icon dashicons dashicons-dashboard" style="color:var(--dac-text-muted); font-size:16px; margin-right:8px;"></span>';
				echo '<span class="dac-item-label">' . esc_html( $name ) . '</span>';
				echo '<code style="margin-left:8px; font-size:11px; color:var(--dac-text-muted);">' . esc_html( $id ) . '</code>';
				echo '</div>';

				echo '<div class="dac-accordion-right">';
				// Toggle switch.
				printf(
					'<label class="dac-toggle" title="%s">',
					esc_attr__( 'Toggle visibility', 'dashboard-access-control' )
				);
				printf(
					'<input type="checkbox" name="dac_widgets[%s][hidden]" value="1" class="dac-toggle-input" %s />',
					esc_attr( $id ),
					checked( $is_hidden, true, false )
				);
				echo '<span class="dac-toggle-slider"></span>';
				echo '</label>';

				printf(
					'<input type="hidden" name="dac_all_rendered_widgets[%s]" value="%s" />',
					esc_attr( $id ),
					esc_attr( $name )
				);

				echo '<span class="dac-badge ' . esc_attr( $badge_class ) . '">' . esc_html( $badge_text ) . '</span>';
				echo '</div>'; // .dac-accordion-right

				echo '</div>'; // .dac-accordion-header
				echo '</div>'; // .dac-accordion-item
			}
		}
		echo '</div>';

		// Stats bar.
		echo '<div class="dac-stats-bar">';
		$shown_count = $total_count - $hidden_count;
		printf(
			'<span class="dac-stat dac-stat-shown"><span class="dac-stat-num">%d</span> %s</span>',
			$shown_count,
			esc_html__( 'visible', 'dashboard-access-control' )
		);
		printf(
			'<span class="dac-stat dac-stat-hidden"><span class="dac-stat-num">%d</span> %s</span>',
			$hidden_count,
			esc_html__( 'hidden', 'dashboard-access-control' )
		);
		printf(
			'<span class="dac-stat dac-stat-total"><span class="dac-stat-num">%d</span> %s</span>',
			$total_count,
			esc_html__( 'total', 'dashboard-access-control' )
		);
		echo '</div>';

		// Save button.
		echo '<div class="dac-submit-row">';
		submit_button( __( 'Save Widget Rules', 'dashboard-access-control' ), 'primary', 'submit', false );
		echo '</div>';

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

		$submitted_all = $_POST['dac_all_rendered_widgets'] ?? [];
		if ( ! is_array( $submitted_all ) ) {
			$submitted_all = [];
		}

		$widgets = [];
		foreach ( $submitted_all as $id => $name ) {
			$id = sanitize_text_field( wp_unslash( (string) $id ) );
			if ( '' === $id ) {
				continue;
			}
			$is_hidden = ! empty( $raw_widgets[ $id ]['hidden'] );
			if ( $is_hidden ) {
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
