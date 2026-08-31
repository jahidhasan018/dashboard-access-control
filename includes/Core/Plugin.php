<?php
declare(strict_types=1);

namespace DashboardAccessControl\Core;

use DashboardAccessControl\Admin\SettingsPage;
use DashboardAccessControl\Admin\Assets;
use DashboardAccessControl\Admin\Tabs\RoleManagerTab;
use DashboardAccessControl\Admin\Tabs\MenuControlTab;
use DashboardAccessControl\RoleAccess\RoleProfileRepository;
use DashboardAccessControl\RoleAccess\RoleResolver;
use DashboardAccessControl\RoleAccess\ConflictResolver;
use DashboardAccessControl\RoleAccess\ExclusionGuard;
use DashboardAccessControl\Enforcement\MenuEnforcer;
use DashboardAccessControl\Enforcement\CapabilityEnforcer;
use DashboardAccessControl\Enforcement\RouteGuard;
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
			RoleProfileRepository::class,
			function ( Container $c ): RoleProfileRepository {
				return new RoleProfileRepository( $c->get( Options::class ) );
			}
		);

		$this->container->set(
			ConflictResolver::class,
			function ( Container $c ): ConflictResolver {
				return new ConflictResolver( $c->get( Options::class ) );
			}
		);

		$this->container->set(
			RoleResolver::class,
			function ( Container $c ): RoleResolver {
				return new RoleResolver(
					$c->get( RoleProfileRepository::class ),
					$c->get( ConflictResolver::class )
				);
			}
		);

		$this->container->set(
			ExclusionGuard::class,
			function ( Container $c ): ExclusionGuard {
				return new ExclusionGuard(
					$c->get( RoleProfileRepository::class ),
					$c->get( Options::class )
				);
			}
		);

		$this->container->set(
			RoleManagerTab::class,
			function ( Container $c ): RoleManagerTab {
				return new RoleManagerTab( $c->get( RoleProfileRepository::class ) );
			}
		);

		$this->container->set(
			MenuControlTab::class,
			function ( Container $c ): MenuControlTab {
				return new MenuControlTab( $c->get( RoleProfileRepository::class ) );
			}
		);

		$this->container->set(
			MenuEnforcer::class,
			function ( Container $c ): MenuEnforcer {
				return new MenuEnforcer( $c->get( RoleResolver::class ) );
			}
		);

		$this->container->set(
			CapabilityEnforcer::class,
			function ( Container $c ): CapabilityEnforcer {
				return new CapabilityEnforcer( $c->get( RoleResolver::class ) );
			}
		);

		$this->container->set(
			RouteGuard::class,
			function ( Container $c ): RouteGuard {
				return new RouteGuard( $c->get( RoleResolver::class ) );
			}
		);

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

		// Settings page (handles its own tabs via filter).
		$settings_page = $this->container->get( SettingsPage::class );
		$settings_page->init();

		// Admin assets.
		$assets = $this->container->get( Assets::class );
		$assets->init();

		// Menu control tab: capture menu + handle saves.
		$menu_tab = $this->container->get( MenuControlTab::class );
		add_action( 'admin_menu', [ $menu_tab, 'capture_menu' ], 9999 );
		add_action( 'admin_init', [ $menu_tab, 'handle_save' ] );

		// Role manager tab: handle saves.
		$role_tab = $this->container->get( RoleManagerTab::class );
		add_action( 'admin_init', [ $role_tab, 'handle_save' ] );
		add_action( 'admin_init', [ $role_tab, 'handle_reset' ] );

		// Enforcement layers.
		$menu_enforcer = $this->container->get( MenuEnforcer::class );
		$menu_enforcer->init();

		$cap_enforcer = $this->container->get( CapabilityEnforcer::class );
		$cap_enforcer->init();

		$route_guard = $this->container->get( RouteGuard::class );
		$route_guard->init();
	}

	/**
	 * Retrieve the service container.
	 */
	public function container(): Container {
		return $this->container;
	}
}
