# Bug Report Template

Usar este formato para reportar defectos encontrados durante las tests.

---

## BUG-{FASE}-{NUMERO}: {Titulo descriptivo}

| Campo | Valor |
|-------|-------|
| **ID** | BUG-{FASE}-{NUMERO} |
| **Fecha** | YYYY-MM-DD |
| **Reportado por** | {name} |
| **Fase** | {fase del roadmap} |
| **Severidad** | Critica / Alta / Media / Baja |
| **Prioridad** | P1 / P2 / P3 / P4 |
| **Estado** | Abierto / En progreso / Resuelto / Cerrado / No reproducible |
| **Asignado a** | {name o sin asignar} |
| **Test Case relacionado** | TC-{ID} |
| **Ambiente** | Local / Staging / Operations |

### Descripcion

{Descripcion clara y concisa del defecto.}

### Steps para reproducir

1. {Step 1}
2. {Step 2}
3. {Step 3}

### Resultado actual

{Que ocurre actualmente — el comportamiento incorrecto.}

### Resultado esperado

{Que deberia ocurrir segun la especificacion.}

### Evidencia

{Screenshots, logs, stack traces, grabaciones de pantalla.}

```
// Stack trace o log relevante
```

### Datos de test

| Campo | Valor |
|-------|-------|
| User | {email} |
| Rol | {rol} |
| Tenant | {name tenant} |
| URL | {url donde ocurre} |
| Browser | {Chrome/Firefox/Safari + version} |

### Notas adicionales

{Contexto adicional, workarounds, posible causa raiz.}

### Resolucion

| Campo | Valor |
|-------|-------|
| Commit fix | {hash} |
| Fecha resolucion | YYYY-MM-DD |
| Verificado por | {name} |

---

## Clasificacion de Severidad

| Severidad | Descripcion | Example |
|-----------|-------------|---------|
| **Critica** | Sistema inutilizable, datos corruptos, security comprometida | User sin permiso puede eliminar datos |
| **Alta** | Funcionalidad principal bloqueada, sin workaround | No se pueden crear orders |
| **Media** | Funcionalidad afectada pero hay workaround | Sidebar muestra item incorrecto pero URL directa funciona |
| **Baja** | Cosmetico, UX menor, no afecta funcionalidad | Texto mal alineado, typo en label |

## Clasificacion de Prioridad

| Prioridad | Descripcion | SLA |
|-----------|-------------|-----|
| **P1** | Resolver inmediatamente | Mismo dia |
| **P2** | Resolver antes del siguiente release | 2-3 dias |
| **P3** | Resolver en el sprint actual | 1 semana |
| **P4** | Backlog, resolver cuando haya capacidad | Sin SLA |
