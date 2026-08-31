# Extensibility Hooks — Dashboard Access Control

> Updated as new hooks are added during development.

## Filters

| Hook | Type | Description |
|------|------|-------------|
| `dac_registered_menu_capability_map` | filter | Map custom admin menu slugs to capabilities |
| `dac_role_profile_defaults` | filter | Change default rule set for a role on first install |
| `dac_settings_tabs` | filter | Register additional tabs in the settings UI |
| `dac_conflict_resolution_strategy` | filter | Override least-privilege default strategy |
| `dac_is_user_excluded` | filter | Mark specific users as always-excluded |
| `dac_dashboard_widget_registry` | filter | Declare additional dashboard widgets as controllable |
| `dac_enforced_capabilities` | filter | Modify the capability map before enforcement |
| `dac_redirect_on_denied` | filter | Customize the redirect URL when access is denied |
| `dac_white_label_settings` | filter | Modify branding settings per context |

## Actions

| Hook | Type | Description |
|------|------|-------------|
| `dac_before_enforce_menu` | action | Fires before menu enforcement for a role |
| `dac_after_enforce_menu` | action | Fires after menu enforcement for a role |
| `dac_before_save_profile` | action | Fires before a role profile is saved |
| `dac_after_save_profile` | action | Fires after a role profile is saved |
| `dac_settings_page_loaded` | action | Fires when the settings page loads |
| `dac_enforcement_complete` | action | Fires after all enforcement layers run |

## Usage Notes

- All filters should check context (role slug, user, screen) before modifying behavior
- Use `has_filter()` / `has_action()` before expensive operations
- Document any new hooks added during development in this file
