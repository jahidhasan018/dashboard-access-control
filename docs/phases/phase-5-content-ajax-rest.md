# Phase 5 — Content Restrictions + AJAX/REST Guards

## Tasks

1. `Admin/Tabs/ContentRestrictionsTab.php`:
   - Hide meta boxes per role
   - Disable screen options tab per role
   - Disable help tab per role
   - Suppress admin notices per role (with whitelist for critical security notices)
   - Hide "At a Glance" widget
   - Disable file editor

2. Content restriction enforcers:
   - Meta boxes: `get_user_option_meta_boxes-{screen}` filter
   - Screen options: `screen_options_show_screen` filter
   - Help tab: `contextual_help` filter
   - Admin notices: `admin_notices` / `all_admin_notices` filter
   - File editor: `map_meta_cap` to strip `edit_themes`, `edit_plugins` capabilities

3. `Enforcement/AjaxGuard.php` (Layer 4):
   - Map of `action → required feature`
   - Short-circuit via `check_ajax_referer` + feature check
   - Hook as early as possible

4. `Enforcement/RestGuard.php` (Layer 4):
   - Hook `rest_authentication_errors`
   - Reject requests to routes tied to hidden features
   - Wrap `permission_callback`s for known core routes

5. `Enforcement/XmlRpcGuard.php`:
   - XML-RPC toggle from Security tab

6. Run full manual QA matrix against every feature shipped so far

## Definition of Done

- [ ] Meta boxes hidden per role
- [ ] Screen options disabled per role
- [ ] Help tab disabled per role
- [ ] Admin notices suppressed per role (security notices still shown)
- [ ] File editor disabled per role
- [ ] AJAX calls for hidden features are blocked
- [ ] REST API calls for hidden features are blocked
- [ ] XML-RPC toggle works
- [ ] Full QA matrix passes for all features
- [ ] No PHP notices with WP_DEBUG
- [ ] PHPCS passes
