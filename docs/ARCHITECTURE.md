# Architecture — Dashboard Access Control

## 2.1 Standards

- PHP 8.1+ baseline (minimum 8.0)
- PSR-4 autoloading via Composer, PSR-12 code style, WPCS via PHPCS
- `declare(strict_types=1);` in all class files
- OOP, SOLID, dependency injection via a lightweight service container
- No procedural code in the main plugin file — bootstrap only
- Every hook registered inside a class method
- Every option key, meta key, nonce action, capability name uses `Constants.php` — no magic strings

## 2.2 Folder Structure

```
dashboard-access-control/
├── dashboard-access-control.php                 # Bootstrap only: header, constants, autoloader, activation hooks
├── composer.json
├── composer.lock
├── readme.txt                                   # WP.org style readme
├── CHANGELOG.md
├── uninstall.php                                # Cleanup (respects "keep data" setting)
├── includes/
│   ├── Core/
│   │   ├── Plugin.php                           # Main orchestrator (singleton via container)
│   │   ├── Activator.php                        # Table creation, default options, capability seeding
│   │   ├── Deactivator.php
│   │   ├── Container.php                        # Tiny service container (~40 lines)
│   │   └── Constants.php                        # Option keys, capability names, nonce actions
│   ├── Admin/
│   │   ├── SettingsPage.php                     # Registers admin menu + tab router
│   │   ├── Tabs/
│   │   │   ├── AbstractTab.php                  # Interface/contract every tab implements
│   │   │   ├── MenuControlTab.php
│   │   │   ├── DashboardWidgetsTab.php
│   │   │   ├── AdminBarTab.php
│   │   │   ├── WhiteLabelTab.php
│   │   │   ├── ContentRestrictionsTab.php
│   │   │   ├── SecurityTab.php
│   │   │   ├── RoleManagerTab.php
│   │   │   ├── CustomCodeTab.php
│   │   │   └── ToolsTab.php
│   │   ├── Assets.php                           # Enqueue admin CSS/JS, only on plugin screens
│   │   └── Notices.php                          # Admin notice service
│   ├── RoleAccess/
│   │   ├── RoleProfileRepository.php            # CRUD for per-role config
│   │   ├── RoleResolver.php                     # Given a WP_User, resolve effective merged rule set
│   │   ├── ConflictResolver.php                 # least-privilege vs most-permissive strategy
│   │   └── ExclusionGuard.php                   # Hard-block self-lockout of last admin
│   ├── Enforcement/
│   │   ├── MenuEnforcer.php                     # remove_menu_page/remove_submenu_page + admin_menu hook
│   │   ├── CapabilityEnforcer.php               # user_has_cap / map_meta_cap filters — the REAL gate
│   │   ├── RouteGuard.php                       # current_screen / admin_init — blocks direct URLs
│   │   ├── AjaxGuard.php                        # Filters registered AJAX actions
│   │   ├── RestGuard.php                        # rest_authentication_errors / permission_callback wrapping
│   │   ├── DashboardWidgetEnforcer.php
│   │   ├── AdminBarEnforcer.php
│   │   └── XmlRpcGuard.php
│   ├── WhiteLabel/
│   │   ├── BrandingService.php                  # Logo, footer text, page titles, "Howdy" replace
│   │   └── ColorSchemeService.php
│   ├── CustomCode/
│   │   └── CodeInjector.php                     # Escaped, capability-gated CSS/JS output
│   ├── Support/
│   │   ├── Sanitizer.php                        # Centralized sanitize/validate callbacks
│   │   ├── Options.php                          # Thin wrapper over get_option/update_option with caching
│   │   ├── Capabilities.php                     # Custom capability registration
│   │   └── Logger.php                           # Optional debug logger
│   └── Api/
│       └── RestController.php                   # Optional REST endpoints for settings
├── assets/
│   ├── css/
│   │   └── admin.css
│   ├── js/
│   │   └── admin.js
│   └── src/                                     # Uncompiled source
├── languages/                                   # .pot for i18n
└── tests/
    ├── Unit/
    └── Integration/                             # WP_UnitTestCase based
```

## 2.3 Data Model

Options split by concern to keep autoloaded option size small:

| Option Key | Contents | Autoload |
|-----------|----------|----------|
| `dac_role_profiles` | Array keyed by role slug: menu rules, widget rules, admin bar rules, restriction rules | yes |
| `dac_white_label_settings` | Global + per-role branding overrides | yes |
| `dac_general_settings` | Plugin-wide toggles (conflict strategy, exclude-admins flag, logging) | yes |
| `dac_db_version` | Schema version for migrations | no |
| `dac_custom_code_{role}` | Stored in postmeta on hidden CPT `dac_custom_code` (not options) | no |

Register everything through **Settings API** (`register_setting`, `sanitize_callback`) for capability checks, nonces, and `options.php` handling.

Add versioned migration logic in `Activator::maybe_migrate()` keyed off `dac_db_version`.
