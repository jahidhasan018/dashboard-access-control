# Manual QA Matrix — Dashboard Access Control 1.0.0

Test each feature on a fresh WordPress install with multiple roles (Administrator, Editor, Author, Subscriber).

## Setup

1. Install WordPress 6.7+ with sample content
2. Install DAC plugin
3. Create test users: Admin, Editor, Author, Subscriber

---

## Phase 1: Plugin Scaffold

| # | Test Case | Steps | Expected | Pass |
|---|-----------|-------|----------|------|
| 1.1 | Plugin activates | Activate plugin | No errors, Settings menu shows "Access Control" | |
| 1.2 | Plugin deactivates | Deactivate plugin | Clean deactivation | |
| 1.3 | Admin menu appears | Log in as Admin | "Access Control" link under Settings | |
| 1.4 | Settings page loads | Click "Access Control" | Page renders with tabs | |
| 1.5 | Non-admin blocked | Log in as Subscriber, access settings URL | Access denied | |

---

## Phase 2: Role Manager + Menu Control

| # | Test Case | Steps | Expected | Pass |
|---|-----------|-------|----------|------|
| 2.1 | Role Manager tab | Click Role Manager tab | Shows role profiles | |
| 2.2 | Create role profile | Select Editor role, save | Profile created | |
| 2.3 | Menu Control tab | Click Menu Control tab | Shows all admin menus | |
| 2.4 | Hide menu item | Toggle a menu off for Editor | Toggle switches to "off" | |
| 2.5 | Menu hidden for Editor | Log in as Editor | Hidden menu not visible | |
| 2.6 | Menu visible for Admin | Log in as Admin | All menus visible | |
| 2.7 | Direct URL blocked | Editor tries direct URL to hidden menu | Access denied | |
| 2.8 | Capability stripped | Editor tries wpdb capability | Capability not present | |
| 2.9 | Search filter | Type in search box | Menus filter in real-time | |
| 2.10 | Bulk action | Select multiple, toggle all | Bulk action applies | |
| 2.11 | Conflict resolution | Set least privilege, test with multi-role user | Most restrictive wins | |

---

## Phase 3: Dashboard Widgets + Admin Bar

| # | Test Case | Steps | Expected | Pass |
|---|-----------|-------|----------|------|
| 3.1 | Widget Control tab | Click Dashboard Widgets tab | Shows all widgets | |
| 3.2 | Hide widget | Toggle a widget off for Author | Toggle off | |
| 3.3 | Widget hidden | Log in as Author | Widget not on dashboard | |
| 3.4 | Admin Bar tab | Click Admin Bar tab | Shows admin bar nodes | |
| 3.5 | Hide admin bar node | Toggle a node off | Toggle off | |
| 3.6 | Node hidden | Log in as configured role | Node not in admin bar | |

---

## Phase 4: White Label

| # | Test Case | Steps | Expected | Pass |
|---|-----------|-------|----------|------|
| 4.1 | White Label tab | Click White Label tab | Shows branding options | |
| 4.2 | Custom admin title | Enter custom title | Saved | |
| 4.3 | Custom footer text | Enter custom footer | Saved | |
| 4.4 | Howdy replacement | Enter replacement text | Saved | |
| 4.5 | Login logo upload | Upload image | Saved | |
| 4.6 | Branding applied | Load admin as configured role | Custom branding visible | |
| 4.7 | Login page customized | Visit wp-login.php | Custom logo/title shown | |

---

## Phase 5: Content Restrictions + Guards

| # | Test Case | Steps | Expected | Pass |
|---|-----------|-------|----------|------|
| 5.1 | Content Restrictions tab | Click tab | Shows restriction toggles | |
| 5.2 | Hide meta boxes | Toggle "Hide Meta Boxes" for role | Saved | |
| 5.3 | Meta boxes hidden | Log in as role | Meta boxes not visible | |
| 5.4 | Disable Screen Options | Toggle on | Saved | |
| 5.5 | Screen Options hidden | Log in as role | Screen Options tab not shown | |
| 5.6 | Disable Help tab | Toggle on | Saved | |
| 5.7 | Help tab hidden | Log in as role | Help tab not shown | |
| 5.8 | Suppress notices | Toggle on | Saved | |
| 5.9 | Notices hidden | Log in as role | Admin notices hidden | |
| 5.10 | AJAX guard | Editor tries unauthorized AJAX | 403 error | |
| 5.11 | REST guard | Editor tries restricted REST route | 403 error | |
| 5.12 | XML-RPC guard | XML-RPC disabled for role | xmlrpc_enabled filter returns false | |

---

## Phase 6: Custom Code + Tools

| # | Test Case | Steps | Expected | Pass |
|---|-----------|-------|----------|------|
| 6.1 | Custom Code tab | Click Custom Code tab | Shows role tabs + editors | |
| 6.2 | Add CSS | Enter CSS, save | Saved | |
| 6.3 | CSS applied | Log in as role | Custom CSS in admin_head | |
| 6.4 | Add JS | Enter JS, save | Saved | |
| 6.5 | JS applied | Log in as role | Custom JS in admin_footer | |
| 6.6 | Security warning | View Custom Code tab | Warning visible | |
| 6.7 | Tools tab | Click Tools tab | Shows export/import/reset | |
| 6.8 | Export | Click Export Settings | JSON file downloads | |
| 6.9 | Import | Upload JSON, import | Settings imported | |
| 6.10 | Reset | Click Reset to Defaults | All settings cleared | |
| 6.11 | Uninstall toggle | Toggle delete on uninstall | Setting saved | |

---

## Phase 7: Polish

| # | Test Case | Steps | Expected | Pass |
|---|-----------|-------|----------|------|
| 7.1 | i18n strings | Search for untranslated strings | None found | |
| 7.2 | PHPDoc | Review all files | Docblocks present | |
| 7.3 | readme.txt | Review | Complete | |
| 7.4 | CHANGELOG.md | Review | Complete | |
| 7.5 | PHPCS | Run phpcs | Zero errors | |
| 7.6 | PHPUnit | Run phpunit | All tests green | |
| 7.7 | WP_DEBUG | Enable WP_DEBUG, test all pages | No PHP notices/warnings | |
