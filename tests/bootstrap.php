<?php
/**
 * PHPUnit bootstrap file for Dashboard Access Control tests.
 */

// Mock WordPress functions for standalone testing.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action() {}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter() {}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action() {}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) {
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key, $single = false ) {
		return '';
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $meta_key, $meta_value ) {
		return true;
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 1;
	}
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
	function wp_get_current_user() {
		return (object) [
			'ID'    => 1,
			'roles' => [ 'administrator' ],
		];
	}
}

if ( ! function_exists( 'get_user_meta' ) ) {
	function get_user_meta( $user_id, $key, $single = false ) {
		return '';
	}
}

if ( ! function_exists( 'update_user_meta' ) ) {
	function update_user_meta( $user_id, $meta_key, $meta_value ) {
		return true;
	}
}

if ( ! function_exists( 'register_post_type' ) ) {
	function register_post_type() {}
}

if ( ! function_exists( 'unregister_post_type' ) ) {
	function unregister_post_type() {}
}

if ( ! function_exists( 'wp_insert_post' ) ) {
	function wp_insert_post( $data ) {
		return 1;
	}
}

if ( ! function_exists( 'wp_delete_post' ) ) {
	function wp_delete_post( $post_id, $force = false ) {
		return true;
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	function get_posts() {
		return [];
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return true;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		return true;
	}
}

if ( ! function_exists( 'get_role' ) ) {
	function get_role( $role ) {
		return (object) [
			'capabilities' => [],
		];
	}
}

if ( ! function_exists( 'wp_roles' ) ) {
	function wp_roles() {
		return (object) [
			'roles' => [],
		];
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'http://example.com/wp-admin/' . $path;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return $url;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0 ) {
		return json_encode( $data, $options );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return strip_tags( $str );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return stripslashes( $value );
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action ) {
		return true;
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field() {}
}

if ( ! function_exists( 'add_settings_error' ) ) {
	function add_settings_error() {}
}

if ( ! function_exists( 'flush_rewrite_rules' ) ) {
	function flush_rewrite_rules() {}
}

if ( ! function_exists( 'nocache_headers' ) ) {
	function nocache_headers() {}
}

if ( ! function_exists( 'get_admin_page_title' ) ) {
	function get_admin_page_title() {
		return 'Test';
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return strip_tags( $text );
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook() {}
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook() {}
}

// Load the plugin autoloader.
$plugin_dir = dirname( __DIR__ );
if ( file_exists( $plugin_dir . '/vendor/autoload.php' ) ) {
	require_once $plugin_dir . '/vendor/autoload.php';
}
