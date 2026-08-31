# Phase 1 — Plugin Scaffold

## Tasks

1. `composer init` — PSR-4 autoload mapping `DashboardAccessControl\` → `includes/`
2. Bootstrap file `dashboard-access-control.php` with:
   - Plugin header (Plugin Name, Description, Version, Author, Text Domain)
   - Constants defined early (plugin path, version, capability names, option keys)
   - Autoloader registration (Composer or custom SPL)
   - Activation/deactivation hook registration
   - Container initialization
3. `Core/Container.php` — Lightweight service container (~40 lines)
4. `Core/Constants.php` — All option keys, nonce actions, capability names as class constants
5. `Core/Activator.php`:
   - Creates default options (`dac_role_profiles`, `dac_white_label_settings`, `dac_general_settings`)
   - Registers `dac_manage_settings` capability on `administrator` role
   - Stores `dac_db_version`
6. `Core/Deactivator.php` — Cleanup on deactivation (flush rewrite rules if needed)
7. `Core/Plugin.php` — Main orchestrator, singleton via container
8. `Admin/SettingsPage.php` — Registers top-level menu under Settings, placeholder tab
9. `Admin/Assets.php` — Enqueue stub (no actual assets yet, just the conditional hook)
10. `Admin/Notices.php` — Admin notice service (success/error/dismissible)
11. `Support/Options.php` — Thin wrapper with caching
12. `Support/Sanitizer.php` — Empty class ready for callbacks
13. `Support/Capabilities.php` — Custom capability registration helper

## Definition of Done

- [ ] Plugin activates cleanly (no PHP notices/warnings)
- [ ] `WP_DEBUG` on, no errors
- [ ] Admin menu item appears for users with `dac_manage_settings` capability
- [ ] Admin menu item hidden for users without that capability
- [ ] PHPCS (WPCS) passes with zero errors
- [ ] `composer dump-autoload` works, autoloader resolves all classes
- [ ] Default options exist in `wp_options` after activation
- [ ] `dac_manage_settings` capability assigned to `administrator` role
