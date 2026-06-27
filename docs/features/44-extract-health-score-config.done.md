# Extract Health Score Thresholds to Config

**Date:** 2026-03-16
**Status:** Pending
**Priority:** MEDIUM
**Area:** Code
**Found by:** /audit all

---

## Problem

Health score thresholds (70 for "healthy", 50 for "at risk") are hardcoded in multiple files instead of being config-driven. A `low_health_threshold` config value exists in `config/saas.php` but only covers the 50 threshold, and isn't consistently used:

```php
// Tenant.php — hardcoded
return match (true) {
    $this->health_score >= 70 => 'success',
    $this->health_score >= 50 => 'warning',
    default => 'danger',
};

// TenantResource.php — hardcoded in filters
'healthy' => $query->where('health_score', '>=', 70),
'at_risk' => $query->where('health_score', '>=', 50)->where('health_score', '<', 70),
'critical' => $query->where('health_score', '<', 50),
```

## Impact

- Magic numbers scattered across multiple files
- Changing a threshold requires finding and updating every hardcoded instance
- Risk of inconsistency if one location is updated but others are missed
- Existing config value (`low_health_threshold`) is incomplete and underused

## Files Affected

| File | Issue |
|------|-------|
| `app/Models/Tenant.php` | Lines 428-429, 441-442: Hardcoded 70 and 50 thresholds |
| `app/Filament/SuperAdmin/Resources/TenantResource.php` | Lines 95-97, 130-140: Hardcoded thresholds in columns and filters |
| `config/saas.php` | Only `low_health_threshold` (50) defined, missing high threshold (70) |

## Proposed Solution

1. Add both thresholds to `config/saas.php`:
   ```php
   'health_score' => [
       'healthy_threshold' => 70,
       'at_risk_threshold' => 50,
   ],
   ```

2. Replace all hardcoded values with `config('saas.health_score.healthy_threshold')` and `config('saas.health_score.at_risk_threshold')`.

3. Remove the old `low_health_threshold` key (or alias it for backwards compatibility if used elsewhere).

## Acceptance Criteria

- [ ] Both thresholds (70, 50) defined in `config/saas.php`
- [ ] All hardcoded instances replaced with `config()` calls
- [ ] Old `low_health_threshold` config key removed or aliased
- [ ] Health score display and filters work identically
- [ ] `composer test` passes
- [ ] `composer analyse` passes
