<?php
/**
 * Plugin Name: Dashboard Access Control
 * Plugin URI: https://github.com/jahidhasan018/dashboard-access-control
 * Description: Role-based access control and white-label plugin for WordPress. Control what each role can see and do in wp-admin.
 * Version: 1.0.0
 * Author: Jahid Hasan
 * Author URI: https://github.com/jahidhasan018
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dashboard-access-control
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DAC_VERSION', '1.0.0' );
define( 'DAC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DAC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Autoloader.
if ( file_exists( DAC_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once DAC_PLUGIN_DIR . 'vendor/autoload.php';
}

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\Core\Activator;
use DashboardAccessControl\Core\Deactivator;
use DashboardAccessControl\Core\Plugin;

register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Deactivator::class, 'deactivate' ] );

add_action( 'plugins_loaded', [ Plugin::class, 'instance' ] );
