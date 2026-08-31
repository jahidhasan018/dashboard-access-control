<?php
declare(strict_types=1);

namespace DashboardAccessControl\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized sanitize and validate callbacks for Settings API.
 */
final class Sanitizer {

	// This class will be populated in Phase 2+ with sanitize callbacks
	// for each field type (text, color, URL, role slugs, menu slugs, etc.).
}
