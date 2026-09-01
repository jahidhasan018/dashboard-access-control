<?php
declare(strict_types=1);

namespace DashboardAccessControl\RoleAccess;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRUD for per-role config stored in dac_role_profiles option.
 */
final class RoleProfileRepository {

	private Options $options;

	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Get all role profiles.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_all(): array {
		$profiles = $this->options->get( Constants::OPT_ROLE_PROFILES, [] );
		return is_array( $profiles ) ? $profiles : [];
	}

	/**
	 * Get a single role profile.
	 *
	 * @param string $role_slug WordPress role slug.
	 * @return array<string, mixed>
	 */
	public function get( string $role_slug ): array {
		$profiles = $this->get_all();
		return $profiles[ $role_slug ] ?? $this->defaults();
	}

	/**
	 * Save a role profile.
	 *
	 * @param string                $role_slug WordPress role slug.
	 * @param array<string, mixed>  $profile   Profile data.
	 */
	public function save( string $role_slug, array $profile ): void {
		$profiles            = $this->get_all();
		$profiles[ $role_slug ] = $this->validate( $role_slug, $profile );
		$this->options->update( Constants::OPT_ROLE_PROFILES, $profiles );
	}

	/**
	 * Expose the Options instance for use by dependent classes (e.g. RoleResolver::is_excluded).
	 *
	 * Bug 9 fix: allows RoleResolver to read general settings through the cached
	 * Options object instead of calling get_option() directly.
	 */
	public function get_options(): Options {
		return $this->options;
	}

	/**
	 * Delete a role profile.
	 */
	public function delete( string $role_slug ): void {
		$profiles = $this->get_all();
		unset( $profiles[ $role_slug ] );
		$this->options->update( Constants::OPT_ROLE_PROFILES, $profiles );
	}

	/**
	 * Get the default profile structure for a new role.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults(): array {
		return [
			Constants::PROFILE_MENUS       => [],
			Constants::PROFILE_WIDGETS     => [],
			Constants::PROFILE_ADMIN_BAR   => [
				'hide_frontend'  => false,
				'hide_backend'   => false,
				'removed_nodes'  => [],
			],
			Constants::PROFILE_RESTRICTIONS => [
				'hide_meta_boxes'    => false,
				'disable_screen_options' => false,
				'disable_help_tab'   => false,
				'suppress_notices'   => false,
				'hide_at_a_glance'   => false,
				'disable_file_editor' => false,
			],
			Constants::PROFILE_SECURITY    => [
				'xmlrpc_enabled' => true,
			],
			Constants::PROFILE_DASHBOARD   => [],
			Constants::PROFILE_APPEARANCE  => [],
		];
	}

	/**
	 * Validate and sanitize a profile before saving.
	 *
	 * @param string               $role_slug Role slug.
	 * @param array<string, mixed> $profile   Raw profile data.
	 * @return array<string, mixed> Sanitized profile.
	 */
	private function validate( string $role_slug, array $profile ): array {
		$defaults = $this->defaults();

		$validated = wp_parse_args( $profile, $defaults );

		if ( isset( $validated[ Constants::PROFILE_MENUS ] ) && is_array( $validated[ Constants::PROFILE_MENUS ] ) ) {
			$validated[ Constants::PROFILE_MENUS ] = array_map(
				function ( $item ) {
					if ( ! is_array( $item ) ) {
						return [];
					}
					return [
						'slug'   => sanitize_text_field( $item['slug'] ?? '' ),
						'hidden' => ! empty( $item['hidden'] ),
						'label'  => sanitize_text_field( $item['label'] ?? '' ),
						'icon'   => sanitize_text_field( $item['icon'] ?? '' ),
					];
				},
				$validated[ Constants::PROFILE_MENUS ]
			);
		}

		return $validated;
	}
}
