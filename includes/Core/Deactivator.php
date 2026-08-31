<?php
declare(strict_types=1);

namespace DashboardAccessControl\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs on plugin deactivation.
 */
final class Deactivator {

	/**
	 * Main deactivation handler.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
