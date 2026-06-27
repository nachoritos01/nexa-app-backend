# Test Plan — Loyalty Program (Points, Tiers & Rewards)

**Version:** 1.0
**Date:** 2026-03-16
**Feature:** Loyalty program with points, tiers (Bronze/Silver/Gold/VIP), rewards catalog, customer portal tab
**Branch:** feature/loyalty-program
**PR:** #6

---

## 1. Objetivo

Validar que el programa de lealtad permite acumular puntos por compras, calcular tiers automaticamente, mostrar rewards disponibles en el portal del customer, y administrar el catalogo de rewards desde Filament.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Data layer (L1) | 4 migraciones, 3 modelos (LoyaltyReward, LoyaltyTransaction, LoyaltyCoupon), LoyaltyTier enum |
| Business logic (L2) | LoyaltyService (credit/reverse/redeem), Order observer, first-purchase bonus |
| Customer portal (L3) | Loyalty tab con tier card, progress bar, rewards, transactions, coupons |
| Admin panel (L4) | LoyaltyRewardResource CRUD, CustomerResource loyalty section |
| Plugin integration | Loyalty plugin en marketplace, "Included" para Growth/Pro |
| Seeder | 9 default rewards (idempotent via firstOrCreate) |
| Rebase | Conflictos con billing-history resueltos correctamente |

### Fuera de alcance

- L5: Referrals integration + polish (deferred)
- Stripe payment for loyalty coupons — coupons are applied manually
- Email notifications for tier upgrades — future enhancement

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Unit/Feature | PHPUnit (301 tests) | Models, service, observer, portal, Filament |
| Static analysis | PHPStan Level 5 | 0 errors |
| Manual/E2E | Chrome DevTools MCP | Admin CRUD, marketplace, customer portal, loyalty tab |

## 4. Entorno

| Componente | Detalle |
|------------|---------|
| PHP | 8.5 |
| Laravel | 12 |
| Filament | 3.3 |
| Livewire | 4.1 |
| PostgreSQL | 15 (Docker, port 5433) |

## 5. Riesgos

| Riesgo | Mitigacion |
|--------|------------|
| Puntos negativos por reverse en orden cancelada | LoyaltyService valida que no queden puntos negativos |
| Tier upgrade no reflejado en portal | Tier se calcula dinámicamente desde lifetime_points |
| Conflict con billing-history en TenantResource | Resuelto: ambas funcionalidades coexisten |
| Conflict con billing-history en PluginBillingService | Resuelto: BillingHistoryService calls preservados |
| Reward redeemed sin puntos suficientes | Validacion en LoyaltyService::redeemReward() |
