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
delete_option( Constants::OPT_PREFIX . 'last_backup' );

// Remove all custom code posts.
$custom_posts = get_posts( [
	'post_type'      => 'dac_custom_code',
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'fields'         => 'ids',
] );
foreach ( $custom_posts as $post_id ) {
	wp_delete_post( $post_id, true );
}

// Unregister CPT.
unregister_post_type( 'dac_custom_code' );

Capabilities::remove_all();
