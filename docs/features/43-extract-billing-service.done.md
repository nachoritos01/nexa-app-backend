# Extract BillingService from Controller

**Date:** 2026-03-16
**Status:** Pending
**Priority:** MEDIUM
**Area:** Code
**Found by:** /audit all

---

## Problem

`BillingController` contains business logic in private methods `swapPlan()` and `validateDowngrade()` that should be in a service class:

```php
// BillingController.php — business logic in controller
private function swapPlan(Tenant $tenant, string $newPlan, string $priceId)
{
    $isDowngrade = PlanType::from($newPlan)->order() < PlanType::from($tenant->plan)->order();
    if ($isDowngrade) {
        $error = $this->validateDowngrade($tenant, $newPlan);
        // ...
    }
    $tenant->subscription('default')->swap($priceId);
    $tenant->update(['plan' => $newPlan]);
    $tenant->clearUsageCache();
    PlanChanged::dispatch($tenant, $previousPlan, $newPlan);
}

private function validateDowngrade(Tenant $tenant, string $newPlan): ?string
{
    $newLimits = config("saas.plans.{$newPlan}", []);
    $usage = $tenant->usageCounts();
    // Complex validation logic...
}
```

## Impact

- Business logic coupled to HTTP layer — cannot be reused in commands, jobs, or API
- Difficult to unit test swap/downgrade logic independently
- Violates single responsibility principle
- Other parts of the app (e.g., SuperAdmin manual subscription) may need the same logic

## Files Affected

| File | Issue |
|------|-------|
| `app/Http/Controllers/BillingController.php` | Lines 74-126: `swapPlan()` and `validateDowngrade()` contain business logic |

## Proposed Solution

Extract to `app/Services/BillingService.php`:

```php
class BillingService
{
    public function swapPlan(Tenant $tenant, string $newPlan, string $priceId): void
    {
        // Move logic from controller
    }

    public function validateDowngrade(Tenant $tenant, string $newPlan): ?string
    {
        // Move validation logic
    }
}
```

Update `BillingController` to inject and delegate to the service. Update any other callers (SuperAdmin actions) to use the service.

## Acceptance Criteria

- [ ] `BillingService` created with `swapPlan()` and `validateDowngrade()` methods
- [ ] `BillingController` delegates to `BillingService`
- [ ] Any other callers of similar logic updated to use the service
- [ ] Unit tests for `BillingService` methods
- [ ] `composer test` passes
- [ ] `composer analyse` passes
