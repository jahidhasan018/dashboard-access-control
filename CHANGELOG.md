# Changelog

All notable changes to Dashboard Access Control will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-08-31

### Added
- Role Manager with multi-role selection, clone, and reset
- Admin Menu Control with hierarchical accordion UI, toggle switches, search, bulk actions
- Dashboard Widget Control per role
- Admin Bar Control per role (frontend + backend)
- White Label: custom admin branding, login page, footer text, Howdy replacement, favicon
- Content Restrictions: hide meta boxes, disable Screen Options, disable Help tab, suppress notices, hide At a Glance, disable file editor
- Custom Code: per-role CSS/JS injection via hidden CPT
- Tools: JSON export/import with merge, backup before import, reset to defaults, uninstall toggle
- 4-layer enforcement model (visual, capability, route guard, AJAX/REST/XML-RPC guard)
- Conflict resolution (least privilege / most permissive)
- Admin exclusion toggle
- Modern CSS with variables, accordion/toggle/badge styles

### Security
- All option reads/writes through `Support/Options.php`
- All constants through `Core/Constants.php`
- Nonce verification on all form submissions
- Capability checks on all admin actions
- `wp_strip_all_tags` defense-in-depth for custom CSS
- XML-RPC guard per role
