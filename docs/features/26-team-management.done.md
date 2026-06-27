# Team Management — Gestion de Equipo

**Status:** Done
**Date:** 2026-02-26
**Branch:** feature/team-management

## Resumen

Pagina Filament para gestionar miembros del equipo: invitar, cambiar roles y quitar miembros. Los planes Growth (5 users) y Pro (ilimitado) permiten equipos. Se respetan los limites del plan y los 5 roles de Spatie existentes.

## Arquitectura

- **Filament Page con HasTable** — `app/Filament/Pages/TeamManagement.php`
- **Acceso** — permiso `users.manage` (owner + admin)
- **Navegacion** — grupo "Configuracion", sort 96
- **Password reset** — Filament `->passwordReset()` habilitado en AdminPanelProvider

## Funcionalidades

### Table de miembros
- Query con JOIN a `tenant_user` filtrando por tenant actual
- Columns: name, email, rol (badge con color), fecha de ingreso
- Busqueda por name y email

### Invitar miembro (header action)
- Modal con email + select de rol
- Si email existe → attach al tenant con rol seleccionado
- Si email no existe → crear User + generar token de password reset + attach
- Validacion: `isAtLimit('users')` bloquea invitacion, duplicados muestran warning
- Notificacion email con link de reset (nuevos) o link al panel (existentes)
- `checkNearLimit('users')` post-invitacion

### Cambiar rol (row action)
- Modal con select de rol
- Solo owner puede asignar rol "owner"
- Admin no puede cambiar el rol del owner
- No se puede cambiar el propio rol (accion hidden)

### Quitar del equipo (row action)
- Confirmacion antes de ejecutar
- No se puede quitar a uno mismo (accion hidden)
- No se puede dejar el tenant con 0 miembros
- Solo hace `detach()`, no elimina la cuenta

### Cache
- `Tenant::clearUsageCache()` despues de attach/detach
- Metodo centralizado para evitar duplicar cache key

## Vista
- Header con uso actual (X/Y users + progress bar)
- Boton "Mejorar plan" visible cuando at limit
- Table de miembros debajo

## Notification
- `TeamInviteNotification` — email con name del tenant, rol asignado
- Users nuevos: link "Establecer contrasena" (URL firmada de reset)
- Users existentes: link "Ir al panel"

## Files

| File | Tipo |
|---------|------|
| `app/Filament/Pages/TeamManagement.php` | Nuevo |
| `app/Notifications/TeamInviteNotification.php` | Nuevo |
| `resources/views/filament/pages/team-management.blade.php` | Nuevo |
| `tests/Feature/TeamManagementTest.php` | Nuevo |
| `app/Models/Tenant.php` | Modificado (clearUsageCache) |
| `app/Providers/Filament/AdminPanelProvider.php` | Modificado (passwordReset) |

## Tests

15 tests, 76 assertions:
- Acceso: owner/admin pueden, sales/operations no
- Invitar: user existente, email nuevo (con resetUrl), duplicado, al limite
- Cambiar rol: owner puede, admin no puede promover a owner, no puede cambiar owner
- Quitar: owner puede, no puede dejar 0 miembros
- Cache: invalidacion despues de attach/detach

## Pendiente

- [ ] Configurar mail provider (Resend/Mailgun/SES) — actualmente `MAIL_MAILER=log`
- [ ] Check emails en operations (Railway env vars)
- [ ] Considerar: reenviar invitacion si el user no ha establecido contrasena
