<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin\Tabs;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\RoleProfileRepository;
use DashboardAccessControl\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Appearance tab — customize admin sidebar and body appearance per role.
 */
final class AppearanceTab {

	private RoleProfileRepository $repository;

	public function __construct( RoleProfileRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Tab identifier.
	 */
	public static function id(): string {
		return 'appearance';
	}

	/**
	 * Tab label.
	 */
	public static function label(): string {
		return __( 'Appearance', 'dashboard-access-control' );
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		$roles    = wp_roles()->roles;
		$selected = isset( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : '';

		// Persist selected role: use GET param if set, otherwise load from storage.
		$options = $this->repository->get_options();
		if ( '' !== $selected && isset( $roles[ $selected ] ) ) {
			$options->set_selected_role( self::id(), $selected );
		} else {
			$selected = $options->get_selected_role( self::id() );
		}

		// Default to first role if nothing stored yet.
		if ( '' === $selected || ! isset( $roles[ $selected ] ) ) {
			$selected = array_key_first( $roles );
		}

		// Build list of roles that have appearance settings applied.
		$all_profiles  = $this->repository->get_all();
		$applied_roles = [];
		foreach ( $all_profiles as $slug => $profile ) {
			$appear = $profile[ Constants::PROFILE_APPEARANCE ] ?? [];
			if ( ! empty( $appear ) ) {
				$applied_roles[] = $slug;
			}
		}

		$current = $this->repository->get( $selected );
		$appear  = $current[ Constants::PROFILE_APPEARANCE ] ?? [];

		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html__( 'Appearance settings saved.', 'dashboard-access-control' );
			echo '</p></div>';
		}
		?>
		<div class="dac-section">
			<h2 class="dac-section-title"><?php esc_html_e( 'Admin Appearance Settings', 'dashboard-access-control' ); ?></h2>
			<p class="dac-section-desc">
				<?php esc_html_e( 'Customize the look and feel of the WordPress admin area for specific roles.', 'dashboard-access-control' ); ?>
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
					<select id="dac-appear-role" class="dac-select" onchange="if(this.value)window.location.href='<?php echo esc_url( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() . '&role=' ) ); ?>' + this.value">
						<?php foreach ( $roles as $slug => $role_data ) :
							$has_settings = in_array( $slug, $applied_roles, true );
							$checkmark    = $has_settings ? ' ✓' : '';
						?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $selected ); ?>>
								<?php echo esc_html( $role_data['name'] . $checkmark ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>

		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() ) ); ?>">
			<?php wp_nonce_field( 'dac_save_appearance', '_dac_nonce' ); ?>
			<input type="hidden" name="dac_action" value="dac_save_appearance">
			<input type="hidden" name="dac_role" value="<?php echo esc_attr( $selected ); ?>">

			<div class="dac-section">
				<h3 class="dac-section-title"><?php echo esc_html( $roles[ $selected ]['name'] ); ?> — <?php esc_html_e( 'Appearance Settings', 'dashboard-access-control' ); ?></h3>
			</div>

			<!-- Sidebar Section -->
			<div class="dac-card">
				<div class="dac-card-header">
					<span class="dac-icon dac-icon-admin-bar"></span>
					<strong><?php esc_html_e( 'Sidebar Settings', 'dashboard-access-control' ); ?></strong>
				</div>
				<div class="dac-card-body">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Sidebar Background Color', 'dashboard-access-control' ); ?></th>
							<td><input type="text" name="dac_appear[<?php echo esc_attr( Constants::APPEAR_SIDEBAR_BG ); ?>]" value="<?php echo esc_attr( $appear[ Constants::APPEAR_SIDEBAR_BG ] ?? '' ); ?>" class="dac-color-picker" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Sidebar Text Color', 'dashboard-access-control' ); ?></th>
							<td><input type="text" name="dac_appear[<?php echo esc_attr( Constants::APPEAR_SIDEBAR_TEXT ); ?>]" value="<?php echo esc_attr( $appear[ Constants::APPEAR_SIDEBAR_TEXT ] ?? '' ); ?>" class="dac-color-picker" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Sidebar Hover Background', 'dashboard-access-control' ); ?></th>
							<td><input type="text" name="dac_appear[<?php echo esc_attr( Constants::APPEAR_SIDEBAR_HOVER ); ?>]" value="<?php echo esc_attr( $appear[ Constants::APPEAR_SIDEBAR_HOVER ] ?? '' ); ?>" class="dac-color-picker" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Sidebar Text Hover Color', 'dashboard-access-control' ); ?></th>
							<td><input type="text" name="dac_appear[<?php echo esc_attr( Constants::APPEAR_SIDEBAR_TEXT_HOVER ); ?>]" value="<?php echo esc_attr( $appear[ Constants::APPEAR_SIDEBAR_TEXT_HOVER ] ?? '' ); ?>" class="dac-color-picker" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Sidebar Font Size', 'dashboard-access-control' ); ?></th>
							<td>
								<input type="number" name="dac_appear[<?php echo esc_attr( Constants::APPEAR_SIDEBAR_FONT_SIZE ); ?>]" value="<?php echo esc_attr( $appear[ Constants::APPEAR_SIDEBAR_FONT_SIZE ] ?? '' ); ?>" min="10" max="24" step="1" class="small-text" /> px
								<p class="description"><?php esc_html_e( 'Default: 13px. Range: 10–24px.', 'dashboard-access-control' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Sidebar Width', 'dashboard-access-control' ); ?></th>
							<td>
								<input type="number" name="dac_appear[<?php echo esc_attr( Constants::APPEAR_SIDEBAR_WIDTH ); ?>]" value="<?php echo esc_attr( $appear[ Constants::APPEAR_SIDEBAR_WIDTH ] ?? '' ); ?>" min="120" max="400" step="1" class="small-text" /> px
								<p class="description"><?php esc_html_e( 'Default: 160px. Range: 120–400px.', 'dashboard-access-control' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Sidebar Text Alignment', 'dashboard-access-control' ); ?></th>
							<td>
								<select name="dac_appear[<?php echo esc_attr( Constants::APPEAR_SIDEBAR_ALIGN ); ?>]">
									<option value="left" <?php selected( $appear[ Constants::APPEAR_SIDEBAR_ALIGN ] ?? '', 'left' ); ?>><?php esc_html_e( 'Left', 'dashboard-access-control' ); ?></option>
									<option value="center" <?php selected( $appear[ Constants::APPEAR_SIDEBAR_ALIGN ] ?? '', 'center' ); ?>><?php esc_html_e( 'Center', 'dashboard-access-control' ); ?></option>
									<option value="right" <?php selected( $appear[ Constants::APPEAR_SIDEBAR_ALIGN ] ?? '', 'right' ); ?>><?php esc_html_e( 'Right', 'dashboard-access-control' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Body / Content Section -->
			<div class="dac-card">
				<div class="dac-card-header">
					<span class="dac-icon dac-icon-admin-bar"></span>
					<strong><?php esc_html_e( 'Body & Content Settings', 'dashboard-access-control' ); ?></strong>
				</div>
				<div class="dac-card-body">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Admin Body Background', 'dashboard-access-control' ); ?></th>
							<td><input type="text" name="dac_appear[<?php echo esc_attr( Constants::APPEAR_ADMIN_BG ); ?>]" value="<?php echo esc_attr( $appear[ Constants::APPEAR_ADMIN_BG ] ?? '' ); ?>" class="dac-color-picker" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Body Background Image', 'dashboard-access-control' ); ?></th>
							<td>
								<?php
								$bg_id = $appear[ Constants::APPEAR_BODY_BG_IMAGE ] ?? 0;
								if ( $bg_id ) {
									$bg_url = wp_get_attachment_image_url( $bg_id, 'medium' );
									if ( $bg_url ) {
										printf( '<p><img src="%s" alt="%s" style="max-width:200px;height:auto;" /></p>', esc_url( $bg_url ), esc_attr__( 'Current Background', 'dashboard-access-control' ) );
									}
								}
								?>
								<input type="hidden" name="dac_appear[<?php echo esc_attr( Constants::APPEAR_BODY_BG_IMAGE ); ?>]" value="<?php echo esc_attr( $bg_id ); ?>" />
								<input type="file" name="dac_appear_bg_image" accept="image/*" />
								<?php if ( $bg_id ) : ?>
									<label><input type="checkbox" name="dac_appear[remove_bg_image]" value="1"> <?php esc_html_e( 'Remove image', 'dashboard-access-control' ); ?></label>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Background Size', 'dashboard-access-control' ); ?></th>
							<td>
								<select name="dac_appear[<?php echo esc_attr( Constants::APPEAR_BODY_BG_SIZE ); ?>]">
									<option value="cover" <?php selected( $appear[ Constants::APPEAR_BODY_BG_SIZE ] ?? '', 'cover' ); ?>><?php esc_html_e( 'Cover', 'dashboard-access-control' ); ?></option>
									<option value="contain" <?php selected( $appear[ Constants::APPEAR_BODY_BG_SIZE ] ?? '', 'contain' ); ?>><?php esc_html_e( 'Contain', 'dashboard-access-control' ); ?></option>
									<option value="auto" <?php selected( $appear[ Constants::APPEAR_BODY_BG_SIZE ] ?? '', 'auto' ); ?>><?php esc_html_e( 'Auto', 'dashboard-access-control' ); ?></option>
									<option value="repeat" <?php selected( $appear[ Constants::APPEAR_BODY_BG_SIZE ] ?? '', 'repeat' ); ?>><?php esc_html_e( 'Repeat', 'dashboard-access-control' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Admin Text Color', 'dashboard-access-control' ); ?></th>
							<td><input type="text" name="dac_appear[<?php echo esc_attr( Constants::APPEAR_ADMIN_TEXT_COLOR ); ?>]" value="<?php echo esc_attr( $appear[ Constants::APPEAR_ADMIN_TEXT_COLOR ] ?? '' ); ?>" class="dac-color-picker" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Admin Link Color', 'dashboard-access-control' ); ?></th>
							<td><input type="text" name="dac_appear[<?php echo esc_attr( Constants::APPEAR_ADMIN_LINK_COLOR ); ?>]" value="<?php echo esc_attr( $appear[ Constants::APPEAR_ADMIN_LINK_COLOR ] ?? '' ); ?>" class="dac-color-picker" /></td>
						</tr>
					</table>
				</div>
			</div>

			<!-- Buttons & Borders Section -->
			<div class="dac-card">
				<div class="dac-card-header">
					<span class="dac-icon dac-icon-admin-bar"></span>
					<strong><?php esc_html_e( 'Buttons & Borders', 'dashboard-access-control' ); ?></strong>
				</div>
				<div class="dac-card-body">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Primary Button Color', 'dashboard-access-control' ); ?></th>
							<td><input type="text" name="dac_appear[<?php echo esc_attr( Constants::APPEAR_ADMIN_BTN_COLOR ); ?>]" value="<?php echo esc_attr( $appear[ Constants::APPEAR_ADMIN_BTN_COLOR ] ?? '' ); ?>" class="dac-color-picker" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Primary Button Text Color', 'dashboard-access-control' ); ?></th>
							<td><input type="text" name="dac_appear[<?php echo esc_attr( Constants::APPEAR_ADMIN_BTN_TEXT ); ?>]" value="<?php echo esc_attr( $appear[ Constants::APPEAR_ADMIN_BTN_TEXT ] ?? '' ); ?>" class="dac-color-picker" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Admin Border Color', 'dashboard-access-control' ); ?></th>
							<td><input type="text" name="dac_appear[<?php echo esc_attr( Constants::APPEAR_ADMIN_BORDER_COLOR ); ?>]" value="<?php echo esc_attr( $appear[ Constants::APPEAR_ADMIN_BORDER_COLOR ] ?? '' ); ?>" class="dac-color-picker" /></td>
						</tr>
					</table>
				</div>
			</div>

			<div class="dac-submit-row">
				<?php submit_button( __( 'Save Appearance Settings', 'dashboard-access-control' ), 'primary', 'submit', false ); ?>
			</div>
		</form>
		<?php
	}

	/**
	 * Handle form submission.
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['dac_action'] ) || 'dac_save_appearance' !== $_POST['dac_action'] ) {
			return;
		}

		if ( ! current_user_can( Constants::CAP_MANAGE_SETTINGS ) ) {
			return;
		}

		check_admin_referer( 'dac_save_appearance', '_dac_nonce' );

		$role_slug = sanitize_text_field( wp_unslash( $_POST['dac_role'] ?? '' ) );
		if ( '' === $role_slug ) {
			return;
		}

		$roles = wp_roles()->roles;
		if ( ! isset( $roles[ $role_slug ] ) ) {
			return;
		}

		$raw = $_POST['dac_appear'] ?? [];
		if ( ! is_array( $raw ) ) {
			$raw = [];
		}

		$profile = $this->repository->get( $role_slug );
		$appear  = $profile[ Constants::PROFILE_APPEARANCE ] ?? [];

		// Handle background image upload.
		if ( ! empty( $_FILES['dac_appear_bg_image']['tmp_name'] ) ) {
			$upload_dir = wp_upload_dir();
			$attach_dir = $upload_dir['basedir'] . '/dac-branding';
			if ( ! file_exists( $attach_dir ) ) {
				wp_mkdir_p( $attach_dir );
			}
			$bg_id = $this->handle_image_upload( 'dac_appear_bg_image', $attach_dir );
			if ( $bg_id ) {
				$appear[ Constants::APPEAR_BODY_BG_IMAGE ] = $bg_id;
			}
		}
		if ( ! empty( $raw['remove_bg_image'] ) ) {
			unset( $appear[ Constants::APPEAR_BODY_BG_IMAGE ] );
		}

		// Text fields.
		$text_fields = [
			Constants::APPEAR_ADMIN_BG,
			Constants::APPEAR_SIDEBAR_BG,
			Constants::APPEAR_SIDEBAR_HOVER,
			Constants::APPEAR_SIDEBAR_TEXT,
			Constants::APPEAR_SIDEBAR_TEXT_HOVER,
			Constants::APPEAR_SIDEBAR_FONT_SIZE,
			Constants::APPEAR_SIDEBAR_WIDTH,
			Constants::APPEAR_SIDEBAR_ALIGN,
			Constants::APPEAR_ADMIN_TEXT_COLOR,
			Constants::APPEAR_ADMIN_LINK_COLOR,
			Constants::APPEAR_ADMIN_BTN_COLOR,
			Constants::APPEAR_ADMIN_BTN_TEXT,
			Constants::APPEAR_ADMIN_BORDER_COLOR,
			Constants::APPEAR_BODY_BG_SIZE,
		];

		foreach ( $text_fields as $field ) {
			if ( isset( $raw[ $field ] ) && '' !== $raw[ $field ] ) {
				$appear[ $field ] = sanitize_text_field( wp_unslash( $raw[ $field ] ) );
			} elseif ( ! isset( $raw[ $field ] ) || '' === $raw[ $field ] ) {
				unset( $appear[ $field ] );
			}
		}

		$profile[ Constants::PROFILE_APPEARANCE ] = $appear;
		$this->repository->save( $role_slug, $profile );

		wp_safe_redirect( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() . '&role=' . $role_slug . '&saved=1' ) );
		exit;
	}

	/**
	 * Handle image upload.
	 */
	private function handle_image_upload( string $field_key, string $upload_dir ): int {
		if ( empty( $_FILES[ $field_key ]['tmp_name'] ) ) {
			return 0;
		}

		$file = $_FILES[ $field_key ];
		$allowed_types = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
		$file_type     = wp_check_filetype( $file['name'] );

		if ( ! in_array( $file['type'], $allowed_types, true ) && ! in_array( $file_type['type'] ?? '', $allowed_types, true ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment = [
			'post_mime_type' => $file['type'],
			'post_title'     => sanitize_file_name( pathinfo( $file['name'], PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attach_id = media_handle_sideload( [
			'name'     => $file['name'],
			'type'     => $file['type'],
			'tmp_name' => $file['tmp_name'],
			'error'    => $file['error'],
			'size'     => $file['size'],
		], 0, '', $attachment );

		return is_wp_error( $attach_id ) ? 0 : $attach_id;
	}
}
