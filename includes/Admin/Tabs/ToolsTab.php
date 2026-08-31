<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin\Tabs;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tools tab — Export, Import, Reset, Uninstall toggle.
 */
final class ToolsTab {

	private Options $options;

	public function __construct( Options $options ) {
		$this->options = $options;
	}

	public static function id(): string {
		return 'tools';
	}

	public static function label(): string {
		return __( 'Tools', 'dashboard-access-control' );
	}

	/**
	 * Handle form submissions.
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['dac_tools_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dac_tools_nonce'] ) ), 'dac_tools_action' ) ) {
			return;
		}
		if ( ! current_user_can( Constants::CAP_MANAGE_SETTINGS ) ) {
			return;
		}

		$action = isset( $_POST['dac_tool_action'] ) ? sanitize_text_field( wp_unslash( $_POST['dac_tool_action'] ) ) : '';

		switch ( $action ) {
			case 'export':
				$this->handle_export();
				return;

			case 'import':
				$this->handle_import();
				return;

			case 'reset':
				$this->handle_reset();
				return;

			case 'restore_backup':
				$this->handle_restore_backup();
				return;

			case 'toggle_uninstall':
				$this->handle_toggle_uninstall();
				return;
		}
	}

	/**
	 * JSON export — stream as download.
	 */
	private function handle_export(): void {
		$data = [
			'version'    => Constants::CURRENT_DB_VERSION,
			'exported'   => current_time( 'mysql', true ),
			'profiles'   => get_option( Constants::OPT_ROLE_PROFILES, [] ),
			'white_label'=> get_option( Constants::OPT_WHITE_LABEL, [] ),
			'general'    => get_option( Constants::OPT_GENERAL, [] ),
		];

		$filename = 'dac-export-' . gmdate( 'Y-m-d-His' ) . '.json';
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Import from uploaded JSON.
	 */
	private function handle_import(): void {
		if ( empty( $_FILES['dac_import_file']['tmp_name'] ) ) {
			add_settings_error( 'dac_notices', 'import_no_file', __( 'Please select a file to import.', 'dashboard-access-control' ), 'error' );
			return;
		}

		$raw = file_get_contents( $_FILES['dac_import_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw ) {
			add_settings_error( 'dac_notices', 'import_read_error', __( 'Could not read the uploaded file.', 'dashboard-access-control' ), 'error' );
			return;
		}

		$data = json_decode( $raw, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			add_settings_error( 'dac_notices', 'import_invalid_json', __( 'Invalid JSON file.', 'dashboard-access-control' ), 'error' );
			return;
		}

		// Schema validation.
		if ( ! isset( $data['version'] ) || ! is_array( $data['profiles'] ?? null ) ) {
			add_settings_error( 'dac_notices', 'import_bad_schema', __( 'Invalid DAC export file structure.', 'dashboard-access-control' ), 'error' );
			return;
		}

		// Backup current settings.
		$this->backup_current();

		// Merge profiles.
		$existing = get_option( Constants::OPT_ROLE_PROFILES, [] );
		$imported = $data['profiles'] ?? [];
		$merged   = array_merge( $existing, $imported );
		update_option( Constants::OPT_ROLE_PROFILES, $merged );

		// Merge white label.
		if ( ! empty( $data['white_label'] ) && is_array( $data['white_label'] ) ) {
			$wl_existing = get_option( Constants::OPT_WHITE_LABEL, [] );
			$wl_merged   = array_merge( $wl_existing, $data['white_label'] );
			update_option( Constants::OPT_WHITE_LABEL, $wl_merged );
		}

		// Replace general settings.
		if ( ! empty( $data['general'] ) && is_array( $data['general'] ) ) {
			update_option( Constants::OPT_GENERAL, $data['general'] );
		}

		add_settings_error( 'dac_notices', 'import_done', __( 'Settings imported successfully. A backup of your previous settings was saved.', 'dashboard-access-control' ), 'updated' );
	}

	/**
	 * Backup current settings to a transient (24h) as safety net.
	 */
	private function backup_current(): void {
		$backup = [
			'profiles'    => get_option( Constants::OPT_ROLE_PROFILES, [] ),
			'white_label' => get_option( Constants::OPT_WHITE_LABEL, [] ),
			'general'     => get_option( Constants::OPT_GENERAL, [] ),
		];
		set_option( Constants::OPT_PREFIX . 'last_backup', $backup );
	}

	/**
	 * Reset all settings to defaults.
	 */
	private function handle_reset(): void {
		update_option( Constants::OPT_ROLE_PROFILES, [] );
		update_option( Constants::OPT_WHITE_LABEL, [] );
		update_option(
			Constants::OPT_GENERAL,
			[
				Constants::GENERAL_CONFLICT_STRATEGY   => Constants::STRATEGY_LEAST_PRIVILEGE,
				Constants::GENERAL_EXCLUDE_ADMINS      => true,
				Constants::GENERAL_LOGGING             => false,
				Constants::GENERAL_DELETE_ON_UNINSTALL => false,
			]
		);

		add_settings_error( 'dac_notices', 'reset_done', __( 'All settings have been reset to defaults.', 'dashboard-access-control' ), 'updated' );
	}

	/**
	 * Restore the last backup.
	 */
	private function handle_restore_backup(): void {
		$backup = get_option( Constants::OPT_PREFIX . 'last_backup', null );
		if ( ! $backup || ! is_array( $backup ) ) {
			add_settings_error( 'dac_notices', 'no_backup', __( 'No backup found.', 'dashboard-access-control' ), 'error' );
			return;
		}

		if ( isset( $backup['profiles'] ) ) {
			update_option( Constants::OPT_ROLE_PROFILES, $backup['profiles'] );
		}
		if ( isset( $backup['white_label'] ) ) {
			update_option( Constants::OPT_WHITE_LABEL, $backup['white_label'] );
		}
		if ( isset( $backup['general'] ) ) {
			update_option( Constants::OPT_GENERAL, $backup['general'] );
		}

		add_settings_error( 'dac_notices', 'backup_restored', __( 'Settings restored from backup.', 'dashboard-access-control' ), 'updated' );
	}

	/**
	 * Toggle the delete-on-uninstall setting.
	 */
	private function handle_toggle_uninstall(): void {
		$general   = get_option( Constants::OPT_GENERAL, [] );
		$current   = $general[ Constants::GENERAL_DELETE_ON_UNINSTALL ] ?? false;
		$general[ Constants::GENERAL_DELETE_ON_UNINSTALL ] = ! $current;
		update_option( Constants::OPT_GENERAL, $general );

		$label = $general[ Constants::GENERAL_DELETE_ON_UNINSTALL ]
			? __( 'Data will be removed on uninstall.', 'dashboard-access-control' )
			: __( 'Data will be kept on uninstall.', 'dashboard-access-control' );

		add_settings_error( 'dac_notices', 'toggle_uninstall', $label, 'updated' );
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		$general         = get_option( Constants::OPT_GENERAL, [] );
		$delete_on_unist = $general[ Constants::GENERAL_DELETE_ON_UNINSTALL ] ?? false;
		$backup          = get_option( Constants::OPT_PREFIX . 'last_backup', null );
		?>
		<div class="dac-section">
			<h2 class="dac-section-title"><?php esc_html_e( 'Export Settings', 'dashboard-access-control' ); ?></h2>
			<p class="dac-section-desc">
				<?php esc_html_e( 'Download all role profiles, white label settings, and general settings as a JSON file.', 'dashboard-access-control' ); ?>
			</p>
			<form method="post">
				<?php wp_nonce_field( 'dac_tools_action', 'dac_tools_nonce' ); ?>
				<input type="hidden" name="dac_tool_action" value="export">
				<p class="submit">
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Export Settings', 'dashboard-access-control' ); ?>">
				</p>
			</form>
		</div>

		<div class="dac-section">
			<h2 class="dac-section-title"><?php esc_html_e( 'Import Settings', 'dashboard-access-control' ); ?></h2>
			<p class="dac-section-desc">
				<?php esc_html_e( 'Import settings from a previously exported JSON file. Existing settings will be merged.', 'dashboard-access-control' ); ?>
			</p>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'dac_tools_action', 'dac_tools_nonce' ); ?>
				<input type="hidden" name="dac_tool_action" value="import">
				<table class="form-table">
					<tr>
						<th scope="row"><label for="dac_import_file"><?php esc_html_e( 'Export File', 'dashboard-access-control' ); ?></label></th>
						<td><input type="file" id="dac_import_file" name="dac_import_file" accept=".json"></td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" class="button button-secondary" value="<?php esc_attr_e( 'Import Settings', 'dashboard-access-control' ); ?>">
				</p>
			</form>
		</div>

		<?php if ( $backup ) : ?>
		<div class="dac-section">
			<h2 class="dac-section-title"><?php esc_html_e( 'Restore Backup', 'dashboard-access-control' ); ?></h2>
			<p class="dac-section-desc">
				<?php esc_html_e( 'A backup was saved before your last import. You can restore it below.', 'dashboard-access-control' ); ?>
			</p>
			<form method="post">
				<?php wp_nonce_field( 'dac_tools_action', 'dac_tools_nonce' ); ?>
				<input type="hidden" name="dac_tool_action" value="restore_backup">
				<p class="submit">
					<input type="submit" class="button button-secondary" value="<?php esc_attr_e( 'Restore Backup', 'dashboard-access-control' ); ?>">
				</p>
			</form>
		</div>
		<?php endif; ?>

		<div class="dac-section">
			<h2 class="dac-section-title"><?php esc_html_e( 'Reset All Settings', 'dashboard-access-control' ); ?></h2>
			<p class="dac-section-desc">
				<?php esc_html_e( 'Remove all role profiles, white label settings, and custom code. This cannot be undone.', 'dashboard-access-control' ); ?>
			</p>
			<form method="post">
				<?php wp_nonce_field( 'dac_tools_action', 'dac_tools_nonce' ); ?>
				<input type="hidden" name="dac_tool_action" value="reset">
				<p class="submit">
					<input type="submit" class="button button-secondary dac-confirm" data-message="<?php esc_attr_e( 'Are you sure? This will reset all DAC settings.', 'dashboard-access-control' ); ?>" value="<?php esc_attr_e( 'Reset to Defaults', 'dashboard-access-control' ); ?>">
				</p>
			</form>
		</div>

		<div class="dac-section">
			<h2 class="dac-section-title"><?php esc_html_e( 'Uninstall Behavior', 'dashboard-access-control' ); ?></h2>
			<p class="dac-section-desc">
				<?php esc_html_e( 'Choose whether DAC data is removed when the plugin is deleted from WordPress.', 'dashboard-access-control' ); ?>
			</p>
			<form method="post">
				<?php wp_nonce_field( 'dac_tools_action', 'dac_tools_nonce' ); ?>
				<input type="hidden" name="dac_tool_action" value="toggle_uninstall">
				<p class="submit">
					<input type="submit" class="button button-secondary" value="<?php
						echo $delete_on_unist
							? esc_attr__( 'Keep Data on Uninstall', 'dashboard-access-control' )
							: esc_attr__( 'Delete Data on Uninstall', 'dashboard-access-control' );
					?>">
					<span class="description" style="margin-left:8px;">
						<?php
						echo $delete_on_unist
							? esc_html__( 'Currently: Data WILL be deleted.', 'dashboard-access-control' )
							: esc_html__( 'Currently: Data will be kept.', 'dashboard-access-control' );
						?>
					</span>
				</p>
			</form>
		</div>
		<?php
	}
}
