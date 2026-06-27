# 11. Git Troubleshooting - Resolution de Errores de Rama

## Caso: Commit accidental en develop (en lugar de feature branch)

### Escenario

Se hizo un commit directamente en `develop` cuando should have ir en una feature branch:

```
develop: 420ac1d → 53269b0 (commit accidental here)
```

### Solution: Mover commit a nueva rama y resetear develop

#### Step 1: Crear la feature branch desde el commit actual

```bash
git branch feature/mi-feature
```

Esto crea una nueva rama apuntando al mismo commit (`53269b0`), sin mover HEAD.

#### Step 2: Resetear develop al commit anterior

```bash
git reset --hard HEAD~1
```

`HEAD~1` significa "un commit back". Develop vuelve a `420ac1d`.

Estado local after de estos 2 pasos:
```
develop:            420ac1d  ✓ (reseteado)
feature/mi-feature: 53269b0  ✓ (con el commit)
```

#### Step 3: Push de la feature branch

```bash
git push -u origin feature/mi-feature
```

#### Step 4: Sincronizar develop en remote

```bash
git push origin develop --force-with-lease
```

`--force-with-lease` es more seguro que `--force`: verifica que nadie more haya pusheado al remote antes de sobrescribir.

#### Step 5: Crear el PR

```bash
gh pr create --base develop --head feature/mi-feature --title "feat: mi feature"
```

---

## Errors comunes durante este proceso

### Error 1: `"No commits between develop and feature/..."` al crear PR

**Causa:** `origin/develop` no se was reset. Ambas ramas apuntan al mismo commit en GitHub.

**Solution:** Asegurarse de hacer `git push origin develop --force-with-lease` para que el remote refleje el reset.

### Error 2: `"Everything up-to-date"` al hacer push del reset

**Causa:** `git checkout develop` puede re-sincronizar silenciosamente con `origin/develop`, deshaciendo el reset local.

**Por what pasa:** Al hacer checkout a develop, si git detecta que `origin/develop` is adelante, puede hacer fast-forward automatic, revirtiendo el reset.

**Solution:** Hacer el reset y push sin cambiar de rama en medio:

```bash
# Correcto: todo de una vez
git checkout develop
git reset --hard <commit-hash>
git push origin develop --force-with-lease

# Incorrecto: cambiar de rama entre reset y push
git reset --hard HEAD~1
git checkout otra-rama        # ← al volver a develop, se puede perder el reset
git checkout develop
git push origin develop       # ← "Everything up-to-date"
```

### Error 3: No saber a what commit resetear

**Solution:** Usar `git reflog` para ver el historial de movimientos de HEAD:

```bash
git reflog
# Muestra:
# 53269b0 HEAD@{0}: commit: feat: FASE 7...
# 420ac1d HEAD@{1}: merge: Merge pull request #7...
```

O usar `git log --oneline` para encontrar el commit deseado.

---

## Resumen del flujo correcto

```bash
# 1. Darme cuenta que committed en develop
git log --oneline -3    # Check estado

# 2. Crear rama desde el commit actual
git branch feature/mi-feature

# 3. Resetear develop (sin cambiar de rama)
git reset --hard HEAD~1

# 4. Push de ambos cambios
git push origin develop --force-with-lease      # Resetear remote
git push -u origin feature/mi-feature           # Subir feature branch

# 5. Crear PR
gh pr create --base develop --head feature/mi-feature
```

## Prevention

Para evitar este error, crear siempre la rama **antes** de empezar a trabajar:

```bash
git checkout -b feature/mi-feature    # Crear y cambiar a la rama
# ... hacer cambios ...
git add . && git commit -m "feat: ..."
git push -u origin feature/mi-feature
```
