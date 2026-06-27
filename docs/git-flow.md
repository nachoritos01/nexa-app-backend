# Git Flow - Branch Methodology

> Convenciones de ramas, commits, PRs y tags para el proyecto.

---

## Estructura de Ramas

```
main          ← Operations (solo releases y hotfixes)
  │
  └── develop ← Integration (default branch for development)
        │
        ├── feature/*  ← Nuevas funcionalidades
        ├── bugfix/*   ← Bug fixs
        ├── docs/*     ← Solo documentation
        └── hotfix/*   ← Fix urgente en operations
```

---

## Ramas Principales

| Rama | Purpose | Protected |
|------|-----------|-----------|
| `main` | Production code | Yes |
| `develop` | Integration de features | Yes |

---

## Ramas de Trabajo

| Prefijo | Origen | Destino | Purpose |
|---------|--------|---------|-----------|
| `feature/` | develop | develop | Nueva funcionalidad |
| `bugfix/` | develop | develop | Bug fix |
| `docs/` | develop | develop | Solo documentation |
| `hotfix/` | main | main + develop | Fix urgente en operations |

---

## Nomenclatura de Ramas

### Formato

```
tipo/descripcion-en-kebab-case
```

Para features con fases (SaaS, Mobile), incluir la fase:

```
feature/{proyecto}-phase{N}-{descripcion}
feature/{proyecto}-phase{N}-{descripcion}-pr{N}   # Si la fase se divide en PRs
```

### Examples Reales (del historial del proyecto)

```bash
# Features con fases
feature/saas-phase1-multitenancy
feature/saas-phase5-trial-conversion
feature/saas-phase5-landing-superadmin          # Fase 5, segundo PR
feature/saas-phase8-integrations-pr1
feature/saas-phase8-integrations-pr2
feature/saas-phase9-optimization-pr1
feature/mobile-api-endpoints

# Features standalone
feature/order-wizard
feature/customer-portal
feature/payments-payment_gateway
feature/enums-constants
feature/ux-improvements

# Bugfixes
bugfix/decimal-monetary-columns
bugfix/prevent-listo-pending-balance

# Docs
docs/saas-mobile-security

# Hotfixes
hotfix/critical-payment-bug
```

---

## Convention de Commits

Usar [Conventional Commits](https://www.conventionalcommits.org/):

| Prefijo | Uso |
|---------|-----|
| `feat:` | Nueva funcionalidad |
| `fix:` | Bug fix |
| `docs:` | Documentation |
| `refactor:` | Refactoring (sin cambio de comportamiento) |
| `test:` | Solo tests |
| `chore:` | Tareas de mantenimiento (deps, config) |
| `build:` | Build system, CI, dependencias |

### Reglas

- Idioma: **ingles** para commits
- Maximo ~72 caracteres en la primera linea
- Primera linea en imperativo: "add", "fix", "update" (no "added", "fixes")
- Body optional para contexto adicional (separado por linea en blanco)
- NO agregar "Co-Authored-By: Claude" ni "Generated with Claude Code"

### Examples

```bash
# Feature commits (un commit por subtarea significativa)
git commit -m "feat: add auth API endpoints for mobile login/logout/me/push-token"
git commit -m "feat: add dashboard stats API with reusable service"
git commit -m "feat: add store, update, and orders to V1 customers API"

# Fix commits
git commit -m "fix: resolve price calculation error for 2XG sizes"
git commit -m "fix: prevent listo/entregado with pending balance"

# Docs commits
git commit -m "docs: add mobile app roadmap with 6 implementation phases"
git commit -m "docs: update mobile docs with implemented endpoint status"

# Refactor commits
git commit -m "refactor: extract pricing logic to PricingCalculator service"
git commit -m "refactor: centralize business config for template reuse"

# Build commits (Dependabot los genera automaticamente)
git commit -m "build(deps-dev): bump tailwindcss from 4.1.18 to 4.2.1"
```

### Frecuencia de Commits

- Cada paso de una fase = 1 commit minimo
- Si una tarea toca multiples files, commitear al terminar esa tarea
- NO esperar a terminar toda la fase para hacer un solo commit grande
- NO commitear files sensibles (.env, credentials, etc.)

---

## Convention de Pull Requests

### Titulo del PR

Formato: `tipo: descripcion concisa`

Para features con fases:

```
feat: Fase {N} — {Descripcion}
feat: Fase {N} PR{N} — {Descripcion} ({tareas})
```

### Examples Reales

```
feat: SaaS Phase 1 — Multi-tenancy Core
feat: SaaS Fase 2 — Roles y Permisos (RBAC)
feat: SaaS Fase 3 — Registration y Onboarding
feat: SaaS Fase 4 — Stripe + Plans
feat: trial expiry flow + conversion UI (Fase 5 PR1)
feat: SaaS Fase 5 PR2 — Landing, Pricing, Super-Admin, Exports
feat: SaaS Fase 6 PR1 — Core Retention (6.1-6.3)
feat: Fase 6 PR2 — Win-back, Referidos, Messaging Auto (6.4-6.6)
feat: Fase 7 PR1 — Branding en PDFs + Reportes Avanzados (7.1-7.2)
feat: Fase 7 PR2 — CFDI + Corte de Caja + Prioridad (7.3-7.5)
feat: Fase 8 PR1 — API Publica + Webhooks Salientes (8.1-8.2)
feat: Fase 8 PR2 — Rate Limiting + Shopify Research (8.3-8.4)
feat: SaaS Phase 9 PR1 — Performance + Infrastructure (9.1-9.2)
feat: Fase 9 PR2 — Security, Monitoring & Docs
feat: Order Wizard 5 pasos + Envia.com + Operations + Tracking (Fases A-D)
feat: Portal del Customer (Fase F)
feat: extract enums and constants (Fase G)
feat: UX improvements - orders, payments, customers
fix: change monetary columns from integer to decimal(10,2)
fix: prevent listo/entregado with pending balance
docs: add SaaS evolution roadmap and update security hardening
refactor: centralize business config for template reuse
```

### Body del PR

```markdown
## Summary
- Bullet points describiendo QUE se hizo
- Cada punto es una subtarea completada

## Test plan
- [ ] `composer test` pasa (X tests, Y assertions)
- [ ] `composer analyse` sin errores nuevos
- [ ] Verificado en local / staging
```

### Reglas de PRs

- Base branch: `develop` (excepto hotfixes → `main`)
- NO agregar "Generated with Claude Code" ni "Co-Authored-By: Claude"
- Incluir conteo de tests si se agregaron nuevos
- Si una fase es grande, dividir en PRs: `PR1`, `PR2`, etc.

---

## Tags y Releases

Crear tag + release en GitHub despues de cada merge a develop:

| Tipo | Formato | Cuando |
|------|---------|--------|
| Patch | `v0.x.1` | Bugfixes (ej: fix decimal columns) |
| Minor | `v0.x.0` | Features completadas (ej: portal customer, enums) |
| Major | `v1.0.0` | Cambios breaking o hitos grandes (ej: lanzamiento SaaS) |

**Regla:** un tag por PR mergeado a develop (features y bugfixes). Docs son optionales.

### Crear Tag

```bash
git checkout develop
git pull origin develop
git tag -a v0.14.0 -m "feat: mobile API endpoints"
git push origin v0.14.0
```

### Crear Release en GitHub

```bash
gh release create v0.14.0 --title "v0.14.0 — Mobile API Endpoints" --notes "$(cat <<'EOF'
## What's New
- Auth endpoints for mobile (login/logout/me/push-token)
- Dashboard stats API with reusable service
- Customer CRUD expansion (store/update/orders)
- Mobile app roadmap (6 phases)

## Stats
- 286 tests, 745 assertions
EOF
)"
```

---

## Flujo de Trabajo

### 1. Crear Feature

```bash
# Desde develop actualizado
git checkout develop
git pull origin develop

# Crear rama de feature
git checkout -b feature/name-descriptivo

# Trabajar, commits frecuentes
git commit -m "feat: primera subtarea"
git commit -m "feat: segunda subtarea"

# Push
git push -u origin feature/name-descriptivo
```

### 2. Bugfix

```bash
git checkout develop
git pull origin develop
git checkout -b bugfix/descripcion-del-bug

# Corregir el bug...
git commit -m "fix: descripcion de la correccion"
git push -u origin bugfix/descripcion-del-bug

# PR a develop (mismo flujo que feature)
```

### 3. Hotfix (Urgente en Operations)

```bash
# Desde main
git checkout main
git pull origin main
git checkout -b hotfix/descripcion-urgente

# Corregir...
git commit -m "fix: correccion urgente"
git push -u origin hotfix/descripcion-urgente

# PR a main Y merge back a develop
```

### 4. Crear Pull Request

```bash
gh pr create --base develop \
  --title "feat: descripcion concisa" \
  --body "$(cat <<'EOF'
## Summary
- Subtarea 1
- Subtarea 2

## Test plan
- [ ] `composer test` pasa
- [ ] `composer analyse` sin errores
EOF
)"
```

### 5. Despues del Merge

```bash
# Actualizar develop local
git checkout develop
git pull origin develop

# Eliminar rama local
git branch -d feature/name-descriptivo

# Crear tag si aplica
git tag -a v0.x.0 -m "descripcion"
git push origin v0.x.0
```

---

## Checklist antes de PR

- [ ] Codigo formateado (`composer format`)
- [ ] Sin errores de PHPStan (`composer analyse`)
- [ ] Tests pasan (`composer test`)
- [ ] Commits siguen convencion (feat/fix/docs/refactor)
- [ ] PR tiene titulo descriptivo
- [ ] PR tiene body con Summary + Test plan
- [ ] Rama actualizada con develop (`git merge develop` o `git rebase develop`)
- [ ] NO incluye files sensibles (.env, credentials, screenshots)

---

## Commands Utiles

```bash
# Ver todas las ramas
git branch -a

# Actualizar develop
git checkout develop && git pull

# Ver historial de PRs mergeados
gh pr list --state merged --limit 20

# Eliminar rama local despues de merge
git branch -d feature/mi-feature

# Eliminar rama remota
git push origin --delete feature/mi-feature

# Ver historial grafico
git log --oneline --graph --all

# Ver tags
git tag --sort=-creatordate | head -10
```

---

**Ultima actualizacion:** 2026-02-24
