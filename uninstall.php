<?php
/**
 * Fired when the plugin is uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\Support\Capabilities;

$general = get_option( Constants::OPT_GENERAL, [] );
$delete   = $general[ Constants::GENERAL_DELETE_ON_UNINSTALL ] ?? false;

if ( ! $delete ) {
	return;
}

delete_option( Constants::OPT_ROLE_PROFILES );
delete_option( Constants::OPT_WHITE_LABEL );
delete_option( Constants::OPT_GENERAL );
delete_option( Constants::OPT_DB_VERSION );

Capabilities::remove_all();
