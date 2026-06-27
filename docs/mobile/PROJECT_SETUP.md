# PROJECT_SETUP.md — SaaS Template Mobile

> Setup completo del proyecto React Native desde cero.

---

## 1. Decision: Expo vs React Native CLI

### Comparativa

| Criterio | Expo (Managed) | Expo (Bare) | RN CLI |
|---------|---------------|-------------|--------|
| Setup inicial | 5 min | 15 min | 45 min |
| OTA updates | Si (EAS Update) | Si | No nativo |
| Build en la nube | Si (EAS Build) | Si | No |
| Acceso nativo | Limitado | Completo | Completo |
| Push notifications | expo-notifications | expo-notifications | Firebase manual |
| Camera/Files | expo-camera | expo-camera | react-native-vision-camera |
| Firma de apps | EAS (automatic) | EAS (automatic) | Manual (keystore) |
| Ideal para | 90% de apps | Apps con modules nativos | Apps 100% nativas |

### Decision: **Expo SDK con Bare Workflow**

**Justification:**
- SaaS Template no necesita modules nativos exotic
- EAS Build elimina la friction de firmar apps
- OTA updates permiten corregir bugs sin pasar por revision de stores
- Expo Router simplifica la navigation tipo file-based (similar a Next.js)
- El equipo puede desarrollar sin Xcode/Android Studio en el day a day
- Si en el futuro se necesita un module nativo: `expo prebuild` genera el proyecto nativo

---

## 2. Prerequisitos

### Node y herramientas base

```bash
# Instalar NVM (manejo de versiones Node)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash

# Instalar Node LTS
nvm install --lts
nvm use --lts
node --version  # v22.x.x

# Check npm
npm --version  # 10.x.x
```

### Expo CLI y EAS CLI

```bash
# Instalar globales
npm install -g expo-cli eas-cli

# Check
expo --version   # 0.x.x
eas --version    # 10.x.x
```

### Android (para desarrollo local)

```bash
# macOS: instalar Android Studio
# https://developer.android.com/studio

# Configurar variables de environment (~/.zshrc o ~/.bashrc)
export ANDROID_HOME=$HOME/Library/Android/sdk
export PATH=$PATH:$ANDROID_HOME/emulator
export PATH=$PATH:$ANDROID_HOME/platform-tools

# Check
adb --version
```

### iOS (solo macOS)

```bash
# Instalar Xcode desde App Store (macOS only)
# Luego instalar simuladores desde Xcode > Settings > Platforms

# Herramientas de line de commands
xcode-select --install

# CocoaPods (para bare workflow)
sudo gem install cocoapods
pod --version
```

### Expo Go (testing fast)

Instalar en el dispositivo physical:
- **iOS:** [App Store - Expo Go](https://apps.apple.com/app/expo-go/id982107779)
- **Android:** [Play Store - Expo Go](https://play.google.com/store/apps/details?id=host.exp.exponent)

---

## 3. Crear el Proyecto

```bash
# Crear proyecto con template TypeScript
npx create-expo-app@latest saas-template-mobile --template

# Seleccionar: "Blank (TypeScript)" o usar directamente:
npx create-expo-app@latest saas-template-mobile -t expo-template-blank-typescript

# Entrar al proyecto
cd saas-template-mobile

# Check estructura inicial
ls -la
```

### Initializar repositorio Git

```bash
git init
git add .
git commit -m "feat: initial Expo TypeScript setup"
```

---

## 4. Instalar Libraries Base

### Installation en bloque

```bash
# Navigation (Expo Router — file-based routing)
npx expo install expo-router react-native-safe-area-context react-native-screens

# Estado global y fetching
npm install zustand @tanstack/react-query axios

# Almacenamiento seguro (tokens)
npx expo install expo-secure-store @react-native-async-storage/async-storage

# UI Components
npm install react-native-paper react-native-vector-icons
npx expo install expo-status-bar expo-font

# Formularios y validation
npm install react-hook-form @hookform/resolvers zod

# Fechas y utilidades
npm install date-fns

# Notificaciones push
npx expo install expo-notifications expo-device expo-constants

# Images y media
npx expo install expo-image-picker expo-file-system expo-sharing

# Links externos (Messaging, phone)
npx expo install expo-linking

# Dev tools
npm install --save-dev @types/react @types/react-native typescript
npm install --save-dev jest jest-expo @testing-library/react-native
npm install --save-dev eslint @typescript-eslint/eslint-plugin prettier
```

### Table de dependencias principales

| Library | Version | Purpose |
|---------|---------|-----------|
| `expo` | ~51.x | Core framework |
| `expo-router` | ~3.x | Navigation file-based |
| `react-native-paper` | ~5.x | Componentes Material UI |
| `zustand` | ^4.x | Estado global (ligero) |
| `@tanstack/react-query` | ^5.x | Server state, caching, loading |
| `axios` | ^1.x | Customer HTTP |
| `expo-secure-store` | ~13.x | Tokens con encryption |
| `react-hook-form` | ^7.x | Formularios performativos |
| `zod` | ^3.x | Validation de esquemas |
| `date-fns` | ^3.x | Manipulation de fechas |
| `expo-notifications` | ~0.28.x | Push notifications |

---

## 5. Configuration de Expo

### app.json

```json
{
  "expo": {
    "name": "SaaS Template",
    "slug": "saas-template-mobile",
    "version": "1.0.0",
    "orientation": "portrait",
    "icon": "./assets/icon.png",
    "userInterfaceStyle": "light",
    "splash": {
      "image": "./assets/splash.png",
      "resizeMode": "contain",
      "backgroundColor": "#1a1a2e"
    },
    "assetBundlePatterns": ["**/*"],
    "ios": {
      "supportsTablet": false,
      "bundleIdentifier": "mx.saas-template.mobile",
      "buildNumber": "1"
    },
    "android": {
      "adaptiveIcon": {
        "foregroundImage": "./assets/adaptive-icon.png",
        "backgroundColor": "#1a1a2e"
      },
      "package": "mx.saas-template.mobile",
      "versionCode": 1,
      "permissions": [
        "CAMERA",
        "READ_EXTERNAL_STORAGE",
        "WRITE_EXTERNAL_STORAGE",
        "RECEIVE_BOOT_COMPLETED",
        "VIBRATE"
      ]
    },
    "web": {
      "favicon": "./assets/favicon.png"
    },
    "plugins": [
      "expo-router",
      "expo-secure-store",
      [
        "expo-notifications",
        {
          "icon": "./assets/notification-icon.png",
          "color": "#1a1a2e"
        }
      ]
    ],
    "scheme": "saas-template",
    "extra": {
      "eas": {
        "projectId": "YOUR_EAS_PROJECT_ID"
      }
    }
  }
}
```

### Variables de environment (.env)

```bash
# .env.development
EXPO_PUBLIC_API_URL=http://localhost:8000/api
EXPO_PUBLIC_APP_ENV=development

# .env.staging
EXPO_PUBLIC_API_URL=https://your-app.example.com/api
EXPO_PUBLIC_APP_ENV=staging

# .env.production
EXPO_PUBLIC_API_URL=https://app.saas-template.mx/api
EXPO_PUBLIC_APP_ENV=production
```

> **IMPORTANTE:** Variables con prefijo `EXPO_PUBLIC_` son accesibles en el bundle. Nunca poner secrets here. Los tokens van en `expo-secure-store`.

### TypeScript (tsconfig.json)

```json
{
  "extends": "expo/tsconfig.base",
  "compilerOptions": {
    "strict": true,
    "baseUrl": ".",
    "paths": {
      "@/*": ["src/*"],
      "@/components/*": ["src/components/*"],
      "@/features/*": ["src/features/*"],
      "@/lib/*": ["src/lib/*"],
      "@/stores/*": ["src/stores/*"]
    }
  }
}
```

### ESLint y Prettier

```json
// .eslintrc.js
module.exports = {
  extends: [
    "expo",
    "@typescript-eslint/recommended"
  ],
  rules: {
    "@typescript-eslint/no-unused-vars": "error",
    "@typescript-eslint/explicit-function-return-type": "off",
    "no-console": ["warn", { "allow": ["warn", "error"] }]
  }
}

// .prettierrc
{
  "semi": false,
  "singleQuote": true,
  "tabWidth": 2,
  "trailingComma": "es5",
  "printWidth": 100
}
```

---

## 6. Initializar EAS

```bash
# Login a Expo account
eas login

# Initializar proyecto EAS
eas init

# Configurar builds (genera eas.json)
eas build:configure
```

### eas.json resultante

```json
{
  "cli": {
    "version": ">= 10.0.0"
  },
  "build": {
    "development": {
      "developmentClient": true,
      "distribution": "internal",
      "env": {
        "EXPO_PUBLIC_APP_ENV": "development",
        "EXPO_PUBLIC_API_URL": "http://localhost:8000/api"
      }
    },
    "staging": {
      "distribution": "internal",
      "env": {
        "EXPO_PUBLIC_APP_ENV": "staging",
        "EXPO_PUBLIC_API_URL": "https://your-app.example.com/api"
      }
    },
    "production": {
      "distribution": "store",
      "env": {
        "EXPO_PUBLIC_APP_ENV": "production",
        "EXPO_PUBLIC_API_URL": "https://app.saas-template.mx/api"
      }
    }
  },
  "submit": {
    "production": {
      "ios": {
        "appleId": "dev@saas-template.mx",
        "ascAppId": "YOUR_APP_STORE_ID",
        "appleTeamId": "YOUR_TEAM_ID"
      },
      "android": {
        "serviceAccountKeyPath": "./google-play-key.json",
        "track": "production"
      }
    }
  }
}
```

---

## 7. Primer Arranque

```bash
# Modo desarrollo (Expo Go)
npx expo start

# Android emulador
npx expo start --android

# iOS simulador (solo macOS)
npx expo start --ios

# Con limpieza de cache
npx expo start --clear
```

### Scripts en package.json

```json
{
  "scripts": {
    "start": "expo start",
    "android": "expo start --android",
    "ios": "expo start --ios",
    "web": "expo start --web",
    "test": "jest --watchAll",
    "test:ci": "jest --ci --coverage",
    "lint": "eslint src --ext .ts,.tsx",
    "lint:fix": "eslint src --ext .ts,.tsx --fix",
    "format": "prettier --write 'src/**/*.{ts,tsx}'",
    "type-check": "tsc --noEmit",
    "build:staging": "eas build --profile staging --platform all",
    "build:prod": "eas build --profile production --platform all",
    "submit:prod": "eas submit --profile production --platform all",
    "update": "eas update --branch production --message"
  }
}
```

---

## 8. Verification del Setup

```bash
# Check que todo compila
npx tsc --noEmit

# Ejecutar linter
npm run lint

# Correr tests
npm run test:ci

# Check que la app arranca
npx expo start --clear
```

Lista de verification:
- [ ] `expo start` abre sin errores
- [ ] App carga en Expo Go o emulador
- [ ] TypeScript compila sin errores
- [ ] ESLint pasa sin errores
- [ ] Tests iniciales pasan
- [ ] `eas whoami` muestra tu cuenta

---

*Creado: 2026-02-19*
*Actualizado: 2026-02-23*
