<?php
declare(strict_types=1);

namespace DashboardAccessControl\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightweight service container.
 */
final class Container {

	/** @var array<string, mixed> */
	private array $services = [];

	/** @var array<string, bool> */
	private array $frozen = [];

	/**
	 * Store a service instance.
	 *
	 * @param string $id    Service identifier.
	 * @param mixed  $value Service instance or factory closure.
	 */
	public function set( string $id, mixed $value ): void {
		$this->services[ $id ] = $value;
	}

	/**
	 * Retrieve a service, creating it via factory on first access.
	 *
	 * @param string $id Service identifier.
	 * @return mixed
	 *
	 * @throws \RuntimeException If service is not registered.
	 */
	public function get( string $id ): mixed {
		if ( ! array_key_exists( $id, $this->services ) ) {
			throw new \RuntimeException( "Service '{$id}' is not registered." );
		}

		if ( ! isset( $this->frozen[ $id ] ) && is_callable( $this->services[ $id ] ) ) {
			$this->services[ $id ] = ( $this->services[ $id ] )( $this );
			$this->frozen[ $id ]   = true;
		}

		return $this->services[ $id ];
	}

	/**
	 * Check if a service is registered.
	 */
	public function has( string $id ): bool {
		return array_key_exists( $id, $this->services );
	}
}
