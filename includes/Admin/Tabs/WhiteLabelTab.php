<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin\Tabs;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * White Label tab — branding, logos, login page, footer, Howdy text, favicon.
 */
final class WhiteLabelTab {

	private Options $options;

	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Tab identifier.
	 */
	public static function id(): string {
		return 'white-label';
	}

	/**
	 * Tab label.
	 */
	public static function label(): string {
		return __( 'White Label', 'dashboard-access-control' );
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		$settings = $this->options->get( Constants::OPT_WHITE_LABEL, [] );

		echo '<div class="dac-white-label">';
		echo '<h2>' . esc_html__( 'White Label Settings', 'dashboard-access-control' ) . '</h2>';
		echo '<p>' . esc_html__( 'Replace WordPress branding with your own. All changes are applied globally and are reversible on deactivation.', 'dashboard-access-control' ) . '</p>';

		echo '<form method="post" enctype="multipart/form-data">';
		wp_nonce_field( 'dac_save_white_label', '_dac_nonce' );
		echo '<input type="hidden" name="dac_action" value="dac_save_white_label">';

		echo '<table class="form-table">';

		// ── Admin Footer Text ───────────────────────────────────────────────
		echo '<tr><th scope="row">' . esc_html__( 'Admin Footer Text', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<textarea name="dac_wl[footer_text]" rows="3" cols="50" class="large-text">%s</textarea>',
			esc_textarea( $settings['footer_text'] ?? '' )
		);
		echo '<p class="description">' . esc_html__( 'Replace the default "Thank you for creating with WordPress." text. HTML is allowed.', 'dashboard-access-control' ) . '</p>';
		echo '</td></tr>';

		// ── Howdy Text ──────────────────────────────────────────────────────
		echo '<tr><th scope="row">' . esc_html__( '"Howdy" Text Replacement', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<input type="text" name="dac_wl[howdy_text]" value="%s" class="regular-text" placeholder="%s" />',
			esc_attr( $settings['howdy_text'] ?? '' ),
			esc_attr__( 'e.g., Welcome', 'dashboard-access-control' )
		);
		echo '<p class="description">' . esc_html__( 'Replace the "Howdy" greeting in the admin bar. Leave empty to keep default.', 'dashboard-access-control' ) . '</p>';
		echo '</td></tr>';

		// ── Favicon ─────────────────────────────────────────────────────────
		echo '<tr><th scope="row">' . esc_html__( 'Custom Favicon', 'dashboard-access-control' ) . '</th><td>';
		$favicon_id = $settings['favicon_id'] ?? 0;
		if ( $favicon_id ) {
			$favicon_url = wp_get_attachment_image_url( $favicon_id, 'full' );
			if ( $favicon_url ) {
				printf( '<p><img src="%s" alt="%s" style="width:32px;height:auto;" /></p>', esc_url( $favicon_url ), esc_attr__( 'Current Favicon', 'dashboard-access-control' ) );
			}
		}
		printf(
			'<input type="hidden" name="dac_wl[favicon_id]" value="%d" />',
			$favicon_id
		);
		echo '<input type="file" name="dac_wl_favicon" accept="image/*" />';
		if ( $favicon_id ) {
			printf(
				' <label><input type="checkbox" name="dac_wl[remove_favicon]" value="1"> %s</label>',
				esc_html__( 'Remove favicon', 'dashboard-access-control' )
			);
		}
		echo '</td></tr>';

		// ── Hide Version Number ─────────────────────────────────────────────
		echo '<tr><th scope="row">' . esc_html__( 'Hide WordPress Version', 'dashboard-access-control' ) . '</th><td>';
		printf(
			'<label><input type="checkbox" name="dac_wl[hide_version]" value="1" %s> %s</label>',
			checked( ! empty( $settings['hide_version'] ), true, false ),
			esc_html__( 'Hide the WordPress version number from admin pages and login screen', 'dashboard-access-control' )
		);
		echo '</td></tr>';

		echo '</table>';

		submit_button( __( 'Save White Label Settings', 'dashboard-access-control' ), 'primary', 'submit', false );
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Handle form submission.
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['dac_action'] ) || 'dac_save_white_label' !== $_POST['dac_action'] ) {
			return;
		}

		if ( ! current_user_can( Constants::CAP_MANAGE_SETTINGS ) ) {
			return;
		}

		check_admin_referer( 'dac_save_white_label', '_dac_nonce' );

		$raw = $_POST['dac_wl'] ?? [];
		if ( ! is_array( $raw ) ) {
			$raw = [];
		}

		$settings = $this->options->get( Constants::OPT_WHITE_LABEL, [] );

		// Handle image uploads.
		$upload_dir = wp_upload_dir();
		$attach_dir = $upload_dir['basedir'] . '/dac-branding';

		if ( ! file_exists( $attach_dir ) ) {
			wp_mkdir_p( $attach_dir );
		}

		// Favicon upload.
		if ( ! empty( $_FILES['dac_wl_favicon']['tmp_name'] ) ) {
			$favicon_id = $this->handle_image_upload( 'dac_wl_favicon', $attach_dir );
			if ( $favicon_id ) {
				$settings['favicon_id'] = $favicon_id;
			}
		}
		if ( ! empty( $raw['remove_favicon'] ) ) {
			unset( $settings['favicon_id'] );
		}

		// Text fields.
		$text_fields = [ 'footer_text', 'howdy_text' ];
		foreach ( $text_fields as $field ) {
			if ( isset( $raw[ $field ] ) ) {
				$settings[ $field ] = sanitize_text_field( wp_unslash( $raw[ $field ] ) );
			}
		}

		// Footer text allows HTML.
		if ( isset( $raw['footer_text'] ) ) {
			$settings['footer_text'] = wp_kses_post( wp_unslash( $raw['footer_text'] ) );
		}

		// Checkbox fields.
		$settings['hide_version'] = ! empty( $raw['hide_version'] );

		$this->options->update( Constants::OPT_WHITE_LABEL, $settings );

		wp_safe_redirect( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=' . self::id() . '&saved=1' ) );
		exit;
	}

	/**
	 * Handle a single image upload and return the attachment ID.
	 *
	 * @param string $field_key $_FILES key.
	 * @param string $upload_dir Target directory.
	 * @return int Attachment ID or 0.
	 */
	private function handle_image_upload( string $field_key, string $upload_dir ): int {
		if ( empty( $_FILES[ $field_key ]['tmp_name'] ) ) {
			return 0;
		}

		$file = $_FILES[ $field_key ];

		// Basic validation.
		$allowed_types = [ 'image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/x-icon' ];
		$file_type     = wp_check_filetype( $file['name'] );

		if ( ! in_array( $file['type'], $allowed_types, true ) && ! in_array( $file_type['type'], $allowed_types, true ) ) {
			return 0;
		}

		// Use WordPress media handle.
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
