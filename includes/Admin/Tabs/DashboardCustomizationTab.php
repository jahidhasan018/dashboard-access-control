<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin\Tabs;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleProfileRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard Customization tab — role-based UI controls for the admin dashboard.
 */
final class DashboardCustomizationTab {

	private RoleProfileRepository $repository;

	public function __construct( RoleProfileRepository $repository ) {
		$this->repository = $repository;
	}

	public static function id(): string {
		return 'dashboard-customization';
	}

	public static function label(): string {
		return __( 'Dashboard', 'dashboard-access-control' );
	}

	/**
	 * Handle save from admin_init.
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['dac_action'] ) || 'dac_save_dashboard_customization' !== $_POST['dac_action'] ) {
			return;
		}

		if ( ! current_user_can( Constants::CAP_MANAGE_SETTINGS ) ) {
			return;
		}

		check_admin_referer( 'dac_save_dashboard_customization', '_dac_nonce' );

		$role_slug = sanitize_text_field( wp_unslash( $_POST['dac_role'] ?? '' ) );
		if ( '' === $role_slug ) {
			return;
		}

		$roles = wp_roles()->roles;
		if ( ! isset( $roles[ $role_slug ] ) ) {
			return;
		}

		$profile = $this->repository->get( $role_slug );

		$profile[ Constants::PROFILE_DASHBOARD ] = [
			Constants::DASH_REMOVE_SCREEN_OPTIONS => ! empty( $_POST['dac_dash']['remove_screen_options'] ),
			Constants::DASH_REMOVE_HELP_TAB       => ! empty( $_POST['dac_dash']['remove_help_tab'] ),
			Constants::DASH_FULL_WIDTH            => ! empty( $_POST['dac_dash']['full_width'] ),
			Constants::DASH_DISABLE_DRAGGING      => ! empty( $_POST['dac_dash']['disable_dragging'] ),
		];

		$this->repository->save( $role_slug, $profile );

		wp_safe_redirect(
			admin_url(
				'options-general.php?page=' . Constants::MENU_SLUG
				. '&tab=' . self::id()
				. '&role=' . $role_slug
				. '&saved=1'
			)
		);
		exit;
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		$roles    = wp_roles()->roles;
		$selected = isset( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : '';

		if ( '' === $selected || ! isset( $roles[ $selected ] ) ) {
			$selected = array_key_first( $roles );
		}

		// Build list of roles that have dashboard settings applied.
		$all_profiles  = $this->repository->get_all();
		$applied_roles = [];
		foreach ( $all_profiles as $slug => $profile ) {
			$dash = $profile[ Constants::PROFILE_DASHBOARD ] ?? [];
			if ( ! empty( $dash ) ) {
				$applied_roles[] = $slug;
			}
		}

		$current = $this->repository->get( $selected );
		$dash    = $current[ Constants::PROFILE_DASHBOARD ] ?? [];

		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html__( 'Dashboard customization settings saved.', 'dashboard-access-control' );
			echo '</p></div>';
		}
		?>
		<div class="dac-section">
			<h2 class="dac-section-title"><?php esc_html_e( 'Dashboard Customization', 'dashboard-access-control' ); ?></h2>
			<p class="dac-section-desc">
				<?php esc_html_e( 'Customize the admin dashboard experience for specific roles. These settings apply only to users with the selected role.', 'dashboard-access-control' ); ?>
			</p>
		</div>

		<!-- Role selector with checkmarks -->
		<div class="dac-card dac-role-selector">
			<div class="dac-card-header">
				<span class="dac-icon dac-icon-users"></span>
				<strong><?php esc_html_e( 'Select Role', 'dashboard-access-control' ); ?></strong>
			</div>
			<div class="dac-card-body">
				<div class="dac-role-picker">
					<select id="dac-dash-role" class="dac-select" onchange="if(this.value)window.location.href='<?php echo esc_url( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() . '&role=' ) ); ?>' + this.value">
						<?php foreach ( $roles as $slug => $role_data ) :
							$has_settings = in_array( $slug, $applied_roles, true );
							$checkmark    = $has_settings ? ' ✓' : '';
							$label        = $role_data['name'] . $checkmark;
						?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $selected ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() ) ); ?>">
			<?php wp_nonce_field( 'dac_save_dashboard_customization', '_dac_nonce' ); ?>
			<input type="hidden" name="dac_action" value="dac_save_dashboard_customization">
			<input type="hidden" name="dac_role" value="<?php echo esc_attr( $selected ); ?>">

			<div class="dac-section">
				<h3 class="dac-section-title"><?php echo esc_html( $roles[ $selected ]['name'] ); ?> — <?php esc_html_e( 'Dashboard Options', 'dashboard-access-control' ); ?></h3>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Remove Screen Options', 'dashboard-access-control' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="dac_dash[remove_screen_options]" value="1" <?php checked( ! empty( $dash[ Constants::DASH_REMOVE_SCREEN_OPTIONS ] ) ); ?>>
								<?php esc_html_e( 'Hide the Screen Options tab on all admin pages for this role.', 'dashboard-access-control' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Remove Help Tab', 'dashboard-access-control' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="dac_dash[remove_help_tab]" value="1" <?php checked( ! empty( $dash[ Constants::DASH_REMOVE_HELP_TAB ] ) ); ?>>
								<?php esc_html_e( 'Hide the Help tab on all admin pages for this role.', 'dashboard-access-control' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Full-Width Dashboard', 'dashboard-access-control' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="dac_dash[full_width]" value="1" <?php checked( ! empty( $dash[ Constants::DASH_FULL_WIDTH ] ) ); ?>>
								<?php esc_html_e( 'Expand the dashboard to full width (remove column constraints).', 'dashboard-access-control' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Disable Widget Dragging', 'dashboard-access-control' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="dac_dash[disable_dragging]" value="1" <?php checked( ! empty( $dash[ Constants::DASH_DISABLE_DRAGGING ] ) ); ?>>
								<?php esc_html_e( 'Prevent users from dragging/reordering dashboard widgets.', 'dashboard-access-control' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'dashboard-access-control' ); ?>">
				</p>
			</div>
		</form>
		<?php
	}
}
