<?php
declare(strict_types=1);

namespace DashboardAccessControl\Core;

use DashboardAccessControl\Admin\SettingsPage;
use DashboardAccessControl\Admin\Assets;
use DashboardAccessControl\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin orchestrator — singleton via container.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	/** @var Container */
	private Container $container;

	private function __construct() {
		$this->container = new Container();
		$this->register_services();
	}

	/**
	 * Get or create the singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}
		return self::$instance;
	}

	/**
	 * Register core services in the container.
	 */
	private function register_services(): void {
		$this->container->set( Options::class, new Options() );
		$this->container->set(
			SettingsPage::class,
			function ( Container $c ): SettingsPage {
				return new SettingsPage( $c->get( Options::class ) );
			}
		);
		$this->container->set(
			Assets::class,
			function (): Assets {
				return new Assets();
			}
		);
	}

	/**
	 * Boot the plugin — hook everything into WordPress.
	 */
	private function boot(): void {
		Activator::maybe_migrate();

		$settings_page = $this->container->get( SettingsPage::class );
		$settings_page->init();

		$assets = $this->container->get( Assets::class );
		$assets->init();
	}

	/**
	 * Retrieve the service container.
	 */
	public function container(): Container {
		return $this->container;
	}
}
