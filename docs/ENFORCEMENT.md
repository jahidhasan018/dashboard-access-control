# Enforcement Model — Dashboard Access Control

## The Problem

Most "admin menu editor" plugins only call `remove_menu_page()`, which hides the link but leaves the underlying page fully reachable by URL. This plugin does **layered enforcement**.

## Layer 1 — Visual Hide

`admin_menu` hook (priority 999, after all menus registered) → `remove_menu_page()` / `remove_submenu_page()` based on resolved rules for `wp_get_current_user()`.

## Layer 2 — Capability Revocation (the real gate)

Use `user_has_cap` or `map_meta_cap` filters to strip the specific capability tied to that menu item for the affected role. Maintain a **menu-slug → capability map**:

- `plugins.php` → `activate_plugins`, `install_plugins`, `edit_plugins`
- `edit.php` → `edit_posts`
- `edit-tags.php` → `manage_categories`
- etc.

Build a small admin tool that lists all currently registered menu slugs + their required caps so the site owner can map new/third-party menus.

## Layer 3 — Route Guard

Hook `current_screen` or `admin_init` for non-screen requests → if the resolved screen ID / `page` query var matches a hidden item for this user, `wp_die()` with a clean "Access Denied" message (or redirect to `admin.php` dashboard — configurable) **before** the page renders.

This is the backstop against direct URL access even if Layer 2 has a gap.

## Layer 4 — AJAX/REST Guard

- **Admin-ajax:** maintain a map of `action → required feature`, short-circuit via `check_ajax_referer` + `does_current_user_have_feature()` check at the top of `AjaxGuard`, hooked as early as possible.
- **REST:** hook `rest_authentication_errors` to reject requests to routes tied to a hidden feature, and/or wrap `permission_callback`s for known core routes.

## Layer 5 — Self-Lockout Guard

Before saving any rule set, run `ExclusionGuard`:
- If the rule would remove `manage_options`/critical caps from **every** administrator-role user → block save
- If the current user is the sole administrator and is in a targeted role → block save
- Always hard-exclude the literal `administrator` role from admin-bar-hide-on-backend and full dashboard lockout unless "I understand the risk" confirmation is submitted

---

## New Feature Checklist

Every new "hide/restrict" feature must complete:

- [ ] Feature added to relevant Tab UI with role multi-select
- [ ] Layer 1: Visual hide implemented
- [ ] Layer 2: Capability revoked via user_has_cap/map_meta_cap
- [ ] Layer 3: Route guard blocks direct URL access
- [ ] Layer 4: AJAX/REST surfaces (if any) guarded
- [ ] ExclusionGuard checked — can this lock out the last admin?
- [ ] Sanitize + validate on save
- [ ] Escaped on output
- [ ] i18n strings wrapped
- [ ] Added to manual QA matrix
- [ ] Documented in readme.txt changelog
