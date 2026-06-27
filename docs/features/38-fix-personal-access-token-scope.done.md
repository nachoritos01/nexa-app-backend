# Fix PersonalAccessToken Tenant Scope

**Date:** 2026-03-16
**Status:** Pending
**Priority:** MEDIUM
**Area:** Security
**Found by:** /audit all

---

## Problem

`PersonalAccessToken` model has a `tenant_id` column and a `tenant()` BelongsTo relationship, but does NOT use the `BelongsToTenant` trait. This means no global scope filters tokens by tenant:

```php
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $fillable = [
        'name', 'token', 'abilities', 'expires_at',
        'tenant_id', // Has tenant_id column
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    // Missing: use BelongsToTenant;
}
```

## Impact

- API token queries are not automatically filtered by tenant
- Tokens could leak across tenants when listing or validating
- Multi-tenant isolation broken at the API authentication layer

## Files Affected

| File | Issue |
|------|-------|
| `app/Models/PersonalAccessToken.php` | Missing `BelongsToTenant` trait — no global scope on tenant_id |

## Proposed Solution

Add the `BelongsToTenant` trait to the model:

```php
use App\Models\Concerns\BelongsToTenant;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use BelongsToTenant;
    // ...
}
```

Verify that `BelongsToTenant` is compatible with Sanctum's `PersonalAccessToken` base class (the trait adds a global scope on `tenant_id` which should work since the column already exists).

## Acceptance Criteria

- [ ] `PersonalAccessToken` uses `BelongsToTenant` trait
- [ ] Token queries are automatically scoped by tenant
- [ ] Sanctum token validation still works correctly
- [ ] Test verifies tokens from other tenants are not accessible
- [ ] `composer test` passes
- [ ] `composer analyse` passes
