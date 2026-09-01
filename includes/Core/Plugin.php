<?php
declare(strict_types=1);

namespace DashboardAccessControl\Core;

use DashboardAccessControl\Admin\SettingsPage;
use DashboardAccessControl\Admin\Assets;
use DashboardAccessControl\Admin\Tabs\RoleManagerTab;
use DashboardAccessControl\Admin\Tabs\MenuControlTab;
use DashboardAccessControl\Admin\Tabs\DashboardWidgetsTab;
use DashboardAccessControl\Admin\Tabs\AdminBarTab;
use DashboardAccessControl\RoleAccess\RoleProfileRepository;
use DashboardAccessControl\RoleAccess\RoleResolver;
use DashboardAccessControl\RoleAccess\ConflictResolver;
use DashboardAccessControl\RoleAccess\ExclusionGuard;
use DashboardAccessControl\Enforcement\MenuEnforcer;
use DashboardAccessControl\Enforcement\CapabilityEnforcer;
use DashboardAccessControl\Enforcement\RouteGuard;
use DashboardAccessControl\Enforcement\DashboardWidgetEnforcer;
use DashboardAccessControl\Enforcement\AdminBarEnforcer;
use DashboardAccessControl\Admin\Tabs\WhiteLabelTab;
use DashboardAccessControl\WhiteLabel\BrandingService;
use DashboardAccessControl\WhiteLabel\ColorSchemeService;
use DashboardAccessControl\Admin\Tabs\ContentRestrictionsTab;
use DashboardAccessControl\Enforcement\ContentRestrictionEnforcer;
use DashboardAccessControl\Enforcement\AjaxGuard;
use DashboardAccessControl\Enforcement\RestGuard;
use DashboardAccessControl\Enforcement\XmlRpcGuard;
use DashboardAccessControl\CustomCode\CodeInjector;
use DashboardAccessControl\Admin\Tabs\CustomCodeTab;
use DashboardAccessControl\Admin\Tabs\ToolsTab;
use DashboardAccessControl\Admin\Tabs\DashboardCustomizationTab;
use DashboardAccessControl\Enforcement\DashboardCustomizationEnforcer;
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
			DashboardWidgetsTab::class,
			function ( Container $c ): DashboardWidgetsTab {
				return new DashboardWidgetsTab( $c->get( RoleProfileRepository::class ) );
			}
		);

		$this->container->set(
			AdminBarTab::class,
			function ( Container $c ): AdminBarTab {
				return new AdminBarTab( $c->get( RoleProfileRepository::class ) );
			}
		);

		$this->container->set(
			WhiteLabelTab::class,
			function ( Container $c ): WhiteLabelTab {
				return new WhiteLabelTab( $c->get( Options::class ) );
			}
		);

		$this->container->set(
			BrandingService::class,
			function ( Container $c ): BrandingService {
				return new BrandingService(
					$c->get( Options::class ),
					$c->get( RoleResolver::class )
				);
			}
		);

		$this->container->set(
			ColorSchemeService::class,
			function ( Container $c ): ColorSchemeService {
				return new ColorSchemeService( $c->get( Options::class ) );
			}
		);

		$this->container->set(
			ContentRestrictionsTab::class,
			function ( Container $c ): ContentRestrictionsTab {
				return new ContentRestrictionsTab( $c->get( RoleProfileRepository::class ) );
			}
		);

		$this->container->set(
			ContentRestrictionEnforcer::class,
			function ( Container $c ): ContentRestrictionEnforcer {
				return new ContentRestrictionEnforcer( $c->get( RoleResolver::class ) );
			}
		);

		$this->container->set(
			AjaxGuard::class,
			function ( Container $c ): AjaxGuard {
				return new AjaxGuard( $c->get( RoleResolver::class ) );
			}
		);

		$this->container->set(
			RestGuard::class,
			function ( Container $c ): RestGuard {
				return new RestGuard( $c->get( RoleResolver::class ) );
			}
		);

		$this->container->set(
			XmlRpcGuard::class,
			function ( Container $c ): XmlRpcGuard {
				return new XmlRpcGuard( $c->get( RoleResolver::class ) );
			}
		);

		$this->container->set(
			CodeInjector::class,
			function (): CodeInjector {
				return new CodeInjector();
			}
		);

		$this->container->set(
			CustomCodeTab::class,
			function ( Container $c ): CustomCodeTab {
				return new CustomCodeTab(
					$c->get( RoleProfileRepository::class ),
					$c->get( CodeInjector::class )
				);
			}
		);

		$this->container->set(
			ToolsTab::class,
			function ( Container $c ): ToolsTab {
				return new ToolsTab( $c->get( Options::class ) );
			}
		);

		$this->container->set(
			DashboardCustomizationTab::class,
			function ( Container $c ): DashboardCustomizationTab {
				return new DashboardCustomizationTab( $c->get( RoleProfileRepository::class ) );
			}
		);

		$this->container->set(
			DashboardCustomizationEnforcer::class,
			function ( Container $c ): DashboardCustomizationEnforcer {
				return new DashboardCustomizationEnforcer( $c->get( RoleResolver::class ) );
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
			DashboardWidgetEnforcer::class,
			function ( Container $c ): DashboardWidgetEnforcer {
				return new DashboardWidgetEnforcer( $c->get( RoleResolver::class ) );
			}
		);

		$this->container->set(
			AdminBarEnforcer::class,
			function ( Container $c ): AdminBarEnforcer {
				return new AdminBarEnforcer( $c->get( RoleResolver::class ) );
			}
		);

		$this->container->set(
			SettingsPage::class,
			function ( Container $c ): SettingsPage {
				return new SettingsPage( $c->get( Options::class ), $c );
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

		// Settings page.
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

		// Dashboard widgets tab: capture widgets + handle saves.
		$widgets_tab = $this->container->get( DashboardWidgetsTab::class );
		add_action( 'wp_dashboard_setup', [ $widgets_tab, 'capture_widgets' ], 9999 );
		add_action( 'admin_init', [ $widgets_tab, 'handle_save' ] );

		// Admin bar tab: handle saves.
		$admin_bar_tab = $this->container->get( AdminBarTab::class );
		add_action( 'admin_init', [ $admin_bar_tab, 'handle_save' ] );

		// White label tab: handle saves.
		$white_label_tab = $this->container->get( WhiteLabelTab::class );
		add_action( 'admin_init', [ $white_label_tab, 'handle_save' ] );

		// Branding service: apply all white label filters.
		$branding = $this->container->get( BrandingService::class );
		$branding->init();

		// Color scheme service.
		$color_scheme = $this->container->get( ColorSchemeService::class );
		$color_scheme->init();

		// Content restrictions tab: handle saves.
		$content_tab = $this->container->get( ContentRestrictionsTab::class );
		add_action( 'admin_init', [ $content_tab, 'handle_save' ] );

		// Content restriction enforcer.
		$content_enforcer = $this->container->get( ContentRestrictionEnforcer::class );
		$content_enforcer->init();

		// AJAX guard.
		$ajax_guard = $this->container->get( AjaxGuard::class );
		$ajax_guard->init();

		// REST guard.
		$rest_guard = $this->container->get( RestGuard::class );
		$rest_guard->init();

		// XML-RPC guard.
		$xmlrpc_guard = $this->container->get( XmlRpcGuard::class );
		$xmlrpc_guard->init();

		// Custom code injector — register CPT + output hooks.
		$code_injector = $this->container->get( CodeInjector::class );
		add_action( 'init', [ CodeInjector::class, 'register_cpt' ] );
		$code_injector->init();

		// Custom code tab: handle saves.
		$custom_code_tab = $this->container->get( CustomCodeTab::class );
		add_action( 'admin_init', [ $custom_code_tab, 'handle_save' ] );

		// Tools tab: handle saves.
		$tools_tab = $this->container->get( ToolsTab::class );
		add_action( 'admin_init', [ $tools_tab, 'handle_save' ] );

		// Dashboard customization tab: handle saves.
		$dash_tab = $this->container->get( DashboardCustomizationTab::class );
		add_action( 'admin_init', [ $dash_tab, 'handle_save' ] );

		// Dashboard customization enforcer.
		$dash_enforcer = $this->container->get( DashboardCustomizationEnforcer::class );
		$dash_enforcer->init();

		// Enforcement layers.
		$menu_enforcer = $this->container->get( MenuEnforcer::class );
		$menu_enforcer->init();

		$cap_enforcer = $this->container->get( CapabilityEnforcer::class );
		$cap_enforcer->init();

		$route_guard = $this->container->get( RouteGuard::class );
		$route_guard->init();

		$widget_enforcer = $this->container->get( DashboardWidgetEnforcer::class );
		$widget_enforcer->init();

		$admin_bar_enforcer = $this->container->get( AdminBarEnforcer::class );
		$admin_bar_enforcer->init();
	}

	/**
	 * Retrieve the service container.
	 */
	public function container(): Container {
		return $this->container;
	}
}
