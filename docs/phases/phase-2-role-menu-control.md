# Phase 2 — Role Manager + Menu Control

## Tasks

1. `RoleAccess/RoleProfileRepository.php`:
   - CRUD for per-role config (stored in `dac_role_profiles` option)
   - Read/write with caching via `Support/Options.php`
   - Schema validation on save

2. `Admin/Tabs/RoleManagerTab.php`:
   - Multi-role selector using Choices.js
   - Show list of all WordPress roles (from `wp_roles()`)
   - Allow selecting multiple roles per rule set

3. `Admin/Tabs/MenuControlTab.php`:
   - List all currently registered admin menu/submenu items
   - Capture `$menu`/`$submenu` globals late on `admin_menu` at priority 9999
   - Admin-only "menu snapshot" tool for mapping
   - Checkboxes per selected role to show/hide each item
   - Menu label rename input
   - Menu icon selector

4. `Enforcement/MenuEnforcer.php` (Layer 1):
   - Hook `admin_menu` at priority 999
   - Call `remove_menu_page()` / `remove_submenu_page()` based on resolved rules

5. `Enforcement/CapabilityEnforcer.php` (Layer 2):
   - Hook `user_has_cap` filter
   - Strip capabilities tied to hidden menu items
   - Maintain menu-slug → capability map

6. `Enforcement/RouteGuard.php` (Layer 3):
   - Hook `current_screen` and `admin_init`
   - Block direct URL access to hidden items
   - Configurable: wp_die() or redirect to dashboard

7. `RoleAccess/RoleResolver.php`:
   - Given a WP_User, resolve effective merged rule set
   - Handle users with multiple roles

8. `RoleAccess/ConflictResolver.php`:
   - Implement least-privilege (most restrictive wins) — default
   - Option for most-permissive wins

9. `RoleAccess/ExclusionGuard.php`:
   - Before save: check if rule would lock out last admin
   - Hard-block self-lockout

10. Wire ExclusionGuard into save handlers

## Definition of Done

- [ ] Create a test role, hide "Plugins" menu for it
- [ ] User in that role cannot see "Plugins" in admin menu
- [ ] User cannot visit `plugins.php` directly (Layer 3 blocks)
- [ ] Admin role is unaffected
- [ ] Multi-role user sees union of restrictions (least-privilege wins)
- [ ] Settings save/sanitize round-trips correctly
- [ ] ExclusionGuard prevents locking out last admin
- [ ] No PHP notices with WP_DEBUG
- [ ] PHPCS passes
