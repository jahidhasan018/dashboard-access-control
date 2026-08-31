# Dashboard Access Control (DAC)

A role-based access control and white-label plugin for WordPress. Control what each role can see and do in wp-admin — truly, not just visually.

## Requirements

- WordPress 6.0+
- PHP 8.0+ (PHP 7.4 minimum supported)
- MySQL 5.7+ or MariaDB 10.3+

## Installation

1. Upload the `dashboard-access-control` folder to `wp-content/plugins/`
2. Run `composer install` in the plugin root
3. Activate the plugin through the WordPress Plugins screen
4. Navigate to **Settings → Dashboard Access Control** to configure

## Local Development

```bash
cd wp-content/plugins/dashboard-access-control
composer install
```

## Contributing

- Follow WordPress Coding Standards (WPCS)
- PHPStan level 5+ required
- All new "hide/restrict" features must implement 4 enforcement layers
