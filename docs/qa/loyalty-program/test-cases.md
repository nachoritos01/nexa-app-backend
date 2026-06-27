# Test Cases — Loyalty Program

**Date:** 2026-03-16
**Branch:** feature/loyalty-program
**PR:** #6

---

## TC-01: Loyalty migrations run cleanly

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Fresh database |
| **Pasos** | 1. Run `migrate:fresh --seed` |
| **Resultado esperado** | 4 loyalty migrations execute: loyalty fields on customers, rewards, transactions, coupons tables |
| **Verificacion manual** | `migrate:fresh --seed` completes without errors |
| **Estado** | PASS |

## TC-02: LoyaltyReward seeder creates 9 rewards

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Fresh database with tenant |
| **Pasos** | 1. Run LoyaltyRewardSeeder |
| **Resultado esperado** | 9 rewards created: 5% discount (50pts), 10% (150pts), 1 free month (200pts), Storage (300pts), 15% (500pts Silver), Premium feature (500pts Silver), Plan upgrade (750pts Gold), 20% (1000pts Gold), 3 free months (1500pts VIP) |
| **Verificacion manual** | Filament CRUD shows all 9 rewards |
| **Estado** | PASS |

## TC-03: LoyaltyReward seeder is idempotent

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Seeder already ran once |
| **Pasos** | 1. Run LoyaltyRewardSeeder again |
| **Resultado esperado** | No duplicates, still 9 rewards (uses firstOrCreate) |
| **Verificacion manual** | Ran `migrate:fresh --seed` twice, count remains 9 |
| **Estado** | PASS |

## TC-04: LoyaltyTier enum tiers and multipliers

| Campo | Valor |
|-------|-------|
| **Prerequisito** | — |
| **Pasos** | 1. Check LoyaltyTier::fromPoints() for various point values |
| **Resultado esperado** | Bronze (0-499, x1), Silver (500-1499, x1.5), Gold (1500-4999, x2), VIP (5000+, x3) |
| **Estado** | PASS |

## TC-05: Filament LoyaltyRewardResource CRUD visible

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Admin logged in, loyalty plugin active |
| **Pasos** | 1. Navigate to /admin 2. Check sidebar |
| **Resultado esperado** | "Loyalty Rewards" link visible under Settings |
| **Verificacion manual** | Chrome DevTools — sidebar shows "Loyalty Rewards" |
| **Estado** | PASS |

## TC-06: Loyalty Rewards list shows all rewards

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Admin logged in |
| **Pasos** | 1. Navigate to /admin/loyalty-rewards |
| **Resultado esperado** | Table with 9 rows showing Name, Type, Points, Value, Min Tier, Active columns |
| **Verificacion manual** | Chrome DevTools — all 9 rewards visible with correct data |
| **Estado** | PASS |

## TC-07: Customer create form shows Loyalty section

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Admin logged in |
| **Pasos** | 1. Navigate to /admin/customers/create |
| **Resultado esperado** | Form includes collapsible "Loyalty" section |
| **Verificacion manual** | Chrome DevTools — "Loyalty" heading visible in create form |
| **Estado** | PASS |

## TC-08: Customer creation with portal access

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Admin logged in |
| **Pasos** | 1. Fill name "Juan Perez", phone "5551234567", email "juan@example.com" 2. Enable Portal toggle 3. Set password "password123" 4. Click Create |
| **Resultado esperado** | Customer created, redirected to edit page |
| **Verificacion manual** | Chrome DevTools — customer created, edit page shows all fields |
| **Estado** | PASS |

## TC-09: Plugin Marketplace shows Loyalty Program

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Admin on Pro plan |
| **Pasos** | 1. Navigate to /admin/marketplace |
| **Resultado esperado** | "Loyalty Program" card in Engagement category, badge "Included", toggle active |
| **Verificacion manual** | Chrome DevTools — Loyalty Program shows "Included" badge, toggle checked |
| **Estado** | PASS |

## TC-10: All plan-included plugins active on Pro

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Tenant on Pro plan |
| **Pasos** | 1. Visit marketplace |
| **Resultado esperado** | Payment Processing, Loyalty Program, Customer Portal, Multiple Locations, API Access, CSV Exports all "Included" and active |
| **Verificacion manual** | Chrome DevTools — all 6 included plugins verified active |
| **Estado** | PASS |

## TC-11: Paid plugins shown with price

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Pro plan tenant |
| **Pasos** | 1. Visit marketplace |
| **Resultado esperado** | Advanced Analytics ($19.99/mo) and White Label ($49.99/mo) shown with price, toggle off |
| **Verificacion manual** | Chrome DevTools — both paid plugins with correct prices |
| **Estado** | PASS |

## TC-12: Billing page shows Billing History

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Admin logged in |
| **Pasos** | 1. Navigate to /admin/billing |
| **Resultado esperado** | "Billing History" section visible with "Subscribed to Pro plan" event |
| **Verificacion manual** | Chrome DevTools — Billing History shows event with date and "$1,299/mo" |
| **Estado** | PASS |

## TC-13: Customer portal login

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Customer with portal enabled |
| **Pasos** | 1. Navigate to /my-account/login 2. Enter phone "5551234567" and password "password123" 3. Click Sign In |
| **Resultado esperado** | Redirect to /my-account/orders, sidebar shows customer name |
| **Verificacion manual** | Chrome DevTools — login successful, "Juan Perez" shown in sidebar |
| **Estado** | PASS |

## TC-14: Customer portal sidebar shows Loyalty tab

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Customer logged into portal |
| **Pasos** | 1. Check sidebar navigation |
| **Resultado esperado** | "Loyalty" link with "New" badge visible in sidebar |
| **Verificacion manual** | Chrome DevTools — "Loyalty New" link at /my-account/loyalty |
| **Estado** | PASS |

## TC-15: Loyalty tab — Tier card

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Customer with 0 points |
| **Pasos** | 1. Click on Loyalty tab |
| **Resultado esperado** | Tier card shows: Available Points "0", "Bronze", "x1 multiplier" |
| **Verificacion manual** | Chrome DevTools — tier card with Bronze, 0 pts, x1 multiplier |
| **Estado** | PASS |

## TC-16: Loyalty tab — Progress bar

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Customer with 0 points (Bronze) |
| **Pasos** | 1. Check progress section |
| **Resultado esperado** | "Progress to Silver", "0/500 pts", "500 more points to Silver" |
| **Verificacion manual** | Chrome DevTools — progress bar text verified |
| **Estado** | PASS |

## TC-17: Loyalty tab — Tier benefits

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Customer on Bronze tier |
| **Pasos** | 1. Check "Your Tier Benefits" section |
| **Resultado esperado** | "Base points on every purchase (x1)", "Access to basic rewards", "Next tier (Silver): x1.5 multiplier" |
| **Verificacion manual** | Chrome DevTools — 3 benefit items verified |
| **Estado** | PASS |

## TC-18: Loyalty tab — Available rewards (empty)

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Customer with 0 points |
| **Pasos** | 1. Check "Available Rewards" section |
| **Resultado esperado** | Message: "You need more points to redeem rewards" |
| **Verificacion manual** | Chrome DevTools — empty state message shown |
| **Estado** | PASS |

## TC-19: Loyalty tab — Locked rewards

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Customer with 0 points |
| **Pasos** | 1. Check "Locked Rewards" section |
| **Resultado esperado** | 9 locked rewards with points needed and tier requirements (Silver+, Gold+, VIP) |
| **Verificacion manual** | Chrome DevTools — all 9 rewards shown with correct pts and tier badges |
| **Estado** | PASS |

## TC-20: Loyalty tab — Points History (empty)

| Campo | Valor |
|-------|-------|
| **Prerequisito** | New customer, no orders |
| **Pasos** | 1. Check "Points History" section |
| **Resultado esperado** | "No transactions yet. Start using the platform to earn points!" |
| **Verificacion manual** | Chrome DevTools — empty state message verified |
| **Estado** | PASS |

## TC-21: Loyalty tab — My Coupons (empty)

| Campo | Valor |
|-------|-------|
| **Prerequisito** | New customer, no redemptions |
| **Pasos** | 1. Check "My Coupons" section |
| **Resultado esperado** | "No coupons yet. Redeem rewards to get coupons!" |
| **Verificacion manual** | Chrome DevTools — empty state message verified |
| **Estado** | PASS |

## TC-22: Rebase — DatabaseSeeder includes both seeders

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Rebased branch |
| **Pasos** | 1. Check DatabaseSeeder.php |
| **Resultado esperado** | Both BillingEventSeeder and LoyaltyRewardSeeder present |
| **Verificacion manual** | Code review — both seeders in call array |
| **Estado** | PASS |

## TC-23: Rebase — TenantResource preserves BillingHistoryService

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Rebased branch |
| **Pasos** | 1. Check subscribe action in TenantResource.php |
| **Resultado esperado** | `BillingHistoryService::recordSubscriptionStarted()` call preserved |
| **Verificacion manual** | Code review — billing history call present in subscribe action |
| **Estado** | PASS |

## TC-24: Rebase — PluginBillingService preserves BillingHistoryService

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Rebased branch |
| **Pasos** | 1. Check activatePlugin in PluginBillingService.php |
| **Resultado esperado** | `BillingHistoryService::recordPluginActivated()` call preserved |
| **Verificacion manual** | Code review — billing history call present after plugin activation |
| **Estado** | PASS |

## TC-25: Full test suite passes after rebase

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Rebased branch |
| **Pasos** | 1. Run `composer test` |
| **Resultado esperado** | 301 tests, 773 assertions, 0 failures |
| **Verificacion manual** | `DB_PORT=5433 composer test` — all pass |
| **Estado** | PASS |

## TC-26: PHPStan clean after rebase

| Campo | Valor |
|-------|-------|
| **Prerequisito** | Rebased branch |
| **Pasos** | 1. Run `composer analyse` |
| **Resultado esperado** | 0 errors at Level 5 |
| **Verificacion manual** | `composer analyse` — [OK] No errors |
| **Estado** | PASS |
