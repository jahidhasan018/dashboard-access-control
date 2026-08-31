<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin\Tabs;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\CustomCode\CodeInjector;
use DashboardAccessControl\RoleAccess\RoleProfileRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Code tab — CSS/JS editor per role.
 */
final class CustomCodeTab {

	private RoleProfileRepository $repo;
	private CodeInjector $injector;

	public function __construct( RoleProfileRepository $repo, CodeInjector $injector ) {
		$this->repo    = $repo;
		$this->injector = $injector;
	}

	public static function id(): string {
		return 'custom-code';
	}

	public static function label(): string {
		return __( 'Custom Code', 'dashboard-access-control' );
	}

	/**
	 * Handle save from admin_init.
	 */
	public function handle_save(): void {
		if ( ! isset( $_POST['dac_custom_code_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dac_custom_code_nonce'] ) ), 'dac_save_custom_code' ) ) {
			return;
		}
		if ( ! current_user_can( Constants::CAP_MANAGE_SETTINGS ) ) {
			return;
		}

		$role_id = isset( $_POST['dac_code_role'] ) ? sanitize_text_field( wp_unslash( $_POST['dac_code_role'] ) ) : '';
		if ( '' === $role_id ) {
			return;
		}

		$css = isset( $_POST['dac_custom_css'] ) ? wp_unslash( $_POST['dac_custom_css'] ) : '';
		$js  = isset( $_POST['dac_custom_js'] ) ? wp_unslash( $_POST['dac_custom_js'] ) : '';

		$this->injector->save_meta( $role_id, [
			'css' => $css,
			'js'  => $js,
		] );

		add_settings_error( 'dac_notices', 'custom_code_saved', __( 'Custom code saved.', 'dashboard-access-control' ), 'updated' );
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		$role_id = isset( $_GET['dac_role'] ) ? sanitize_text_field( wp_unslash( $_GET['dac_role'] ) ) : '';
		$roles   = $this->repo->get_all();
		if ( empty( $roles ) ) {
			echo '<p>' . esc_html__( 'Create a role profile first in the Role Manager tab.', 'dashboard-access-control' ) . '</p>';
			return;
		}

		if ( '' === $role_id || ! isset( $roles[ $role_id ] ) ) {
			$role_id = array_key_first( $roles );
		}

		$current = $this->injector->get_meta( $role_id );
		?>
		<div class="dac-section">
			<h2 class="dac-section-title"><?php esc_html_e( 'Custom Code', 'dashboard-access-control' ); ?></h2>
			<p class="dac-section-desc">
				<?php esc_html_e( 'Inject custom CSS or JavaScript for specific role profiles. Custom code only runs in wp-admin.', 'dashboard-access-control' ); ?>
			</p>
		</div>

		<div class="dac-section dac-warning-box">
			<strong><?php esc_html_e( 'Security Warning:', 'dashboard-access-control' ); ?></strong>
			<?php esc_html_e( 'Custom JavaScript carries XSS risk. Only grant this to roles you fully trust. Code is output as-is (no auto-escaping) so that functional code works correctly.', 'dashboard-access-control' ); ?>
		</div>

		<div class="dac-role-tabs">
			<?php foreach ( $roles as $rid => $profile ) :
				$active = ( $rid === $role_id ) ? ' active' : '';
				$url    = admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=custom-code&dac_role=' . $rid );
			?>
				<a href="<?php echo esc_url( $url ); ?>" class="dac-role-tab<?php echo esc_attr( $active ); ?>">
					<?php echo esc_html( $profile['label'] ?? $rid ); ?>
				</a>
			<?php endforeach; ?>
		</div>

		<div class="dac-section">
			<form method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=' . Constants::MENU_SLUG . '&tab=custom-code' ) ); ?>">
				<?php wp_nonce_field( 'dac_save_custom_code', 'dac_custom_code_nonce' ); ?>
				<input type="hidden" name="dac_code_role" value="<?php echo esc_attr( $role_id ); ?>">

				<div class="dac-form-row">
					<label class="dac-label" for="dac_custom_css"><?php esc_html_e( 'Custom CSS', 'dashboard-access-control' ); ?></label>
					<textarea id="dac_custom_css" name="dac_custom_css" class="large-text code" rows="12"><?php echo esc_textarea( $current['css'] ?? '' ); ?></textarea>
				</div>

				<div class="dac-form-row">
					<label class="dac-label" for="dac_custom_js"><?php esc_html_e( 'Custom JavaScript', 'dashboard-access-control' ); ?></label>
					<textarea id="dac_custom_js" name="dac_custom_js" class="large-text code" rows="12"><?php echo esc_textarea( $current['js'] ?? '' ); ?></textarea>
				</div>

				<p class="submit">
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Code', 'dashboard-access-control' ); ?>">
				</p>
			</form>
		</div>
		<?php
	}
}
