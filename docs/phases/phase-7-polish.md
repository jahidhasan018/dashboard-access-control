# Phase 7 — Polish

## Tasks

1. **i18n pass:**
   - Generate `.pot` file from all `__()`/`_e()`/`esc_html__()` calls
   - Verify every user-facing string is wrapped
   - Add text domain to plugin header

2. **Doc-blocks pass:**
   - Every method has PHPDoc explaining *why*, not just *what*
   - Class-level doc-blocks describing purpose

3. **readme.txt:**
   - WP.org-style plugin header format
   - Description, installation, FAQ, screenshots, changelog
   - Distinct from root `README.md`

4. **CHANGELOG.md:**
   - Document all v1 features
   - Semantic versioning

5. **PHPCS/PHPStan:**
   - Clean run, zero errors
   - PHPStan level 5+

6. **PHPUnit suite:**
   - Write tests for: capability enforcement, sanitizer, conflict resolver, exclusion guard
   - All tests green

7. **Manual QA matrix:**
   - Run against every feature
   - Document results

8. **Final docs pass:**
   - Update all `docs/` files to reflect what was actually built
   - Ensure `AGENTS.md` links are correct
   - Ensure `PROGRESS.md` reflects final state

## Definition of Done

- [ ] `.pot` file generated
- [ ] All strings wrapped for i18n
- [ ] PHPDoc on every method
- [ ] `readme.txt` complete (WP.org format)
- [ ] `CHANGELOG.md` complete
- [ ] PHPCS: zero errors
- [ ] PHPStan level 5+: zero errors
- [ ] PHPUnit: all tests green
- [ ] Manual QA matrix signed off for all features
- [ ] `docs/` files reflect actual built state
- [ ] `PROGRESS.md` shows all phases complete
