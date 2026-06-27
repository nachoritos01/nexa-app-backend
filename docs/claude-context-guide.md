# Guide de Contexto .claude

Documentation de la estructura de contexto para desarrollo asistido.

---

## Estructura

```
.claude/
├── CLAUDE.md                      # Entry point principal
├── .claudeignore                  # Files a ignorar
│
├── context/                       # Estado del proyecto
│   ├── current.md                 # Estado actual (IMPORTANTE)
│   ├── architecture.md            # Arquitectura del sistema
│   └── conventions.md             # Convenciones de code
│
├── commands/                      # Commands available
│   ├── README.md                  # Index de commands
│   ├── commit/commit.md           # /commit
│   └── review/review.md           # /review
│
├── rules/                         # Reglas de dominio
│   └── laravel.md                 # Reglas specifics Laravel
│
├── templates/                     # Templates reutilizables
│   ├── feature-spec.md            # Specification de features
│   └── session-log.md             # Log de session
│
├── automation/workflows/          # Scripts de automation
│   └── daily-workflow.sh          # Workflow diario
│
└── logs/                          # Logs de desarrollo
    ├── bitacoras/                 # Logs permanentes
    └── sessions/                  # Sesiones temporales
```

---

## Files Clave

### CLAUDE.md

Entry point principal. Contiene:
- Overview del proyecto
- Arquitectura basic
- Commands comunes
- Referencias a otros files

### context/current.md

**El file more importante.** Mantiene el estado actual:
- Sprint/iteration actual
- Tareas completadas
- Tareas en progreso
- Decisiones recientes
- Branches activos

**Regla:** Actualizar al inicio y final de cada session.

### context/conventions.md

Convenciones de code:
- Naming conventions
- Estructura de files
- Patrones Laravel
- Estilo de code
- Convenciones de Git

### rules/laravel.md

Reglas specifics del framework:
- Do's and Don'ts
- Patrones de models
- Patrones de controladores
- Security

---

## Commands

### /commit

Crear commit con mensaje convencional.

```
/commit
```

Analiza cambios staged y genera mensaje siguiendo conventional commits.

### /review

Code review de files.

```
/review app/Models/Product.php
```

Revisa code buscando:
- Bugs potenciales
- Problemas de security
- Violaciones de convenciones
- Oportunidades de mejora

---

## Workflow Diario

Ejecutar al inicio de cada session:

```bash
bash .claude/automation/workflows/daily-workflow.sh
```

Output:
- Estado de Git
- Commits recientes
- Estado de PostgreSQL
- Resultado de PHPStan
- Recordatorio de contexto

---

## Templates

### feature-spec.md

Para documentar nuevas features:

```markdown
# Feature: {Name}

## Overview
...

## User Story
Como [user], quiero [objetivo] para [beneficio].

## Acceptance Criteria
- [ ] Criterio 1
- [ ] Criterio 2

## Technical Design
...
```

### session-log.md

Para documentar sesiones de desarrollo:

```markdown
# Development Session

**Date**: 2026-01-30
**Branch**: feature/fase2-migrations-models
**Focus**: Implementar models

## Goals
- [ ] Goal 1

## Accomplished
- Item completado

## Next Session
- Next pasos
```

---

## Best Practices

### 1. Mantener current.md actualizado

```bash
# Al iniciar session
cat .claude/context/current.md

# Al terminar, editar con los cambios realizados
```

### 2. Usar commands consistentemente

```
/commit          # En lugar de escribir mensajes manuales
/review file.php # Antes de hacer PR
```

### 3. Documentar decisiones

Agregar en `current.md`:

```markdown
## Recent Decisions

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-01-30 | Usar Services | Separar logic de negocio |
```

### 4. Crear logs de sesiones largas

Para sesiones de more de 2 horas:

```bash
cp .claude/templates/session-log.md .claude/logs/sessions/2026-01-30.md
# Editar con notas de la session
```

---

## Ignorar Files

`.claudeignore` excluye:
- `vendor/`, `node_modules/`
- `public/build/`
- Files de cache
- `.env` y secretos
- Files grandes
- IDE helpers generados

---

## Integration con Git

La carpeta `.claude/` se commitea al repositorio:

```bash
git add .claude/
git commit -m "docs: update development context"
```

Esto permite:
- Compartir contexto con el equipo
- Mantener historial de decisiones
- Sincronizar estado del proyecto

---

**Last update:** 2026-01-30
