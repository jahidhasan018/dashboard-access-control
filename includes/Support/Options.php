<?php
declare(strict_types=1);

namespace DashboardAccessControl\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin wrapper over get_option / update_option with in-memory caching.
 */
final class Options {

	/** @var array<string, mixed> */
	private array $cache = [];

	/**
	 * Retrieve an option, using cache if available.
	 *
	 * @param string $key     Option name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get( string $key, mixed $default = false ): mixed {
		if ( array_key_exists( $key, $this->cache ) ) {
			return $this->cache[ $key ];
		}

		$value = get_option( $key, $default );
		$this->cache[ $key ] = $value;

		return $value;
	}

	/**
	 * Update an option and refresh cache.
	 *
	 * @param string $key   Option name.
	 * @param mixed  $value Value to store.
	 * @return bool
	 */
	public function update( string $key, mixed $value ): bool {
		$result = update_option( $key, $value );
		if ( $result ) {
			$this->cache[ $key ] = $value;
		}
		return $result;
	}

	/**
	 * Delete an option and clear cache.
	 *
	 * @param string $key Option name.
	 * @return bool
	 */
	public function delete( string $key ): bool {
		$result = delete_option( $key );
		if ( $result ) {
			unset( $this->cache[ $key ] );
		}
		return $result;
	}

	/**
	 * Check if an option exists.
	 */
	public function exists( string $key ): bool {
		return false !== get_option( $key );
	}
}
