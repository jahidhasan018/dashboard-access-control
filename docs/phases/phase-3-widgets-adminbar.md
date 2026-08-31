# Phase 3 — Dashboard Widgets + Admin Bar

## Tasks

1. `Admin/Tabs/DashboardWidgetsTab.php`:
   - List all registered dashboard widgets (core + third-party)
   - Checkboxes per selected role to show/hide each widget
   - Support for `dac_dashboard_widget_registry` filter

2. `Enforcement/DashboardWidgetEnforcer.php` (Layer 1):
   - Hook `wp_dashboard_setup` late priority
   - Call `remove_meta_box()` for hidden widgets
   - Also filter `get_user_option_meta-box-order_dashboard` if needed

3. `Admin/Tabs/AdminBarTab.php`:
   - Options: hide entire admin bar on front-end / back-end
   - Options: remove specific toolbar nodes per role
   - Checkbox list of known toolbar nodes

4. `Enforcement/AdminBarEnforcer.php`:
   - **Front-end:** hook `wp_before_admin_bar_render` → `show_admin_bar( false )` filtered per role
   - **Back-end:** hide via CSS/body class + `_admin_bar_bump_cb` cleanup — never block dashboard route
   - Node removal: hook `admin_bar_menu` at late priority, remove specific nodes

5. Layer 4 guards for dashboard widget AJAX refresh (if applicable)

## Definition of Done

- [ ] Widgets toggle correctly per role
- [ ] Admin bar hidden on front-end for a role
- [ ] wp-admin remains fully reachable for that role (per requirement)
- [ ] Specific toolbar nodes can be removed per role
- [ ] No PHP notices with WP_DEBUG
- [ ] PHPCS passes
