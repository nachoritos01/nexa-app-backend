# CI_CD.md — SaaS Template Mobile

> GitHub Actions + EAS: lint, tests, builds automatics y distribution interna.

---

## 1. Vision General del Pipeline

```
Push a PR / branch
      │
      ▼
┌─────────────────┐
│  CI: Validate   │  ← Lint + TypeCheck + Tests (fast, ~3 min)
│  (GitHub Actions)│
└────────┬────────┘
         │ merge a develop
         ▼
┌─────────────────┐
│  CD: Staging    │  ← Build EAS + OTA Update (staging)
│  (GitHub Actions)│   Distribution interna a testers
└────────┬────────┘
         │ merge a main
         ▼
┌─────────────────┐
│  CD: Production │  ← Build EAS + Submit stores
│  (GitHub Actions)│   App Store + Play Store
└─────────────────┘
```

---

## 2. Estructura de Branches y Triggers

| Branch | Trigger | Pipeline |
|--------|---------|----------|
| `feature/*`, `bugfix/*` | Push / PR → develop | CI: lint + tests |
| `develop` | Merge | CD: build staging + OTA update |
| `main` | Merge desde develop | CD: build production + submit stores |
| `hotfix/*` | Merge → main | CD: build production urgente |

---

## 3. GitHub Secrets Necesarios

Configurar en `Settings → Secrets and variables → Actions`:

| Secret | Description |
|--------|-------------|
| `EXPO_TOKEN` | Token de authentication de Expo (eas login → token) |
| `APPLE_ID` | Apple ID para App Store Connect |
| `APPLE_TEAM_ID` | Team ID de Apple Developer |
| `ASC_APP_ID` | App ID en App Store Connect |
| `GOOGLE_PLAY_KEY` | JSON key de Google Play Service Account |
| `SLACK_WEBHOOK_URL` | (Optional) Notificaciones de builds |

### Obtener EXPO_TOKEN

```bash
npx expo login
npx expo whoami
# En la web: expo.dev → Account → Access Tokens → Create
```

---

## 4. Workflow: CI (Validate)

```yaml
# .github/workflows/ci.yml
name: CI — Validate

on:
  push:
    branches-ignore:
      - main
      - develop
  pull_request:
    branches:
      - develop
      - main

jobs:
  validate:
    name: Lint + TypeCheck + Tests
    runs-on: ubuntu-latest
    timeout-minutes: 15

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: 'npm'

      - name: Install dependencies
        run: npm ci

      - name: TypeScript check
        run: npx tsc --noEmit

      - name: Lint
        run: npm run lint

      - name: Tests
        run: npm run test:ci

      - name: Upload coverage
        uses: codecov/codecov-action@v4
        if: always()
        with:
          file: ./coverage/lcov.info
          fail_ci_if_error: false
```

---

## 5. Workflow: CD Staging

```yaml
# .github/workflows/cd-staging.yml
name: CD — Staging Build

on:
  push:
    branches:
      - develop

jobs:
  build-staging:
    name: EAS Build (Staging)
    runs-on: ubuntu-latest
    timeout-minutes: 45

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: 'npm'

      - name: Install dependencies
        run: npm ci

      - name: Setup Expo
        uses: expo/expo-github-action@v8
        with:
          expo-version: latest
          eas-version: latest
          token: ${{ secrets.EXPO_TOKEN }}

      - name: Run CI checks
        run: |
          npx tsc --noEmit
          npm run lint
          npm run test:ci

      - name: EAS Build — Android (staging)
        run: eas build --profile staging --platform android --non-interactive
        env:
          EXPO_TOKEN: ${{ secrets.EXPO_TOKEN }}

      - name: EAS Build — iOS (staging)
        run: eas build --profile staging --platform ios --non-interactive
        env:
          EXPO_TOKEN: ${{ secrets.EXPO_TOKEN }}

      - name: OTA Update (staging)
        run: |
          eas update \
            --branch staging \
            --message "Deploy: ${{ github.sha }} — ${{ github.event.head_commit.message }}"
        env:
          EXPO_TOKEN: ${{ secrets.EXPO_TOKEN }}

      - name: Notify team
        if: always()
        uses: 8398a7/action-slack@v3
        with:
          status: ${{ job.status }}
          text: "Staging build ${{ job.status }}: ${{ github.event.head_commit.message }}"
        env:
          SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK_URL }}
```

---

## 6. Workflow: CD Production

```yaml
# .github/workflows/cd-production.yml
name: CD — Production Build & Submit

on:
  push:
    branches:
      - main

jobs:
  build-and-submit:
    name: EAS Build + Submit (Production)
    runs-on: ubuntu-latest
    timeout-minutes: 90

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: 'npm'

      - name: Install dependencies
        run: npm ci

      - name: Setup Expo
        uses: expo/expo-github-action@v8
        with:
          expo-version: latest
          eas-version: latest
          token: ${{ secrets.EXPO_TOKEN }}

      - name: Run CI checks
        run: |
          npx tsc --noEmit
          npm run lint
          npm run test:ci

      - name: Extract version from package.json
        id: version
        run: echo "version=$(node -p "require('./package.json').version")" >> $GITHUB_OUTPUT

      - name: EAS Build — Android (production)
        run: eas build --profile production --platform android --non-interactive
        env:
          EXPO_TOKEN: ${{ secrets.EXPO_TOKEN }}

      - name: EAS Build — iOS (production)
        run: eas build --profile production --platform ios --non-interactive
        env:
          EXPO_TOKEN: ${{ secrets.EXPO_TOKEN }}

      - name: Submit — Play Store
        run: |
          eas submit \
            --profile production \
            --platform android \
            --non-interactive \
            --latest
        env:
          EXPO_TOKEN: ${{ secrets.EXPO_TOKEN }}
          GOOGLE_PLAY_KEY: ${{ secrets.GOOGLE_PLAY_KEY }}

      - name: Submit — App Store
        run: |
          eas submit \
            --profile production \
            --platform ios \
            --non-interactive \
            --latest
        env:
          EXPO_TOKEN: ${{ secrets.EXPO_TOKEN }}
          APPLE_ID: ${{ secrets.APPLE_ID }}
          APPLE_TEAM_ID: ${{ secrets.APPLE_TEAM_ID }}
          ASC_APP_ID: ${{ secrets.ASC_APP_ID }}

      - name: Create Git Tag
        run: |
          git config user.email "ci@saas-template.mx"
          git config user.name "SaaS Template CI"
          git tag "v${{ steps.version.outputs.version }}"
          git push origin "v${{ steps.version.outputs.version }}"

      - name: Notify success
        if: success()
        uses: 8398a7/action-slack@v3
        with:
          status: success
          text: "Operations v${{ steps.version.outputs.version }} enviada a stores"
        env:
          SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK_URL }}
```

---

## 7. OTA Updates (Over The Air)

EAS Update permite enviar actualizaciones JS sin pasar por revision de stores.

### When usar OTA vs Build completa

| Cambio | OTA suficiente | Necesita Build |
|--------|---------------|----------------|
| Fix de bug en JS/TS | Si | No |
| Nuevo feature (solo JS) | Si | No |
| Cambio de UI | Si | No |
| Nuevo paquete nativo | No | Si |
| Cambio en app.json | No | Si |
| Update de SDK Expo | No | Si |

### Enviar OTA update manualmente

```bash
# OTA a staging
eas update --branch staging --message "Fix: error en calculation de precios"

# OTA a operations
eas update --branch production --message "Hotfix: corregir login loop"
```

### Configuration de canales

```json
// app.json — updates config
{
  "expo": {
    "updates": {
      "url": "https://u.expo.dev/YOUR_PROJECT_ID",
      "fallbackToCacheTimeout": 0,
      "requestHeaders": {
        "expo-channel-name": "production"
      }
    },
    "runtimeVersion": {
      "policy": "appVersion"
    }
  }
}
```

---

## 8. Versionado

### Esquema de versiones

```
v{MAJOR}.{MINOR}.{PATCH}

MAJOR: Breaking changes o redesign completo
MINOR: Nuevas features (sin romper nada)
PATCH: Bug fixes y mejoras menores

Examples:
  v1.0.0 — Lanzamiento inicial
  v1.1.0 — Nuevo module de quotes offline
  v1.1.1 — Fix en calculation de saldo
  v2.0.0 — Redesign de UI
```

### Bump de version antes de release

```bash
# Patch (bug fix)
npm version patch  # 1.0.0 → 1.0.1

# Minor (new feature)
npm version minor  # 1.0.1 → 1.1.0

# Major
npm version major  # 1.1.0 → 2.0.0

# Esto actualiza package.json + crea git tag automaticmente
```

### Build numbers automatics (EAS)

EAS puede incrementar automaticmente `versionCode` (Android) y `buildNumber` (iOS):

```json
// eas.json
{
  "build": {
    "production": {
      "autoIncrement": true
    }
  }
}
```

---

## 9. Distribution Interna (TestFlight / Internal Testing)

### Android — Internal Testing Track

```bash
# Build APK para distribution interna
eas build --profile staging --platform android

# Subir a Google Play Internal Testing (automatic con CI)
# O distribuir APK directamente via EAS
eas build --profile staging --platform android
# Compartir link de installation desde expo.dev
```

### iOS — TestFlight

```bash
# Build IPA para TestFlight
eas build --profile staging --platform ios

# Submit a TestFlight
eas submit --profile staging --platform ios --latest

# Agregar testers en App Store Connect
```

### Distribution directa con EAS

Para testers sin cuentas de tienda:

```bash
# Build con distribution interna
# En eas.json: "distribution": "internal"

# Compartir link de installation
# Output del build incluye: https://expo.dev/artifacts/eas/...

# iOS: requiere UDID del dispositivo registrado
eas device:create  # Registrar nuevo dispositivo
```

---

*Creado: 2026-02-19*
*Actualizado: 2026-02-23*
