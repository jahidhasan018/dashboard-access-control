# Security & Validation — Dashboard Access Control

## Non-negotiable Rules

Apply everywhere, no exceptions:

- [ ] `if ( ! defined( 'ABSPATH' ) ) { exit; }` at top of every PHP file
- [ ] Every form submission verified with `check_admin_referer()` / `wp_verify_nonce()`
- [ ] Every settings save gated by `current_user_can( 'dac_manage_settings' )` — custom capability, not hardcoded `manage_options`
- [ ] All `$_POST`/`$_GET`/`$_REQUEST` input passed through `Sanitizer` — never used raw
- [ ] Use typed sanitizers: `sanitize_text_field`, `absint`, `sanitize_hex_color`, `esc_url_raw`, `wp_kses_post` for rich text
- [ ] Strict whitelist validator for role slugs, menu slugs, and capability names — validate against `get_editable_roles()` / registered menu list, never trust client-submitted slugs blindly
- [ ] All output escaped at point of output: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses()` for custom CSS/JS tab
- [ ] Custom JS gated behind `unfiltered_html`-equivalent custom cap with inline warning — stored XSS vector if lower-trust user gets access
- [ ] Restrict Custom Code tab to Administrators only, always, regardless of role settings
- [ ] No SQL string concatenation — use `$wpdb->prepare()` for any custom queries
- [ ] Rate/abuse consideration: settings save actions not exploitable for stored XSS via role names or menu labels — sanitize + cap length
- [ ] `uninstall.php` respects "delete data on uninstall" toggle (default: off)
- [ ] i18n: every string wrapped in `__()`/`_e()`/`esc_html__()` with plugin text domain, `.pot` file generated
- [ ] Escape/validate before storing color hex, URLs, uploaded logo attachment IDs — validate it's an image attachment the current user can access

## Self-Lockout Prevention

- Hard-block a user from locking out their own only-admin account
- Always exclude `administrator` role from full dashboard lockout unless explicit confirmation
- ExclusionGuard runs before every save

## Testing Requirements

- No PHP notices/warnings with `WP_DEBUG` on
- No console JS errors on settings page
- Works with multisite (if in scope)
- Uninstall cleanly removes data only if opted in
