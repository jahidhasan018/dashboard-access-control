<?php
declare(strict_types=1);

namespace DashboardAccessControl\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized constants — no magic strings anywhere in the plugin.
 */
final class Constants {

	// ── Options ────────────────────────────────────────────────────────────
	public const OPT_ROLE_PROFILES    = 'dac_role_profiles';
	public const OPT_WHITE_LABEL     = 'dac_white_label_settings';
	public const OPT_GENERAL         = 'dac_general_settings';
	public const OPT_DB_VERSION      = 'dac_db_version';
	public const OPT_LAST_BACKUP     = 'dac_last_backup';

	// ── Capabilities ───────────────────────────────────────────────────────
	public const CAP_MANAGE_SETTINGS = 'dac_manage_settings';

	// ── Nonce Actions ──────────────────────────────────────────────────────
	public const NONCE_SAVE_SETTINGS = 'dac_save_settings';
	public const NONCE_MENU_SNAPSHOT = 'dac_menu_snapshot';

	// ── Admin Menu ─────────────────────────────────────────────────────────
	public const MENU_SLUG = 'dashboard-access-control';

	// ── DB Version ─────────────────────────────────────────────────────────
	public const CURRENT_DB_VERSION = '1.0.0';

	// ── General Settings Keys ──────────────────────────────────────────────
	public const GENERAL_CONFLICT_STRATEGY = 'conflict_strategy';
	public const GENERAL_EXCLUDE_ADMINS    = 'exclude_admins';
	public const GENERAL_LOGGING           = 'logging';
	public const GENERAL_DELETE_ON_UNINSTALL = 'delete_on_uninstall';

	// ── Role Profile Keys ──────────────────────────────────────────────────
	public const PROFILE_MENUS      = 'menus';
	public const PROFILE_WIDGETS    = 'widgets';
	public const PROFILE_ADMIN_BAR  = 'admin_bar';
	public const PROFILE_RESTRICTIONS = 'restrictions';
	public const PROFILE_WHITE_LABEL = 'white_label';
	public const PROFILE_CUSTOM_CODE = 'custom_code';
	public const PROFILE_SECURITY   = 'security';

	// ── Conflict Strategies ────────────────────────────────────────────────
	public const STRATEGY_LEAST_PRIVILEGE  = 'least_privilege';
	public const STRATEGY_MOST_PERMISSIVE  = 'most_permissive';

	// ── Dashboard Customization Keys ─────────────────────────────────────
	public const PROFILE_DASHBOARD = 'dashboard';
	public const DASH_REMOVE_SCREEN_OPTIONS = 'remove_screen_options';
	public const DASH_REMOVE_HELP_TAB       = 'remove_help_tab';
	public const DASH_FULL_WIDTH            = 'full_width_dashboard';
	public const DASH_DISABLE_DRAGGING      = 'disable_dragging';
}
