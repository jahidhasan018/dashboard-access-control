<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin\Tabs;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Appearance tab — customize admin sidebar and body appearance.
 */
final class AppearanceTab {

	private Options $options;

	public function __construct( Options $options ) {
		$this->options = $options;
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
		$settings = $this->options->get( Constants::OPT_APPEARANCE, [] );

		echo '<div class="dac-appearance">';
		echo '<h2>' . esc_html__( 'Admin Appearance Settings', 'dashboard-access-control' ) . '</h2>';
		echo '<p class="dac-subtitle">' . esc_html__( 'Customize the look and feel of the WordPress admin area. Changes are applied globally to all users.', 'dashboard-access-control' ) . '</p>';

		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html__( 'Appearance settings saved.', 'dashboard-access-control' );
			echo '</p></div>';
		}

		echo '<form method="post" enctype="multipart/form-data">';
		wp_nonce_field( 'dac_save_appearance', '_dac_nonce' );
		echo '<input type="hidden" name="dac_action" value="dac_save_appearance">';

		// ── Sidebar Section ──────────────────────────────────────────────────
		echo '<div class="dac-card">';
		echo '<div class="dac-card-header">';
		echo '<span class="dac-icon dac-icon-admin-bar"></span>';
		echo '<strong>' . esc_html__( 'Sidebar Settings', 'dashboard-access-control' ) . '</strong>';
		echo '</div>';
		echo '<div class="dac-card-body">';

		echo '<table class="form-table">';

		// Sidebar background.
		echo '<tr><th scope="row">' . esc_html__( 'Sidebar Background Color', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<input type="text" name="dac_appear[%s]" value="%s" class="dac-color-picker" />',
			esc_attr( Constants::APPEAR_SIDEBAR_BG ),
			esc_attr( $settings[ Constants::APPEAR_SIDEBAR_BG ] ?? '' )
		);
		echo '</td></tr>';

		// Sidebar text color.
		echo '<tr><th scope="row">' . esc_html__( 'Sidebar Text Color', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<input type="text" name="dac_appear[%s]" value="%s" class="dac-color-picker" />',
			esc_attr( Constants::APPEAR_SIDEBAR_TEXT ),
			esc_attr( $settings[ Constants::APPEAR_SIDEBAR_TEXT ] ?? '' )
		);
		echo '</td></tr>';

		// Sidebar hover color.
		echo '<tr><th scope="row">' . esc_html__( 'Sidebar Hover Background', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<input type="text" name="dac_appear[%s]" value="%s" class="dac-color-picker" />',
			esc_attr( Constants::APPEAR_SIDEBAR_HOVER ),
			esc_attr( $settings[ Constants::APPEAR_SIDEBAR_HOVER ] ?? '' )
		);
		echo '</td></tr>';

		// Sidebar text hover color.
		echo '<tr><th scope="row">' . esc_html__( 'Sidebar Text Hover Color', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<input type="text" name="dac_appear[%s]" value="%s" class="dac-color-picker" />',
			esc_attr( Constants::APPEAR_SIDEBAR_TEXT_HOVER ),
			esc_attr( $settings[ Constants::APPEAR_SIDEBAR_TEXT_HOVER ] ?? '' )
		);
		echo '</td></tr>';

		// Sidebar font size.
		echo '<tr><th scope="row">' . esc_html__( 'Sidebar Font Size', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<input type="number" name="dac_appear[%s]" value="%s" min="10" max="24" step="1" class="small-text" /> px',
			esc_attr( Constants::APPEAR_SIDEBAR_FONT_SIZE ),
			esc_attr( $settings[ Constants::APPEAR_SIDEBAR_FONT_SIZE ] ?? '' )
		);
		echo '<p class="description">' . esc_html__( 'Default: 13px. Range: 10–24px.', 'dashboard-access-control' ) . '</p>';
		echo '</td></tr>';

		// Sidebar width.
		echo '<tr><th scope="row">' . esc_html__( 'Sidebar Width', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<input type="number" name="dac_appear[%s]" value="%s" min="120" max="400" step="1" class="small-text" /> px',
			esc_attr( Constants::APPEAR_SIDEBAR_WIDTH ),
			esc_attr( $settings[ Constants::APPEAR_SIDEBAR_WIDTH ] ?? '' )
		);
		echo '<p class="description">' . esc_html__( 'Default: 160px. Range: 120–400px.', 'dashboard-access-control' ) . '</p>';
		echo '</td></tr>';

		// Sidebar text align.
		echo '<tr><th scope="row">' . esc_html__( 'Sidebar Text Alignment', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<select name="dac_appear[%s]">',
			esc_attr( Constants::APPEAR_SIDEBAR_ALIGN )
		);
		$current_align = $settings[ Constants::APPEAR_SIDEBAR_ALIGN ] ?? 'left';
		printf( '<option value="left" %s>%s</option>', selected( $current_align, 'left', false ), esc_html__( 'Left', 'dashboard-access-control' ) );
		printf( '<option value="center" %s>%s</option>', selected( $current_align, 'center', false ), esc_html__( 'Center', 'dashboard-access-control' ) );
		printf( '<option value="right" %s>%s</option>', selected( $current_align, 'right', false ), esc_html__( 'Right', 'dashboard-access-control' ) );
		echo '</select>';
		echo '</td></tr>';

		echo '</table>';
		echo '</div>';
		echo '</div>';

		// ── Body / Content Section ───────────────────────────────────────────
		echo '<div class="dac-card">';
		echo '<div class="dac-card-header">';
		echo '<span class="dac-icon dac-icon-admin-bar"></span>';
		echo '<strong>' . esc_html__( 'Body & Content Settings', 'dashboard-access-control' ) . '</strong>';
		echo '</div>';
		echo '<div class="dac-card-body">';

		echo '<table class="form-table">';

		// Admin background.
		echo '<tr><th scope="row">' . esc_html__( 'Admin Body Background', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<input type="text" name="dac_appear[%s]" value="%s" class="dac-color-picker" />',
			esc_attr( Constants::APPEAR_ADMIN_BG ),
			esc_attr( $settings[ Constants::APPEAR_ADMIN_BG ] ?? '' )
		);
		echo '</td></tr>';

		// Body background image.
		echo '<tr><th scope="row">' . esc_html__( 'Body Background Image', 'dashboard-access-control' ) . '</th><td>';
		$bg_id = $settings[ Constants::APPEAR_BODY_BG_IMAGE ] ?? 0;
		if ( $bg_id ) {
			$bg_url = wp_get_attachment_image_url( $bg_id, 'medium' );
			if ( $bg_url ) {
				printf( '<p><img src="%s" alt="%s" style="max-width:200px;height:auto;" /></p>', esc_url( $bg_url ), esc_attr__( 'Current Background', 'dashboard-access-control' ) );
			}
		}
		printf(
			'<input type="hidden" name="dac_appear[%s]" value="%d" />',
			esc_attr( Constants::APPEAR_BODY_BG_IMAGE ),
			$bg_id
		);
		echo '<input type="file" name="dac_appear_bg_image" accept="image/*" />';
		if ( $bg_id ) {
			printf(
				' <label><input type="checkbox" name="dac_appear[remove_bg_image]" value="1"> %s</label>',
				esc_html__( 'Remove image', 'dashboard-access-control' )
			);
		}
		echo '</td></tr>';

		// Background size.
		echo '<tr><th scope="row">' . esc_html__( 'Background Size', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<select name="dac_appear[%s]">',
			esc_attr( Constants::APPEAR_BODY_BG_SIZE )
		);
		$current_size = $settings[ Constants::APPEAR_BODY_BG_SIZE ] ?? 'cover';
		printf( '<option value="cover" %s>%s</option>', selected( $current_size, 'cover', false ), esc_html__( 'Cover', 'dashboard-access-control' ) );
		printf( '<option value="contain" %s>%s</option>', selected( $current_size, 'contain', false ), esc_html__( 'Contain', 'dashboard-access-control' ) );
		printf( '<option value="auto" %s>%s</option>', selected( $current_size, 'auto', false ), esc_html__( 'Auto', 'dashboard-access-control' ) );
		printf( '<option value="repeat" %s>%s</option>', selected( $current_size, 'repeat', false ), esc_html__( 'Repeat', 'dashboard-access-control' ) );
		echo '</select>';
		echo '</td></tr>';

		// Text color.
		echo '<tr><th scope="row">' . esc_html__( 'Admin Text Color', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<input type="text" name="dac_appear[%s]" value="%s" class="dac-color-picker" />',
			esc_attr( Constants::APPEAR_ADMIN_TEXT_COLOR ),
			esc_attr( $settings[ Constants::APPEAR_ADMIN_TEXT_COLOR ] ?? '' )
		);
		echo '</td></tr>';

		// Link color.
		echo '<tr><th scope="row">' . esc_html__( 'Admin Link Color', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<input type="text" name="dac_appear[%s]" value="%s" class="dac-color-picker" />',
			esc_attr( Constants::APPEAR_ADMIN_LINK_COLOR ),
			esc_attr( $settings[ Constants::APPEAR_ADMIN_LINK_COLOR ] ?? '' )
		);
		echo '</td></tr>';

		echo '</table>';
		echo '</div>';
		echo '</div>';

		// ── Buttons & Borders Section ────────────────────────────────────────
		echo '<div class="dac-card">';
		echo '<div class="dac-card-header">';
		echo '<span class="dac-icon dac-icon-admin-bar"></span>';
		echo '<strong>' . esc_html__( 'Buttons & Borders', 'dashboard-access-control' ) . '</strong>';
		echo '</div>';
		echo '<div class="dac-card-body">';

		echo '<table class="form-table">';

		// Button color.
		echo '<tr><th scope="row">' . esc_html__( 'Primary Button Color', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<input type="text" name="dac_appear[%s]" value="%s" class="dac-color-picker" />',
			esc_attr( Constants::APPEAR_ADMIN_BTN_COLOR ),
			esc_attr( $settings[ Constants::APPEAR_ADMIN_BTN_COLOR ] ?? '' )
		);
		echo '</td></tr>';

		// Button text color.
		echo '<tr><th scope="row">' . esc_html__( 'Primary Button Text Color', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<input type="text" name="dac_appear[%s]" value="%s" class="dac-color-picker" />',
			esc_attr( Constants::APPEAR_ADMIN_BTN_TEXT ),
			esc_attr( $settings[ Constants::APPEAR_ADMIN_BTN_TEXT ] ?? '' )
		);
		echo '</td></tr>';

		// Border color.
		echo '<tr><th scope="row">' . esc_html__( 'Admin Border Color', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<input type="text" name="dac_appear[%s]" value="%s" class="dac-color-picker" />',
			esc_attr( Constants::APPEAR_ADMIN_BORDER_COLOR ),
			esc_attr( $settings[ Constants::APPEAR_ADMIN_BORDER_COLOR ] ?? '' )
		);
		echo '</td></tr>';

		echo '</table>';
		echo '</div>';
		echo '</div>';

		// Save button.
		echo '<div class="dac-submit-row">';
		submit_button( __( 'Save Appearance Settings', 'dashboard-access-control' ), 'primary', 'submit', false );
		echo '</div>';

		echo '</form>';
		echo '</div>';
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

		$raw = $_POST['dac_appear'] ?? [];
		if ( ! is_array( $raw ) ) {
			$raw = [];
		}

		$settings = $this->options->get( Constants::OPT_APPEARANCE, [] );

		// Handle background image upload.
		if ( ! empty( $_FILES['dac_appear_bg_image']['tmp_name'] ) ) {
			$upload_dir = wp_upload_dir();
			$attach_dir = $upload_dir['basedir'] . '/dac-branding';
			if ( ! file_exists( $attach_dir ) ) {
				wp_mkdir_p( $attach_dir );
			}
			$bg_id = $this->handle_image_upload( 'dac_appear_bg_image', $attach_dir );
			if ( $bg_id ) {
				$settings[ Constants::APPEAR_BODY_BG_IMAGE ] = $bg_id;
			}
		}
		if ( ! empty( $raw['remove_bg_image'] ) ) {
			unset( $settings[ Constants::APPEAR_BODY_BG_IMAGE ] );
		}

		// Text fields (color pickers, font sizes, etc).
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
				$settings[ $field ] = sanitize_text_field( wp_unslash( $raw[ $field ] ) );
			} elseif ( ! isset( $raw[ $field ] ) || '' === $raw[ $field ] ) {
				unset( $settings[ $field ] );
			}
		}

		$this->options->update( Constants::OPT_APPEARANCE, $settings );

		wp_safe_redirect( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() . '&saved=1' ) );
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
