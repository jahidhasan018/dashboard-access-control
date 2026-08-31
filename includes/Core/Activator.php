<?php
declare(strict_types=1);

namespace DashboardAccessControl\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs on plugin activation.
 */
final class Activator {

	/**
	 * Main activation handler.
	 */
	public static function activate(): void {
		self::create_default_options();
		self::register_capabilities();
		self::store_db_version();
		flush_rewrite_rules();
	}

	/**
	 * Seed default options if they don't already exist.
	 */
	private static function create_default_options(): void {
		if ( false === get_option( Constants::OPT_ROLE_PROFILES ) ) {
			update_option( Constants::OPT_ROLE_PROFILES, [] );
		}

		if ( false === get_option( Constants::OPT_WHITE_LABEL ) ) {
			update_option( Constants::OPT_WHITE_LABEL, [] );
		}

		if ( false === get_option( Constants::OPT_GENERAL ) ) {
			update_option(
				Constants::OPT_GENERAL,
				[
					Constants::GENERAL_CONFLICT_STRATEGY   => Constants::STRATEGY_LEAST_PRIVILEGE,
					Constants::GENERAL_EXCLUDE_ADMINS      => true,
					Constants::GENERAL_LOGGING             => false,
					Constants::GENERAL_DELETE_ON_UNINSTALL => false,
				]
			);
		}
	}

	/**
	 * Register the custom capability on the administrator role.
	 */
	private static function register_capabilities(): void {
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( Constants::CAP_MANAGE_SETTINGS );
		}
	}

	/**
	 * Store the current DB version for future migrations.
	 */
	private static function store_db_version(): void {
		update_option( Constants::OPT_DB_VERSION, Constants::CURRENT_DB_VERSION );
	}

	/**
	 * Run migrations if DB version is outdated.
	 */
	public static function maybe_migrate(): void {
		$installed = get_option( Constants::OPT_DB_VERSION, '0.0.0' );

		if ( version_compare( $installed, Constants::CURRENT_DB_VERSION, '>=' ) ) {
			return;
		}

		// Future migrations go here, keyed by version.
		update_option( Constants::OPT_DB_VERSION, Constants::CURRENT_DB_VERSION );
	}
}
