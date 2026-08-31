# Phase 4 — White Label

## Tasks

1. `Admin/Tabs/WhiteLabelTab.php`:
   - Logo upload (admin logo, login logo)
   - Login background color/image
   - Admin footer text input
   - "Howdy" text replacement
   - Hide WP version number toggle
   - Custom favicon upload

2. `WhiteLabel/BrandingService.php`:
   - Admin logo: filter `admin_footer_text` / custom CSS for logo
   - Login logo: `login_headerurl`, `login_headertext`, `login_h1_title_link_url`
   - Login background: `login_body_class` + inline style
   - Admin footer: `admin_footer_text`, `update_footer`
   - "Howdy" replace: `admin_bar_menu` node title filter
   - Favicon: `admin_head`, `login_head` + `wp_head`
   - Version number: `admin_head` hide via CSS

3. `WhiteLabel/ColorSchemeService.php`:
   - Register custom `wp_admin_css_color` scheme
   - Apply per role via body class

4. All branding changes reversible on deactivation (no leftover)

## Definition of Done

- [ ] Branding changes apply per role/globally
- [ ] No touches to core files
- [ ] Reversible by deactivation
- [ ] No leftover branding after uninstall (if data deletion opted in)
- [ ] No PHP notices with WP_DEBUG
- [ ] PHPCS passes
