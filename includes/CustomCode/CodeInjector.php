<?php
declare(strict_types=1);

namespace DashboardAccessControl\CustomCode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores and outputs per-role custom CSS/JS injected into admin.
 */
final class CodeInjector {

	public function __construct() {
	}

	/**
	 * Boot — register output hooks.
	 */
	public function init(): void {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_head', [ $this, 'output_css' ] );
		add_action( 'admin_footer', [ $this, 'output_js' ] );
	}

	/**
	 * Output custom CSS for the current user's roles.
	 */
	public function output_css(): void {
		$css = $this->get_allowed_code_for_user( 'css' );
		if ( '' === $css ) {
			return;
		}
		echo "\n<!-- Dashboard Access Control — Custom CSS -->\n";
		echo '<style id="dac-custom-css">' . wp_strip_all_tags( $css ) . "</style>\n";
	}

	/**
	 * Output custom JS for the current user's roles.
	 */
	public function output_js(): void {
		$js = $this->get_allowed_code_for_user( 'js' );
		if ( '' === $js ) {
			return;
		}
		echo "\n<!-- Dashboard Access Control — Custom JS -->\n";
		echo '<script id="dac-custom-js">' . wp_strip_all_tags( $js ) . "</script>\n";
	}

	/**
	 * Merge CSS/JS from all roles the current user has and return the
	 * concatenated string for the given type.
	 *
	 * @param string $type 'css' or 'js'.
	 */
	private function get_allowed_code_for_user( string $type ): string {
		$user = wp_get_current_user();
		if ( ! $user->exists() ) {
			return '';
		}

		$parts = [];
		foreach ( $user->roles as $role_slug ) {
			$meta = $this->get_meta( $role_slug );
			if ( ! empty( $meta[ $type ] ) ) {
				$parts[] = $meta[ $type ];
			}
		}

		return implode( "\n\n", $parts );
	}

	/**
	 * Retrieve custom code for a role from the hidden CPT.
	 */
	public function get_meta( string $role_id ): array {
		$post_id = $this->find_post_id( $role_id );
		if ( ! $post_id ) {
			return [];
		}
		return (array) get_post_meta( $post_id, '_dac_custom_code', true );
	}

	/**
	 * Save custom code for a role.
	 */
	public function save_meta( string $role_id, array $data ): void {
		$post_id = $this->find_post_id( $role_id );
		if ( ! $post_id ) {
			$post_id = $this->create_post( $role_id );
		}
		update_post_meta( $post_id, '_dac_custom_code', $data );
	}

	/**
	 * Delete custom code for a role.
	 */
	public function delete_meta( string $role_id ): void {
		$post_id = $this->find_post_id( $role_id );
		if ( $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Find the CPT post ID for a given role.
	 */
	private function find_post_id( string $role_id ): ?int {
		$query = new \WP_Query( [
			'post_type'      => 'dac_custom_code',
			'post_status'    => 'publish',
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'   => '_dac_role_id',
					'value' => $role_id,
				],
			],
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );
		return ! empty( $query->posts ) ? (int) $query->posts[0] : null;
	}

	/**
	 * Create a new CPT post for a role's custom code.
	 */
	private function create_post( string $role_id ): int {
		return (int) wp_insert_post( [
			'post_type'    => 'dac_custom_code',
			'post_status'  => 'publish',
			'post_title'   => 'Custom Code: ' . $role_id,
			'post_name'    => $role_id,
			'meta_input'   => [
				'_dac_role_id' => $role_id,
			],
		] );
	}

	/**
	 * Register the hidden CPT.
	 */
	public static function register_cpt(): void {
		register_post_type( 'dac_custom_code', [
			'labels'       => [
				'name'          => 'DAC Custom Code',
				'singular_name' => 'DAC Custom Code',
			],
			'public'       => false,
			'show_ui'      => false,
			'show_in_menu' => false,
			'supports'     => [],
			'map_meta_cap' => true,
		] );
	}
}
