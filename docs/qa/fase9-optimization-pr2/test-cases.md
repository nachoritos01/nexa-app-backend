# Test Cases — SaaS Fase 9 PR2: Security + Monitoring (9.3-9.4)

**Version:** 1.0
**Date:** 2026-02-23
**Relacionado:** `test-plan.md`

---

## Convenciones

- **ID:** TC-SEC-{numero} (SEC = Security)
- **Priority:** P1 / P2 / P3
- **Tipo:** Funcional / Negativo / Regresion / Security
- **Status:** Pendiente / Pasado / Fallido / Bloqueado

---

## 9.3.1 — Session Security

### TC-SEC-001: SESSION_ENCRYPT documentado en .env.example

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-001 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | .env.example |
| **Steps** | 1. Check SESSION_ENCRYPT=false con comentario "Set to true in production"<br>2. Check SESSION_SECURE_COOKIE comentado con nota HTTPS |
| **Resultado esperado** | Variables documentadas para operations |
| **Estado** | Pasado |

### TC-SEC-002: config/session.php lee de env

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-002 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | config/session.php |
| **Steps** | 1. Check `'encrypt' => env('SESSION_ENCRYPT', false)`<br>2. Check `'secure' => env('SESSION_SECURE_COOKIE')` |
| **Resultado esperado** | Config ya lee de env (sin cambios necesarios) |
| **Estado** | Pasado |

---

## 9.3.2 — Encrypted Tenant Settings

### TC-SEC-003: setSecureSetting encripta el valor

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-003 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`EncryptedSettingsTest::test_set_secure_setting_encrypts_value`) |
| **Precondiciones** | Tenant creado |
| **Steps** | 1. setSecureSetting('webhook_secret', 'my-secret-key')<br>2. Leer raw value de settings JSON<br>3. Check raw != 'my-secret-key'<br>4. Check Crypt::decryptString(raw) == 'my-secret-key' |
| **Resultado esperado** | Valor encriptado en DB, desencriptable con Crypt |
| **Estado** | Pasado |

### TC-SEC-004: getSecureSetting desencripta correctamente

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-004 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`EncryptedSettingsTest::test_get_secure_setting_decrypts_correctly`) |
| **Precondiciones** | Setting encriptado previamente |
| **Steps** | 1. setSecureSetting('webhook_secret', 'test-secret-123')<br>2. getSecureSetting('webhook_secret')<br>3. Check resultado == 'test-secret-123' |
| **Resultado esperado** | Valor desencriptado correctamente |
| **Estado** | Pasado |

### TC-SEC-005: Legacy plain value se lee sin error

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-005 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`EncryptedSettingsTest::test_legacy_plain_value_readable_without_error`) |
| **Precondiciones** | Setting con valor plano en DB (pre-encriptacion) |
| **Steps** | 1. Insertar valor plano directamente en settings JSON<br>2. getSecureSetting() del key<br>3. Check que retorna el valor plano sin excepcion |
| **Resultado esperado** | DecryptException caught, retorna valor plano |
| **Estado** | Pasado |

### TC-SEC-006: saas:encrypt-settings migra valores existentes

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-006 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`EncryptedSettingsTest::test_encrypt_command_migrates_existing_values`) |
| **Precondiciones** | Tenant con webhook_secret plano |
| **Steps** | 1. Insertar valor plano<br>2. php artisan saas:encrypt-settings<br>3. Check valor en DB ya no es plano<br>4. Check getSecureSetting lo lee correctamente<br>5. Ejecutar command de nuevo → skip (idempotente) |
| **Resultado esperado** | 1ra ejecucion: "Encrypted 1". 2da: "skipped 1" |
| **Estado** | Pasado |

### TC-SEC-007: WebhookService usa getSecureSetting

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-007 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | WebhookService.php |
| **Steps** | 1. Check que dispatch() usa `$tenant->getSecureSetting('webhook_secret')` |
| **Resultado esperado** | Secret desencriptado antes de enviar webhook |
| **Estado** | Pasado |

### TC-SEC-008: WebhookSettings usa accessors encriptados

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-008 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | WebhookSettings.php |
| **Steps** | 1. mount() usa getSecureSetting para leer<br>2. save() usa setSecureSetting para escribir |
| **Resultado esperado** | UI lee/escribe secret encriptado |
| **Estado** | Pasado |

---

## 9.3.3 — Tenant Isolation Security Tests

### TC-SEC-009: Tenant A no ve orders de tenant B

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-009 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`TenantIsolationTest::test_tenant_a_cannot_see_tenant_b_orders`) |
| **Precondiciones** | 2 tenants con orders |
| **Steps** | 1. Crear 2 orders en tenant A, 3 en tenant B<br>2. Query como tenant A → count 2<br>3. Query como tenant B → count 3 |
| **Resultado esperado** | Aislamiento completo de orders |
| **Estado** | Pasado |

### TC-SEC-010: Tenant A no ve customers de tenant B

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-010 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`TenantIsolationTest::test_tenant_a_cannot_see_tenant_b_customers`) |
| **Precondiciones** | 2 tenants con customers |
| **Steps** | 1. Customer "A" en tenant A, "B" en tenant B<br>2. Query como tenant A → solo "A" |
| **Resultado esperado** | Aislamiento completo de customers |
| **Estado** | Pasado |

### TC-SEC-011: Tenant A no ve products de tenant B

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-011 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`TenantIsolationTest::test_tenant_a_cannot_see_tenant_b_products`) |
| **Precondiciones** | 2 tenants con products |
| **Steps** | 1. 3 products en tenant A, 1 en tenant B<br>2. Query como tenant A → count 3 |
| **Resultado esperado** | Aislamiento completo de products |
| **Estado** | Pasado |

### TC-SEC-012: Crear model auto-asigna tenant_id

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-012 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`TenantIsolationTest::test_creating_model_auto_assigns_current_tenant`) |
| **Precondiciones** | currentTenant activo |
| **Steps** | 1. Crear order<br>2. Check order.tenant_id == currentTenant.id |
| **Resultado esperado** | tenant_id asignado automaticamente via BelongsToTenant |
| **Estado** | Pasado |

### TC-SEC-013: withoutGlobalScopes retorna todos

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-013 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`TenantIsolationTest::test_without_global_scopes_returns_all_records`) |
| **Precondiciones** | 2 tenants con orders |
| **Steps** | 1. 2 + 3 orders en tenants distintos<br>2. Order::withoutGlobalScopes()->get() → count 5 |
| **Resultado esperado** | Confirma que scope es necesario para aislamiento |
| **Estado** | Pasado |

### TC-SEC-014: API v1 token tenant A no retorna datos tenant B

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-014 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`TenantIsolationTest::test_api_v1_with_tenant_a_token_does_not_return_tenant_b_data`) |
| **Precondiciones** | 2 tenants con orders y tokens |
| **Steps** | 1. 2 orders tenant A, 3 orders tenant B<br>2. GET /api/v1/orders con token A<br>3. Check data count == 2 |
| **Resultado esperado** | API respeta tenant scope via token |
| **Estado** | Pasado |

### TC-SEC-015: WebhookLog filtrado por tenant

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-015 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`TenantIsolationTest::test_webhook_logs_filtered_by_tenant`) |
| **Precondiciones** | WebhookLogs en 2 tenants |
| **Steps** | 1. Log en tenant A (url a.example.com)<br>2. Log en tenant B (url b.example.com)<br>3. Query como A → solo a.example.com |
| **Resultado esperado** | Webhook logs aislados por tenant |
| **Estado** | Pasado |

### TC-SEC-016: DB query directa retorna todo

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-016 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`TenantIsolationTest::test_direct_db_query_without_scope_returns_all`) |
| **Precondiciones** | Orders en 2 tenants |
| **Steps** | 1. DB::table('orders')->count() → total sin filtro |
| **Resultado esperado** | Confirma que sin scope se ve todo (scope es esencial) |
| **Estado** | Pasado |

---

## 9.3.4 — OWASP Security Headers

### TC-SEC-017: Response incluye X-Content-Type-Options

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-017 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`SecurityHeadersTest::test_response_includes_x_content_type_options`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. GET /<br>2. Check header X-Content-Type-Options: nosniff |
| **Resultado esperado** | Header presente con valor nosniff |
| **Estado** | Pasado |

### TC-SEC-018: Response incluye X-Frame-Options

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-018 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`SecurityHeadersTest::test_response_includes_x_frame_options`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. GET /<br>2. Check header X-Frame-Options: DENY |
| **Resultado esperado** | Header presente con valor DENY |
| **Estado** | Pasado |

### TC-SEC-019: Response incluye Referrer-Policy

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-019 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`SecurityHeadersTest::test_response_includes_referrer_policy`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. GET /<br>2. Check header Referrer-Policy: strict-origin-when-cross-origin |
| **Resultado esperado** | Header presente con valor correcto |
| **Estado** | Pasado |

### TC-SEC-020: SecurityHeaders middleware registrado globalmente

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-020 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | bootstrap/app.php |
| **Steps** | 1. Check `$middleware->append(SecurityHeaders::class)` |
| **Resultado esperado** | Middleware se aplica a todas las rutas |
| **Estado** | Pasado |

### TC-SEC-021: No hay {!! !!} con user input en Blade

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-021 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | No (grep manual) |
| **Precondiciones** | Templates Blade |
| **Steps** | 1. Buscar `{!! $` en resources/views<br>2. Check que solo se usa con contenido server-controlled |
| **Resultado esperado** | Solo content.blade.php con markdown del filesystem |
| **Estado** | Pasado |

---

## 9.4.1 — Sentry Integration

### TC-SEC-022: sentry/sentry-laravel instalado

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-022 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | composer.json |
| **Steps** | 1. Check sentry/sentry-laravel en require |
| **Resultado esperado** | Paquete instalado |
| **Estado** | Pasado |

### TC-SEC-023: Sentry tiene contexto de tenant

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-023 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | bootstrap/app.php |
| **Steps** | 1. Check reportable() con configureScope<br>2. Check setTag('tenant_id'), setTag('tenant_plan')<br>3. Check setContext('tenant', {id, name, plan})<br>4. Check setUser({id, email}) |
| **Resultado esperado** | Errores incluyen tenant y user context |
| **Estado** | Pasado |

### TC-SEC-024: SENTRY_LARAVEL_DSN en .env.example

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-024 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | .env.example |
| **Steps** | 1. Check SENTRY_LARAVEL_DSN=<br>2. Check SENTRY_TRACES_SAMPLE_RATE=0.1 |
| **Resultado esperado** | Variables documentadas |
| **Estado** | Pasado |

---

## 9.4.2 — Activation Funnel Widget

### TC-SEC-025: ActivationFunnel widget existe

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-025 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | app/Filament/SuperAdmin/Widgets/ActivationFunnel.php |
| **Steps** | 1. Check 5 etapas: Signup, Onboarding, 1er Order, 2do Order, Suscrito<br>2. Check calculo de conversion % entre etapas<br>3. Check vista blade con barras de progreso |
| **Resultado esperado** | Widget con funnel completo y conversion % |
| **Estado** | Pasado |

---

## 9.4.3 — Monitoring Documentation

### TC-SEC-026: RAILWAY_DEPLOY_STATUS.md tiene monitoring

| Campo | Valor |
|-------|-------|
| **ID** | TC-SEC-026 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion por code review) |
| **Precondiciones** | RAILWAY_DEPLOY_STATUS.md |
| **Steps** | 1. Check seccion "Variables de Security"<br>2. Check seccion "Health Check"<br>3. Check seccion "UptimeRobot"<br>4. Check seccion "Sentry" |
| **Resultado esperado** | Documentacion completa de monitoring |
| **Estado** | Pasado |

---

## Resumen de cobertura

| Estado | Cantidad |
|--------|----------|
| Pasado | 26 |
| Total | **26** |

### Por tipo

| Tipo | Cantidad |
|------|----------|
| Automatizado (PHPUnit) | 15 |
| Code review | 11 |

### Por area

| Area | Cantidad |
|------|----------|
| Session security (9.3.1) | 2 |
| Encrypted settings (9.3.2) | 6 |
| Tenant isolation (9.3.3) | 8 |
| OWASP headers (9.3.4) | 5 |
| Sentry (9.4.1) | 3 |
| Activation funnel (9.4.2) | 1 |
| Monitoring docs (9.4.3) | 1 |
