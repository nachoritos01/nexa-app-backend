# Add StripeWebhookController Tests

**Date:** 2026-03-16
**Status:** Pending
**Priority:** MEDIUM
**Area:** Tests
**Found by:** /audit all

---

## Problem

`StripeWebhookController` has 4 webhook handlers with zero test coverage:

- `handleCustomerSubscriptionCreated()`
- `handleCustomerSubscriptionUpdated()`
- `handleCustomerSubscriptionDeleted()`
- `handleInvoicePaymentFailed()`

These handlers modify subscription state and tenant data based on Stripe events.

## Impact

- Subscription lifecycle changes untested — billing errors go undetected
- Invoice payment failure handling untested — tenants may not be notified of failed payments
- Webhook signature verification untested
- Stripe event payload parsing untested — breaking changes in Stripe API could cause silent failures

## Files Affected

| File | Issue |
|------|-------|
| `app/Http/Controllers/StripeWebhookController.php` | All 4 handlers lack test coverage |

## Proposed Solution

Create feature tests (`tests/Feature/StripeWebhookControllerTest.php`) covering:

- Each handler with valid Stripe event payload
- Subscription created → tenant subscription record updated
- Subscription updated → plan/status synced
- Subscription deleted → tenant marked as canceled
- Invoice payment failed → appropriate notification sent
- Invalid/malformed webhook payloads rejected
- Idempotency — processing same event twice doesn't cause issues

Use Stripe's test fixtures or mock payloads matching the real event structure.

## Acceptance Criteria

- [ ] Feature tests cover all 4 webhook handlers
- [ ] Tests verify correct state changes per event type
- [ ] Tests verify invalid payloads are rejected
- [ ] Tests verify idempotent processing
- [ ] All tests pass with `composer test`
- [ ] `composer analyse` passes
