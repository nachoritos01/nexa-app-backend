# Add CustomerPortalController Tests

**Date:** 2026-03-16
**Status:** Done
**Priority:** HIGH
**Area:** Tests
**Found by:** /audit all

---

## Problem

`CustomerPortalController` has zero feature test coverage. This controller handles customer-facing portal pages including loyalty program access, reward redemption, account deletion, and payment history.

## Impact

- Loyalty page rendering with tier gates untested — broken access control could go undetected
- Reward redemption (financial transaction) untested
- Account deletion (`deleteAccount`) untested — could fail silently or delete wrong data
- Payment history rendering untested

## Files Affected

| File | Issue |
|------|-------|
| `app/Http/Controllers/CustomerPortalController.php` | No feature tests for any method |

## Proposed Solution

Create feature tests (`tests/Feature/CustomerPortalControllerTest.php`) covering:

- `loyalty()` — verify page loads, tier gate enforcement, correct data displayed
- `redeemReward()` — verify successful redemption, insufficient points, invalid reward
- `deleteAccount()` — verify account soft-deletion, session invalidation, redirect
- `payments()` — verify payment history loads with correct data
- Authentication: verify all routes require customer auth
- Tenant isolation: verify customers only see their own tenant data

## Acceptance Criteria

- [ ] Feature tests cover all controller methods (loyalty, redeemReward, deleteAccount, payments)
- [ ] Tests verify authentication is required on all routes
- [ ] Tests verify tenant isolation (customer can't access other tenant's data)
- [ ] Edge cases tested (empty states, unauthorized access)
- [ ] All tests pass with `composer test`
- [ ] `composer analyse` passes
