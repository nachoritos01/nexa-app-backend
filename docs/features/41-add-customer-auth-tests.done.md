# Add CustomerAuthController Tests

**Date:** 2026-03-16
**Status:** Pending
**Priority:** MEDIUM
**Area:** Tests
**Found by:** /audit all

---

## Problem

`CustomerAuthController` has zero test coverage. This controller handles customer login and logout for the tenant-scoped customer portal. The auth flow has a known tenant-scoping vulnerability (see #37) that also needs test coverage.

## Impact

- Login/logout flows untested — regressions go undetected
- Session handling untested — potential security issues
- Cross-tenant auth vulnerability (#37) has no regression test
- No verification that auth guards work correctly

## Files Affected

| File | Issue |
|------|-------|
| `app/Http/Controllers/CustomerAuthController.php` | No feature tests for login or logout |

## Proposed Solution

Create feature tests (`tests/Feature/CustomerAuthControllerTest.php`) covering:

- Successful login with valid credentials
- Failed login with invalid credentials
- Logout clears session and redirects
- Login redirects already-authenticated customers
- Rate limiting on login attempts (if applicable)
- Cross-tenant login prevention (ties into #37 fix)

## Acceptance Criteria

- [ ] Feature tests cover `login()` success and failure paths
- [ ] Feature tests cover `logout()` with session invalidation
- [ ] Test verifies customer guard is used (not default guard)
- [ ] Test verifies cross-tenant login is prevented
- [ ] All tests pass with `composer test`
- [ ] `composer analyse` passes
