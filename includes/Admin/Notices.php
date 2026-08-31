<?php
declare(strict_types=1);

namespace DashboardAccessControl\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin notice service — success, error, or warning, optionally dismissible.
 */
final class Notices {

	/** @var array<int, array{message: string, type: string, dismissible: bool}> */
	private static array $notices = [];

	/**
	 * Queue a success notice.
	 */
	public static function success( string $message, bool $dismissible = true ): void {
		self::add( $message, 'success', $dismissible );
	}

	/**
	 * Queue an error notice.
	 */
	public static function error( string $message, bool $dismissible = false ): void {
		self::add( $message, 'error', $dismissible );
	}

	/**
	 * Queue a warning notice.
	 */
	public static function warning( string $message, bool $dismissible = true ): void {
		self::add( $message, 'warning', $dismissible );
	}

	/**
	 * Render all queued notices, then clear them.
	 */
	public static function render_all(): void {
		if ( empty( self::$notices ) ) {
			return;
		}

		foreach ( self::$notices as $notice ) {
			$classes   = [ 'notice', 'notice-' . $notice['type'] ];
			$dismiss   = '';
			if ( $notice['dismissible'] ) {
				$classes[] = 'is-dismissible';
			}
			printf(
				'<div class="%s"><p>%s</p></div>',
				esc_attr( implode( ' ', $classes ) ),
				wp_kses_post( $notice['message'] )
			);
		}

		self::$notices = [];
	}

	/**
	 * Add a notice to the queue.
	 */
	private static function add( string $message, string $type, bool $dismissible ): void {
		self::$notices[] = [
			'message'    => $message,
			'type'       => $type,
			'dismissible' => $dismissible,
		];
	}
}
