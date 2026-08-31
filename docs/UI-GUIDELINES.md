# UI/UX Guidelines — Dashboard Access Control

## Tab System

Query-string driven router: `?page=dashboard-access-control&tab=menu-control`

Each tab implements shared interface:
- `get_id()` — unique tab ID
- `get_label()` — display name
- `render()` — HTML output
- `save()` — sanitize + persist

Tabs registered into `SettingsPage` via filterable array:
```php
apply_filters( 'dac_settings_tabs', $tabs )
```

## Libraries (Lightweight, No Heavy Frameworks)

| Library | Size | Purpose | Notes |
|---------|------|---------|-------|
| Alpine.js | ~15KB | Reactive show/hide, tab switching | Bundled locally, no CDN |
| Choices.js | ~20KB | Multi-role selector chips | No jQuery dependency |
| WP color picker | 0KB extra | Color inputs | Already in WP core |
| Sortable.js | ~10KB | Drag-drop menu reorder | No jQuery dependency |

**Do not use:** React, Vue, or any build-step-heavy framework for v1.

## CSS Approach

- Base on WP admin classes: `.wrap`, `.form-table`, `.button.button-primary`, `.notice`
- Layer thin custom `admin.css` on top for tab bar, drag-drop list, role-picker chips
- No reinventing wp-admin's design language

## Enqueue Discipline

```php
// In Assets::maybe_enqueue() hooked to admin_enqueue_scripts:
// Condition on get_current_screen()->id matching plugin settings page ONLY
// Never load plugin CSS/JS globally across wp-admin
```

## Native Elements

- Use `<input type="color">` or WP's `wp-color-picker`
- Native `<select multiple>` progressively enhanced with Choices.js
- WP's own admin notice classes for success/error/dismissible notices
