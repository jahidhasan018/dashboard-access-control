<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin\Tabs;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleProfileRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Menu Control tab — hierarchical accordion UI with toggle switches.
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
	 */
	public function capture_menu(): void {
		global $menu, $submenu;
		self::$captured_menu    = $menu ?? [];
		self::$captured_submenu = $submenu ?? [];

		// Store available menus (both top-level and submenus) so handle_save (on admin_init) can access them.
		$available = [];
		foreach ( self::$captured_menu as $menu_item ) {
			$slug  = $menu_item[2] ?? '';
			$label = wp_strip_all_tags( $menu_item[0] ?? '' );
			if ( '' !== $slug && ! str_starts_with( $slug, 'separator' ) ) {
				$available[ $slug ] = $label;
			}
		}

		foreach ( self::$captured_submenu as $parent_slug => $sub_items ) {
			if ( ! is_array( $sub_items ) ) {
				continue;
			}
			foreach ( $sub_items as $sub_item ) {
				$slug  = $sub_item[2] ?? '';
				$label = wp_strip_all_tags( $sub_item[0] ?? '' );
				if ( '' !== $slug ) {
					$available[ $slug ] = $label;
				}
			}
		}

		$stored = get_option( 'dac_available_menus', [] );
		if ( $stored !== $available ) {
			update_option( 'dac_available_menus', $available );
		}
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		echo '<div class="dac-menu-control">';
		echo '<h2>' . esc_html__( 'Admin Menu Control', 'dashboard-access-control' ) . '</h2>';
		echo '<p class="dac-subtitle">' . esc_html__( 'Select a role, then toggle visibility for each menu item. Restrictions enforce at capability, route, and AJAX/REST layers.', 'dashboard-access-control' ) . '</p>';

		$roles    = wp_roles()->roles;
		$selected = isset( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : '';

		// Persist selected role: use GET param if set, otherwise load from storage.
		$options = $this->repository->get_options();
		if ( '' !== $selected && isset( $roles[ $selected ] ) ) {
			$options->set_selected_role( self::id(), $selected );
		} else {
			$selected = $options->get_selected_role( self::id() );
		}

		// Build list of roles that have menu settings applied.
		$all_profiles  = $this->repository->get_all();
		$applied_roles = [];
		foreach ( $all_profiles as $slug => $profile ) {
			$menus = $profile[ Constants::PROFILE_MENUS ] ?? [];
			if ( ! empty( $menus ) ) {
				$applied_roles[] = $slug;
			}
		}

		// Role selector card.
		echo '<div class="dac-card dac-role-selector">';
		echo '<div class="dac-card-header">';
		echo '<span class="dac-icon dac-icon-users"></span>';
		echo '<strong>' . esc_html__( 'Select Role', 'dashboard-access-control' ) . '</strong>';
		echo '</div>';
		echo '<div class="dac-card-body">';
		echo '<div class="dac-role-picker">';
		printf(
			'<select id="dac-menu-role" class="dac-select" onchange="if(this.value)window.location.href=\'%s\' + this.value">',
			esc_url( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() . '&role=' ) )
		);
		echo '<option value="">' . esc_html__( '— Choose a Role —', 'dashboard-access-control' ) . '</option>';
		foreach ( $roles as $slug => $role_data ) {
			$has_settings = in_array( $slug, $applied_roles, true );
			$checkmark    = $has_settings ? ' ✓' : '';
			printf(
				'<option value="%s" %s>%s%s</option>',
				esc_attr( $slug ),
				selected( $slug, $selected, false ),
				esc_html( $role_data['name'] ),
				esc_html( $checkmark )
			);
		}
		echo '</select>';
		echo '</div>';
		echo '</div>';
		echo '</div>';

		if ( $selected && isset( $roles[ $selected ] ) ) {
			$this->render_menu_accordion( $selected );
		}

		echo '</div>';
	}

	/**
	 * Build a hierarchical menu tree (no recursion for rendering).
	 *
	 * @return array<int, array{slug: string, label: string, children: array<int, array{slug: string, label: string}>}>
	 */
	private function build_menu_tree(): array {
		$tree = [];

		if ( empty( self::$captured_menu ) ) {
			return $tree;
		}

		foreach ( self::$captured_menu as $menu_item ) {
			$slug  = $menu_item[2] ?? '';
			$label = wp_strip_all_tags( $menu_item[0] ?? '' );
			if ( '' === $slug || str_starts_with( $slug, 'separator' ) ) {
				continue;
			}

			$children = [];
			if ( isset( self::$captured_submenu[ $slug ] ) ) {
				foreach ( self::$captured_submenu[ $slug ] as $sub ) {
					$sub_slug  = $sub[2] ?? '';
					$sub_label = wp_strip_all_tags( $sub[0] ?? '' );
					if ( '' !== $sub_slug ) {
						$children[] = [
							'slug'  => $sub_slug,
							'label' => $sub_label,
						];
					}
				}
			}

			$tree[] = [
				'slug'     => $slug,
				'label'    => $label,
				'children' => $children,
			];
		}

		return $tree;
	}

	/**
	 * Render the hierarchical menu accordion for a role.
	 *
	 * @param string $role_slug Role slug.
	 */
	private function render_menu_accordion( string $role_slug ): void {
		$hidden    = $this->get_hidden_slugs( $role_slug );
		$role_name = wp_roles()->roles[ $role_slug ]['name'] ?? $role_slug;
		$tree      = $this->build_menu_tree();

		$hidden_count = 0;
		$total_count  = 0;

		echo '<form method="post" id="dac-menu-form">';
		wp_nonce_field( 'dac_save_menu_control', '_dac_nonce' );
		echo '<input type="hidden" name="dac_action" value="dac_save_menu_control">';
		echo '<input type="hidden" name="dac_role" value="' . esc_attr( $role_slug ) . '">';

		// Toolbar.
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
		echo '<input type="text" id="dac-menu-search" placeholder="' . esc_attr__( 'Search menu items...', 'dashboard-access-control' ) . '" class="dac-search-input" />';
		echo '</div>';
		printf(
			'<button type="button" class="button dac-btn-sm" id="dac-expand-all">%s</button>',
			esc_html__( 'Expand All', 'dashboard-access-control' )
		);
		printf(
			'<button type="button" class="button dac-btn-sm" id="dac-collapse-all">%s</button>',
			esc_html__( 'Collapse All', 'dashboard-access-control' )
		);
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

		// Menu accordion.
		echo '<div class="dac-menu-accordion" id="dac-menu-accordion">';

		if ( empty( $tree ) ) {
			echo '<div class="dac-empty-state">';
			echo '<span class="dac-icon dac-icon-warning"></span>';
			echo '<p>' . esc_html__( 'No menu items captured. Visit any admin page first to populate the menu list.', 'dashboard-access-control' ) . '</p>';
			echo '</div>';
		} else {
			foreach ( $tree as $item ) {
				++$total_count;
				$has_children = ! empty( $item['children'] );
				$parent_hidden = in_array( $item['slug'], $hidden, true );
				$children_hidden = 0;
				$children_total  = 0;

				if ( $parent_hidden ) {
					++$hidden_count;
				}

				// Count hidden children.
				if ( $has_children ) {
					foreach ( $item['children'] as $child ) {
						++$children_total;
						if ( in_array( $child['slug'], $hidden, true ) ) {
							++$children_hidden;
							++$hidden_count;
						}
						++$total_count;
					}
				}

				$this->render_accordion_item( $item, $hidden, $parent_hidden, $children_hidden, $children_total );
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
		submit_button( __( 'Save Menu Rules', 'dashboard-access-control' ), 'primary', 'submit', false );
		echo '</div>';

		echo '</form>';
	}

	/**
	 * Render a single accordion item (parent with optional children).
	 *
	 * @param array    $item             Menu item data.
	 * @param string[] $hidden           Hidden slugs.
	 * @param bool     $parent_hidden    Whether parent is hidden.
	 * @param int      $children_hidden  Number of hidden children.
	 * @param int      $children_total   Total children count.
	 */
	private function render_accordion_item( array $item, array $hidden, bool $parent_hidden, int $children_hidden, int $children_total ): void {
		$has_children = ! empty( $item['children'] );
		$slug         = $item['slug'];

		// Determine badge status.
		$badge_class = $parent_hidden ? 'dac-badge-hidden' : 'dac-badge-visible';
		$badge_text  = $parent_hidden ? __( 'Hidden', 'dashboard-access-control' ) : __( 'Visible', 'dashboard-access-control' );

		echo '<div class="dac-accordion-item' . ( $parent_hidden ? ' dac-item-hidden' : '' ) . '" data-slug="' . esc_attr( $slug ) . '" data-label="' . esc_attr( strtolower( $item['label'] ) ) . '">';

		// Accordion header.
		echo '<div class="dac-accordion-header">';
		echo '<div class="dac-accordion-left">';
		if ( $has_children ) {
			echo '<span class="dac-accordion-arrow">&#9654;</span>';
		} else {
			echo '<span class="dac-accordion-spacer"></span>';
		}
		echo '<span class="dac-item-label">' . esc_html( $item['label'] ) . '</span>';
		if ( $has_children ) {
			printf(
				'<span class="dac-child-count">%d/%d</span>',
				$children_total - $children_hidden,
				$children_total
			);
		}
		echo '</div>';
		echo '<div class="dac-accordion-right">';

		// Toggle switch.
		printf(
			'<label class="dac-toggle" title="%s">',
			esc_attr__( 'Toggle visibility', 'dashboard-access-control' )
		);
		printf(
			'<input type="checkbox" name="dac_menus[%s][hidden]" value="1" class="dac-toggle-input" %s data-parent="1" />',
			esc_attr( $slug ),
			checked( $parent_hidden, true, false )
		);
		echo '<span class="dac-toggle-slider"></span>';
		echo '</label>';

		printf(
			'<input type="hidden" name="dac_all_rendered_menus[%s]" value="%s" />',
			esc_attr( $slug ),
			esc_attr( $item['label'] )
		);

		echo '<span class="dac-badge ' . esc_attr( $badge_class ) . '">' . esc_html( $badge_text ) . '</span>';
		echo '</div>';
		echo '</div>';

		// Children container.
		if ( $has_children ) {
			echo '<div class="dac-accordion-children">';
			foreach ( $item['children'] as $child ) {
				$child_hidden = in_array( $child['slug'], $hidden, true );
				$child_badge  = $child_hidden ? 'dac-badge-hidden' : 'dac-badge-visible';
				$child_text   = $child_hidden ? __( 'Hidden', 'dashboard-access-control' ) : __( 'Visible', 'dashboard-access-control' );

				echo '<div class="dac-child-item' . ( $child_hidden ? ' dac-item-hidden' : '' ) . '" data-slug="' . esc_attr( $child['slug'] ) . '" data-label="' . esc_attr( strtolower( $child['label'] ) ) . '">';
				echo '<div class="dac-child-left">';
				echo '<span class="dac-child-dot"></span>';
				echo '<span class="dac-item-label">' . esc_html( $child['label'] ) . '</span>';
				echo '</div>';
				echo '<div class="dac-child-right">';

				printf(
					'<label class="dac-toggle dac-toggle-sm" title="%s">',
					esc_attr__( 'Toggle visibility', 'dashboard-access-control' )
				);
				printf(
					'<input type="checkbox" name="dac_menus[%s][hidden]" value="1" class="dac-toggle-input" %s data-child="1" />',
					esc_attr( $child['slug'] ),
					checked( $child_hidden, true, false )
				);
				echo '<span class="dac-toggle-slider"></span>';
				echo '</label>';

				printf(
					'<input type="hidden" name="dac_all_rendered_menus[%s]" value="%s" />',
					esc_attr( $child['slug'] ),
					esc_attr( $child['label'] )
				);

				echo '<span class="dac-badge ' . esc_attr( $child_badge ) . '">' . esc_html( $child_text ) . '</span>';
				echo '</div>';
				echo '</div>';
			}
			echo '</div>';
		}

		echo '</div>';
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

		$submitted_all = $_POST['dac_all_rendered_menus'] ?? [];
		if ( ! is_array( $submitted_all ) ) {
			$submitted_all = [];
		}

		$raw_menus = $_POST['dac_menus'] ?? [];
		if ( ! is_array( $raw_menus ) ) {
			$raw_menus = [];
		}

		// Build hidden lookup from submitted form data.
		$hidden_slugs = [];
		foreach ( $raw_menus as $slug => $data ) {
			if ( '1' === ( $data['hidden'] ?? '0' ) ) {
				$hidden_slugs[] = sanitize_text_field( wp_unslash( (string) $slug ) );
			}
		}

		// Load ALL available menus from the option saved during capture_menu.
		$available = get_option( 'dac_available_menus', [] );
		if ( ! is_array( $available ) ) {
			$available = [];
		}

		// Merge all known menus: rendered in this form + captured in option.
		$all_known = [];
		foreach ( $submitted_all as $slug => $label ) {
			$s = sanitize_text_field( wp_unslash( (string) $slug ) );
			if ( '' !== $s ) {
				$all_known[ $s ] = sanitize_text_field( wp_unslash( (string) $label ) );
			}
		}
		foreach ( $available as $slug => $label ) {
			$s = (string) $slug;
			if ( '' !== $s && ! isset( $all_known[ $s ] ) ) {
				$all_known[ $s ] = (string) $label;
			}
		}

		// Load existing profile to preserve labels, icons.
		$profile = $this->repository->get( $role_slug );
		$existing_menus = $profile[ Constants::PROFILE_MENUS ] ?? [];

		$existing_by_slug = [];
		foreach ( $existing_menus as $item ) {
			$slug = $item['slug'] ?? '';
			if ( '' !== $slug ) {
				$existing_by_slug[ $slug ] = $item;
			}
		}

		// Rebuild menus: set hidden status based on submitted form.
		$menus = [];
		foreach ( $all_known as $slug => $label ) {
			$existing = $existing_by_slug[ $slug ] ?? [];
			$menus[] = [
				'slug'   => $slug,
				'hidden' => in_array( $slug, $hidden_slugs, true ),
				'label'  => ! empty( $existing['label'] ) ? $existing['label'] : $label,
				'icon'   => $existing['icon'] ?? '',
			];
		}

		$profile[ Constants::PROFILE_MENUS ] = $menus;

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
