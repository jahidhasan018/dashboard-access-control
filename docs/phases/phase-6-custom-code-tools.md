# Phase 6 — Custom Code Tab + Tools

## Tasks

1. `CustomCode/CodeInjector.php`:
   - Admin-only capability gate (always Administrators, regardless of role settings)
   - CSS: always allowed, `wp_strip_all_tags` defense-in-depth
   - JS: behind explicit confirmation, custom cap required, inline warning
   - Store in postmeta on hidden CPT `dac_custom_code` (not options)
   - Output in `wp_admin_css_color` or `admin_head` / `admin_footer` hooks

2. `Admin/Tabs/CustomCodeTab.php`:
   - CSS editor textarea
   - JS editor textarea with confirmation checkbox
   - Warning text about XSS risks

3. `Admin/Tabs/ToolsTab.php`:
   - JSON export of `dac_role_profiles`, `dac_white_label_settings`, `dac_general_settings`
   - JSON import with validation
   - Reset to default settings
   - Uninstall data toggle (keep/remove)

4. Export/import handlers:
   - Validate imported JSON schema
   - Merge or replace strategy
   - Backup current settings before import

5. `uninstall.php`:
   - Check `WP_UNINSTALL_PLUGIN` defined
   - Check `dac_general_settings` → `delete_on_uninstall` toggle
   - Remove all options, CPT, postmeta if opted in

## Definition of Done

- [ ] Custom CSS outputs correctly per role
- [ ] Custom JS outputs correctly (with confirmation)
- [ ] Export → import round-trip is lossless
- [ ] Reset to default works
- [ ] Uninstall respects the toggle
- [ ] Custom Code tab restricted to Administrators
- [ ] No PHP notices with WP_DEBUG
- [ ] PHPCS passes
