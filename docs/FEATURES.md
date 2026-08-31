# Feature List — Dashboard Access Control

## v1 Features (ship these)

### Multi-Role Selector
- Available on every rule set
- Choices.js chips interface

### Admin Menu Control
- Show/hide top-level and submenu items per role
- Menu label rename
- Menu icon rename
- True capability enforcement (not just visual)

### Dashboard Widgets
- Show/hide core dashboard widgets per role
- Show/hide third-party dashboard widgets per role

### Admin Bar Control
- Hide on front-end per role
- Hide on back-end per role (dashboard still reachable)
- Remove specific toolbar nodes per role

### White Label
- Custom admin logo (replaces WP logo)
- Login page logo URL/title
- Login background color/image
- Admin footer text
- "Howdy, {name}" text replacement
- Hide WP version number
- Custom favicon

### Content Restrictions
- Hide meta boxes per role
- Disable screen options tab per role
- Disable help tab per role
- Suppress admin notices per role (whitelist critical security notices)
- Hide "At a Glance" widget
- Disable file editor (DISALLOW_FILE_EDIT equivalent)

### Security
- XML-RPC toggle
- Direct-access route guard (Layer 3)
- AJAX guards (Layer 4)
- REST API guards (Layer 4)

### Role Manager
- Clone role profile
- Reset to default
- Per-role summary view

### Import/Export
- JSON export/import of settings

### Uninstall
- Toggle: keep or remove data on uninstall

---

## v2 / Later (roadmap, not v1 scope)

- Login Page Customization (full — custom URL, redirect rules)
- "Preview as role" live simulator
- Custom admin color scheme builder
- Per-role custom dashboard welcome widget with rich content
- Multisite network-level default profiles + per-site override
- Activity log of settings changes
- REST-first admin UI (React alternative)
- Conditional rules (time-based, custom field based)
- Custom login URL / brute-force protection integration
