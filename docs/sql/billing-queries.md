# SQL Queries — Billing & Subscriptions

Queries for checking billing state in DBeaver/pgAdmin.

---

## Tenants and Plans

### 1. Tenants: plan and subscription status

```sql
SELECT id, name, plan, stripe_id, subscribed_at, trial_ends_at, is_active
FROM tenants
ORDER BY id;
```

### 2. Stripe customers (tenants with stripe_id)

```sql
SELECT id, name, plan, stripe_id, pm_type, pm_last_four
FROM tenants
WHERE stripe_id IS NOT NULL;
```

### 3. Specific tenant with full subscription details

```sql
SELECT t.id, t.name, t.plan, t.stripe_id, t.trial_ends_at,
       s.stripe_id AS subscription_stripe_id,
       s.stripe_price, s.stripe_status,
       s.trial_ends_at AS sub_trial_ends,
       s.ends_at AS sub_ends_at,
       s.created_at AS subscription_created,
       s.updated_at AS subscription_updated
FROM tenants t
LEFT JOIN subscriptions s ON s.billable_id = t.id
    AND s.billable_type = 'App\Models\Tenant'
WHERE t.id = :tenant_id;
```

---

## Subscriptions

### 4. All subscriptions (Cashier)

```sql
SELECT s.id, s.billable_id AS tenant_id, t.name AS tenant_name,
       s.type, s.stripe_id, s.stripe_status, s.stripe_price,
       s.trial_ends_at, s.ends_at, s.created_at
FROM subscriptions s
JOIN tenants t ON t.id = s.billable_id
ORDER BY s.id;
```

### 5. Subscription Items: swap history

```sql
SELECT si.id, si.subscription_id, si.stripe_product, si.stripe_price,
       si.quantity, si.created_at, si.updated_at
FROM subscription_items si
JOIN subscriptions s ON s.id = si.subscription_id
ORDER BY si.updated_at DESC;
```

### 6. Active vs cancelled subscriptions

```sql
SELECT
    s.stripe_status,
    s.ends_at IS NOT NULL AS has_cancellation,
    COUNT(*) AS total
FROM subscriptions s
GROUP BY s.stripe_status, s.ends_at IS NOT NULL
ORDER BY total DESC;
```

---

## Trial

### 7. Tenants with active trial (not paying)

```sql
SELECT t.id, t.name, t.plan, t.trial_ends_at,
       EXTRACT(DAY FROM t.trial_ends_at - NOW())::int AS days_remaining,
       CASE
           WHEN t.trial_ends_at - NOW() > INTERVAL '7 days' THEN 'green'
           WHEN t.trial_ends_at - NOW() > INTERVAL '3 days' THEN 'yellow'
           ELSE 'red'
       END AS urgency,
       (SELECT COUNT(*) FROM tenant_user WHERE tenant_id = t.id) AS members
FROM tenants t
WHERE t.trial_ends_at IS NOT NULL
  AND t.trial_ends_at > NOW()
  AND t.subscribed_at IS NULL
ORDER BY t.trial_ends_at;
```

### 8. Tenants with expired trial (not subscribed)

```sql
SELECT t.id, t.name, t.plan, t.trial_ends_at,
       NOW() - t.trial_ends_at AS time_expired,
       t.is_active,
       CASE
           WHEN t.trial_ends_at + INTERVAL '3 days' > NOW() THEN 'in grace period'
           ELSE 'suspended'
       END AS status
FROM tenants t
WHERE t.trial_ends_at IS NOT NULL
  AND t.trial_ends_at < NOW()
  AND t.subscribed_at IS NULL
ORDER BY t.trial_ends_at DESC;
```

### 9. Tenants with trial expiring soon (next 7 days)

```sql
SELECT id, name, plan, trial_ends_at,
       trial_ends_at - NOW() AS time_remaining
FROM tenants
WHERE trial_ends_at IS NOT NULL
  AND trial_ends_at > NOW()
  AND trial_ends_at < NOW() + INTERVAL '7 days'
ORDER BY trial_ends_at;
```

---

## Paying Tenants by Plan

### 10. Summary by plan (starter / growth / pro)

```sql
SELECT t.plan,
       COUNT(*) AS total_tenants,
       COUNT(CASE WHEN s.stripe_status = 'active' THEN 1 END) AS active,
       COUNT(CASE WHEN s.stripe_status = 'past_due' THEN 1 END) AS past_due,
       COUNT(CASE WHEN s.ends_at IS NOT NULL THEN 1 END) AS pending_cancellation
FROM tenants t
JOIN subscriptions s ON s.billable_id = t.id
    AND s.billable_type = 'App\Models\Tenant'
WHERE t.subscribed_at IS NOT NULL
GROUP BY t.plan
ORDER BY CASE t.plan WHEN 'starter' THEN 1 WHEN 'growth' THEN 2 WHEN 'pro' THEN 3 END;
```

### 11. Detail: Starter tenants

```sql
SELECT t.id, t.name, t.subscribed_at,
       s.stripe_status, s.stripe_price, s.created_at AS subscribed_since,
       (SELECT COUNT(*) FROM tenant_user WHERE tenant_id = t.id) AS users,
       (SELECT COUNT(*) FROM orders WHERE tenant_id = t.id
           AND created_at >= date_trunc('month', NOW())) AS orders_this_month
FROM tenants t
JOIN subscriptions s ON s.billable_id = t.id
    AND s.billable_type = 'App\Models\Tenant'
    AND s.stripe_status NOT IN ('canceled', 'incomplete_expired')
WHERE t.plan = 'starter'
ORDER BY t.subscribed_at;
```

### 12. Detail: Growth tenants

```sql
SELECT t.id, t.name, t.subscribed_at,
       s.stripe_status, s.stripe_price, s.created_at AS subscribed_since,
       (SELECT COUNT(*) FROM tenant_user WHERE tenant_id = t.id) AS users,
       (SELECT COUNT(*) FROM orders WHERE tenant_id = t.id
           AND created_at >= date_trunc('month', NOW())) AS orders_this_month
FROM tenants t
JOIN subscriptions s ON s.billable_id = t.id
    AND s.billable_type = 'App\Models\Tenant'
    AND s.stripe_status NOT IN ('canceled', 'incomplete_expired')
WHERE t.plan = 'growth'
ORDER BY t.subscribed_at;
```

### 13. Detail: Pro tenants

```sql
SELECT t.id, t.name, t.subscribed_at,
       s.stripe_status, s.stripe_price, s.created_at AS subscribed_since,
       (SELECT COUNT(*) FROM tenant_user WHERE tenant_id = t.id) AS users,
       (SELECT COUNT(*) FROM orders WHERE tenant_id = t.id
           AND created_at >= date_trunc('month', NOW())) AS orders_this_month
FROM tenants t
JOIN subscriptions s ON s.billable_id = t.id
    AND s.billable_type = 'App\Models\Tenant'
    AND s.stripe_status NOT IN ('canceled', 'incomplete_expired')
WHERE t.plan = 'pro'
ORDER BY t.subscribed_at;
```

### 14. Tenants without subscription (expired trial or never subscribed)

```sql
SELECT t.id, t.name, t.plan, t.trial_ends_at, t.subscribed_at
FROM tenants t
LEFT JOIN subscriptions s ON s.billable_id = t.id
    AND s.billable_type = 'App\Models\Tenant'
WHERE s.id IS NULL
  AND (t.trial_ends_at IS NULL OR t.trial_ends_at < NOW())
ORDER BY t.id;
```

### 15. Detect duplicate subscriptions (debug)

```sql
SELECT billable_id AS tenant_id, type, COUNT(*) AS total
FROM subscriptions
WHERE stripe_status NOT IN ('canceled', 'incomplete_expired')
GROUP BY billable_id, type
HAVING COUNT(*) > 1;
```

---

## Cancellations and Post-Cancellation State

### 16. Subscriptions cancelled with grace period (cancel_at_period_end)

> Tenants that cancelled but still have access until end of paid period.

```sql
SELECT t.id, t.name, t.plan, t.subscribed_at,
       s.stripe_status,
       s.ends_at AS access_until,
       s.ends_at - NOW() AS time_remaining,
       CASE s.stripe_status
           WHEN 'active' THEN 'Paid — retains plan until end of period'
           WHEN 'trialing' THEN 'Trial — never paid'
       END AS cancellation_type
FROM tenants t
JOIN subscriptions s ON s.billable_id = t.id
    AND s.billable_type = 'App\Models\Tenant'
WHERE s.ends_at IS NOT NULL
  AND s.ends_at > NOW()
  AND s.stripe_status IN ('active', 'trialing')
ORDER BY s.ends_at;
```

### 17. Full diagnostic for a specific tenant (billing + trial + subscription)

```sql
SELECT t.id, t.name, t.plan, t.stripe_id,
       t.trial_ends_at,
       CASE
           WHEN t.trial_ends_at IS NOT NULL AND t.trial_ends_at > NOW() THEN 'trial active'
           WHEN t.trial_ends_at IS NOT NULL AND t.trial_ends_at < NOW() THEN 'trial expired'
           ELSE 'no trial'
       END AS trial_status,
       t.subscribed_at,
       t.is_active,
       s.stripe_id AS sub_stripe_id,
       s.stripe_status,
       s.stripe_price,
       s.trial_ends_at AS sub_trial_ends,
       s.ends_at AS cancellation_ends_at,
       CASE
           WHEN s.ends_at IS NOT NULL AND s.stripe_status = 'active' THEN 'cancelled (paid)'
           WHEN s.ends_at IS NOT NULL AND s.stripe_status = 'trialing' THEN 'cancelled (trial, $0)'
           WHEN s.stripe_status = 'active' THEN 'active paying'
           WHEN s.stripe_status = 'trialing' THEN 'in trial with subscription'
           WHEN s.stripe_status = 'canceled' THEN 'permanently cancelled'
           WHEN s.id IS NULL THEN 'no subscription'
           ELSE s.stripe_status
       END AS subscription_status
FROM tenants t
LEFT JOIN subscriptions s ON s.billable_id = t.id
    AND s.billable_type = 'App\Models\Tenant'
WHERE t.id = :tenant_id;
```

### 18. Completed cancellation history (period ended)

```sql
SELECT t.id, t.name, t.plan,
       s.stripe_status, s.ends_at AS ended_on,
       s.updated_at AS last_updated
FROM tenants t
JOIN subscriptions s ON s.billable_id = t.id
    AND s.billable_type = 'App\Models\Tenant'
WHERE s.stripe_status = 'canceled'
ORDER BY s.ends_at DESC;
```

### 19. Verify cancellation message (was_charged flag)

> Simulates the `getCancellationInfo()` logic in PHP. Shows what message each tenant would see.

```sql
SELECT t.id, t.name, t.plan,
       s.stripe_status,
       s.ends_at,
       s.ends_at > NOW() AS on_grace_period,
       s.stripe_status = 'active' AS was_charged,
       CASE
           WHEN s.stripe_status = 'active' THEN
               'Your ' || INITCAP(t.plan) || ' plan cancels on ' ||
               TO_CHAR(s.ends_at, 'Month DD, YYYY') ||
               '. No further charges will be made.'
           WHEN s.stripe_status = 'trialing' THEN
               'Your trial period ends on ' ||
               TO_CHAR(s.ends_at, 'Month DD, YYYY') ||
               '. No charges were made.'
       END AS ui_message,
       CASE
           WHEN s.stripe_status = 'active' THEN 'Cancelling soon (yellow)'
           WHEN s.stripe_status = 'trialing' THEN 'Trial cancelled (orange)'
       END AS ui_badge
FROM tenants t
JOIN subscriptions s ON s.billable_id = t.id
    AND s.billable_type = 'App\Models\Tenant'
WHERE s.ends_at IS NOT NULL
  AND s.ends_at > NOW()
  AND s.stripe_status IN ('active', 'trialing')
ORDER BY t.id;
```

### 20. Tenants that cancelled during trial (never paid)

> Detects subscriptions cancelled where Stripe never charged.

```sql
SELECT t.id, t.name, t.plan,
       t.trial_ends_at,
       s.stripe_status,
       s.ends_at AS access_until,
       EXTRACT(DAY FROM s.ends_at - NOW())::int AS days_remaining
FROM tenants t
JOIN subscriptions s ON s.billable_id = t.id
    AND s.billable_type = 'App\Models\Tenant'
WHERE s.ends_at IS NOT NULL
  AND s.stripe_status = 'trialing'
ORDER BY s.ends_at;
```

### 21. Cancellation dashboard (executive summary)

```sql
SELECT
    COUNT(*) FILTER (WHERE s.ends_at IS NOT NULL AND s.ends_at > NOW() AND s.stripe_status = 'active')
        AS cancelled_paid,
    COUNT(*) FILTER (WHERE s.ends_at IS NOT NULL AND s.ends_at > NOW() AND s.stripe_status = 'trialing')
        AS cancelled_trial,
    COUNT(*) FILTER (WHERE s.stripe_status = 'canceled')
        AS permanently_cancelled,
    COUNT(*) FILTER (WHERE s.stripe_status = 'active' AND s.ends_at IS NULL)
        AS active,
    COUNT(*) FILTER (WHERE s.stripe_status = 'trialing' AND s.ends_at IS NULL)
        AS in_trial
FROM subscriptions s
WHERE s.billable_type = 'App\Models\Tenant';
```

---

## Surveys and Retention

### 22. Cancellation surveys

```sql
SELECT cs.id, cs.tenant_id, t.name AS tenant_name,
       cs.reason, cs.details, cs.user_id, cs.created_at
FROM cancellation_surveys cs
JOIN tenants t ON t.id = cs.tenant_id
ORDER BY cs.created_at DESC;
```

### 23. Cancellation reasons (summary)

```sql
SELECT reason, COUNT(*) AS total
FROM cancellation_surveys
GROUP BY reason
ORDER BY total DESC;
```

### 24. Referrals and conversions

```sql
SELECT r.id, r.referrer_tenant_id,
       t1.name AS referrer_name,
       r.referred_tenant_id,
       t2.name AS referred_name,
       r.converted_at, r.rewarded_at,
       r.created_at
FROM referrals r
JOIN tenants t1 ON t1.id = r.referrer_tenant_id
LEFT JOIN tenants t2 ON t2.id = r.referred_tenant_id
ORDER BY r.created_at DESC;
```
