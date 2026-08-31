=== Dashboard Access Control ===
Contributors: jahidhasan018
Tags: access-control, roles, white-label, admin, dashboard
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Role-based access control and white-label plugin for WordPress. Control what each role can see and do in wp-admin.

== Description ==

Dashboard Access Control (DAC) gives WordPress administrators fine-grained control over what each user role can see and do in wp-admin.

**Features:**

* **Role Manager** — Select roles to manage, clone roles, reset profiles
* **Admin Menu Control** — Hide entire admin menus per role with accordion UI
* **Dashboard Widget Control** — Show/hide dashboard widgets per role
* **Admin Bar Control** — Hide admin bar nodes per role
* **White Label** — Custom branding, login page, footer text, Howdy replacement
* **Content Restrictions** — Hide meta boxes, disable Screen Options, disable Help tab
* **Custom Code** — Inject per-role CSS/JS in wp-admin
* **Tools** — Export/import settings, reset to defaults, uninstall data toggle

**Enforcement Model (4 Layers):**

1. **Visual Hide** — Menus/widgets removed from DOM
2. **Capability Revocation** — Underlying capabilities stripped
3. **Route Guard** — Direct URL access blocked
4. **AJAX/REST/XML-RPC Guard** — Non-menu entry points blocked

== Installation ==

1. Upload the `dashboard-access-control` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Go to Settings → Access Control to configure

== Frequently Asked Questions ==

= Does this affect non-admin users only? =

Yes. Administrators are excluded by default. You can change this in General settings.

= Can I export my settings? =

Yes. Use the Tools tab to export/import settings as JSON.

= Is custom JavaScript safe? =

Custom JS is output as-is. Only grant this capability to roles you fully trust.

== Screenshots ==

1. Role Manager tab
2. Menu Control with accordion UI
3. White Label settings
4. Custom Code editor
5. Tools — Export/Import

== Changelog ==

= 1.0.0 =
* Initial release
* Role Manager with multi-role selection
* Admin Menu Control with accordion UI
* Dashboard Widget Control
* Admin Bar Control
* White Label (branding, login page, footer, Howdy, favicon)
* Content Restrictions (meta boxes, Screen Options, Help tab, notices, file editor)
* Custom Code (CSS/JS injection per role)
* Tools (export/import, reset, uninstall toggle)
* 4-layer enforcement model
