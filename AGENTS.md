# Dashboard Access Control (DAC) — Agent Entry Point

## Project Summary

A role-based access control and white-label WordPress plugin. Admins select roles, then configure what those roles can see/do in wp-admin. **Hiding = revoking** — if a menu is hidden, the underlying capability, route, AJAX, and REST access must also be blocked.

## Standing Rules

1. Never touch WordPress core files. Only hooks/filters.
2. Every new "hide/restrict" feature must implement all 4 enforcement layers from `docs/ENFORCEMENT.md`, or explicitly comment in the code why a layer is N/A.
3. Every option read/write goes through `Support/Options.php` — never raw `get_option()`/`update_option()` scattered in feature classes.
4. No inline styles/scripts in PHP-rendered HTML beyond unavoidable tiny dynamic values (nonces, IDs) — everything else lives in `assets/`.
5. Stop and ask before introducing any new external dependency not already listed in `docs/ARCHITECTURE.md`.
6. Work one phase at a time, in order. Do not start a phase until the previous one's checkbox is ticked in `docs/PROGRESS.md`.
7. At the end of every session: update `docs/PROGRESS.md` (current phase, current task, one-line session log entry, any known issues) before stopping — even if the phase isn't finished.
8. Before marking a phase's checkbox complete, self-verify against that phase file's DoD and against the relevant parts of the manual QA matrix in `docs/SECURITY.md`/testing docs.

## Reference Files

| Topic | Read |
|-------|------|
| Architecture & folder structure | `docs/ARCHITECTURE.md` |
| Enforcement model (4 layers) | `docs/ENFORCEMENT.md` |
| Security & validation rules | `docs/SECURITY.md` |
| UI/UX guidelines | `docs/UI-GUIDELINES.md` |
| Extensibility hooks | `docs/HOOKS-REFERENCE.md` |
| Feature list (v1 vs v2) | `docs/FEATURES.md` |
| Current progress & tasks | `docs/PROGRESS.md` |
| Current phase tasks | `docs/phases/phase-N-*.md` (only read the current phase) |
