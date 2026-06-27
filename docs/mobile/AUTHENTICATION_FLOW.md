# AUTHENTICATION_FLOW.md — SaaS Template Mobile

> Login, tokens, session persistente y multi-tenant para la app mobile.

---

## 1. Arquitectura de Authentication

```
┌─────────────────────────────────────────────────────────┐
│                     MOBILE APP                           │
│                                                          │
│  LoginScreen ──────────────────────────────────────────► │
│                    │                                     │
│              useLogin hook                               │
│                    │                                     │
│              authService.login()                         │
│                    │                                     │
│              POST /api/login (Sanctum)                   │
│                    │                                     │
│              ◄── { user, tenants, token }                │
│                    │                                     │
│         ┌──────────▼──────────────┐                     │
│         │    1 tenant?             │                     │
│         │  auto-select tenant     │                     │
│         │                         │                     │
│         │    N tenants?            │                     │
│         │  TenantSelector screen  │                     │
│         └──────────┬──────────────┘                     │
│                    │                                     │
│         Token → SecureStore                             │
│         User+Tenant → Zustand store                     │
│                    │                                     │
│         Redirect → (app)/(tabs)/                        │
└─────────────────────────────────────────────────────────┘
```

---

## 2. Backend: Laravel Sanctum

El backend usa **Laravel Sanctum** para authentication API con tokens.

### Endpoints necesarios (por implementar para mobile)

```
POST /api/auth/login          -- Autenticar user, obtener token
POST /api/auth/logout         -- Revocar token actual
GET  /api/auth/me             -- Obtener user autenticado + tenants
POST /api/auth/refresh        -- Renovar token (si implementado)
```

> **Note:** El backend ya soporta Sanctum tokens para la API v1 (`/api/v1/*`). Los endpoints de auth specifics para mobile (login, logout, me) necesitan ser creados. Con Sanctum, los tokens no expiran por defecto.

### Endpoints v1 existentes (requieren auth:sanctum)

```
GET  /api/v1/orders           -- Listar orders del tenant
GET  /api/v1/orders/{id}      -- Detalle de orden
POST /api/v1/orders           -- Crear orden
GET  /api/v1/customers        -- Listar customers del tenant
GET  /api/v1/customers/{id}   -- Detalle de customer
GET  /api/v1/products         -- Listar items del tenant
GET  /api/v1/products/{id}    -- Detalle de item
GET  /api/v1/payments         -- Listar payments del tenant
GET  /api/v1/payments/{id}    -- Detalle de payment
```

> **Note:** Los endpoints v1 requieren header `X-Tenant-ID`, middleware `api.tenant` y plan Pro (`api.pro`). Rate limited con `throttle:api-tenant`.

### Response de login

```json
{
  "user": {
    "id": 1,
    "name": "Juan Perez",
    "email": "juan@saas-template.com",
    "role": "owner"
  },
  "tenants": [
    {
      "id": 1,
      "name": "SaaS Template",
      "slug": "saas-template",
      "plan": "growth",
      "is_active": true
    }
  ],
  "token": "1|AbCdEfGhIjKlMnOp...",
  "token_type": "Bearer"
}
```

---

## 3. Almacenamiento Seguro de Tokens

### Por what SecureStore y no AsyncStorage

| Aspecto | AsyncStorage | SecureStore |
|---------|-------------|-------------|
| Cifrado | No | Si (Keychain iOS / Keystore Android) |
| Acceso jailbreak | Vulnerable | Protegido |
| Uso correcto para tokens | No | Si |
| API | Async | Async |

```typescript
// src/lib/storage/secureStorage.ts
import * as SecureStore from 'expo-secure-store'

const KEYS = {
  AUTH_TOKEN: 'saas-template_auth_token',
  TENANT_ID: 'saas-template_tenant_id',
  USER_ID: 'saas-template_user_id',
} as const

export const secureStorage = {
  async setToken(token: string): Promise<void> {
    await SecureStore.setItemAsync(KEYS.AUTH_TOKEN, token)
  },

  async getToken(): Promise<string | null> {
    return SecureStore.getItemAsync(KEYS.AUTH_TOKEN)
  },

  async removeToken(): Promise<void> {
    await SecureStore.deleteItemAsync(KEYS.AUTH_TOKEN)
  },

  async setTenantId(tenantId: number): Promise<void> {
    await SecureStore.setItemAsync(KEYS.TENANT_ID, String(tenantId))
  },

  async getTenantId(): Promise<number | null> {
    const value = await SecureStore.getItemAsync(KEYS.TENANT_ID)
    return value ? parseInt(value, 10) : null
  },

  async clearAll(): Promise<void> {
    await Promise.all([
      SecureStore.deleteItemAsync(KEYS.AUTH_TOKEN),
      SecureStore.deleteItemAsync(KEYS.TENANT_ID),
      SecureStore.deleteItemAsync(KEYS.USER_ID),
    ])
  },
}
```

---

## 4. Auth Store (Zustand)

```typescript
// src/features/auth/stores/authStore.ts
import { create } from 'zustand'
import { secureStorage } from '@/lib/storage/secureStorage'

interface AuthUser {
  id: number
  name: string
  email: string
  role: string
}

interface Tenant {
  id: number
  name: string
  slug: string
  plan: 'starter' | 'growth' | 'pro'
  is_active: boolean
}

interface AuthState {
  user: AuthUser | null
  tenant: Tenant | null
  availableTenants: Tenant[]
  isAuthenticated: boolean
  isLoading: boolean

  // Actions
  initialize: () => Promise<void>
  setAuth: (user: AuthUser, tenant: Tenant, tenants: Tenant[], token: string) => Promise<void>
  switchTenant: (tenant: Tenant) => Promise<void>
  logout: () => Promise<void>
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  tenant: null,
  availableTenants: [],
  isAuthenticated: false,
  isLoading: true,

  // Al arrancar la app: restaurar session desde SecureStore
  initialize: async () => {
    try {
      const token = await secureStorage.getToken()
      if (!token) {
        set({ isLoading: false })
        return
      }

      // Check token con el backend
      const { user, tenants } = await authService.me()
      const tenantId = await secureStorage.getTenantId()
      const activeTenant = tenants.find((t) => t.id === tenantId) ?? tenants[0]

      set({
        user,
        tenant: activeTenant,
        availableTenants: tenants,
        isAuthenticated: true,
        isLoading: false,
      })
    } catch {
      // Token invalid o expirado — limpiar
      await secureStorage.clearAll()
      set({ isLoading: false })
    }
  },

  setAuth: async (user, tenant, tenants, token) => {
    await secureStorage.setToken(token)
    await secureStorage.setTenantId(tenant.id)
    set({
      user,
      tenant,
      availableTenants: tenants,
      isAuthenticated: true,
    })
  },

  switchTenant: async (tenant) => {
    await secureStorage.setTenantId(tenant.id)
    set({ tenant })
    // Clear cache de React Query al cambiar tenant
    queryClient.clear()
  },

  logout: async () => {
    try {
      await authService.logout()
    } catch {
      // Ignorar errores de red al hacer logout
    } finally {
      await secureStorage.clearAll()
      queryClient.clear()
      set({
        user: null,
        tenant: null,
        availableTenants: [],
        isAuthenticated: false,
      })
    }
  },
}))
```

---

## 5. Auth Service

```typescript
// src/features/auth/services/authService.ts
import { apiClient } from '@/lib/api/client'

export interface LoginRequest {
  email: string
  password: string
}

export interface LoginResponse {
  user: AuthUser
  tenants: Tenant[]
  token: string
  token_type: string
}

export const authService = {
  login: async (credentials: LoginRequest): Promise<LoginResponse> => {
    const { data } = await apiClient.post<LoginResponse>('/auth/login', credentials)
    return data
  },

  logout: async (): Promise<void> => {
    await apiClient.post('/auth/logout')
  },

  me: async (): Promise<{ user: AuthUser; tenants: Tenant[] }> => {
    const { data } = await apiClient.get('/auth/me')
    return data
  },
}
```

---

## 6. Hook de Login

```typescript
// src/features/auth/hooks/useLogin.ts
import { useMutation } from '@tanstack/react-query'
import { useAuthStore } from '../stores/authStore'
import { authService } from '../services/authService'
import { router } from 'expo-router'

export function useLogin() {
  const setAuth = useAuthStore((s) => s.setAuth)

  return useMutation({
    mutationFn: authService.login,
    onSuccess: async (response) => {
      const { user, tenants, token } = response

      if (tenants.length === 0) {
        // Sin tenants — error de configuration
        throw new Error('No tienes acceso a no negocio')
      }

      if (tenants.length === 1) {
        // Auto-seleccionar el unique tenant
        await setAuth(user, tenants[0], tenants, token)
        router.replace('/(app)/(tabs)/')
      } else {
        // Guardar temporalmente y mostrar selector
        await setAuth(user, tenants[0], tenants, token)
        router.replace('/(auth)/select-tenant')
      }
    },
    onError: (error) => {
      // El error se maneja en el componente
    },
  })
}
```

---

## 7. Pantalla de Login

```typescript
// app/(auth)/login.tsx
import { useForm, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useLogin } from '@/features/auth'

const loginSchema = z.object({
  email: z.string().email('Email invalid'),
  password: z.string().min(8, 'Minimum 8 caracteres'),
})

type LoginForm = z.infer<typeof loginSchema>

export default function LoginScreen() {
  const { mutate: login, isPending, error } = useLogin()

  const { control, handleSubmit, formState: { errors } } = useForm<LoginForm>({
    resolver: zodResolver(loginSchema),
  })

  const onSubmit = (data: LoginForm) => login(data)

  return (
    <Screen padding scrollable={false}>
      <Image source={require('@/assets/images/logo.png')} style={styles.logo} />
      <Text variant="headlineMedium" style={styles.title}>
        Iniciar session
      </Text>

      <Controller
        control={control}
        name="email"
        render={({ field: { onChange, value } }) => (
          <FormInput
            label="Email"
            value={value}
            onChangeText={onChange}
            keyboardType="email-address"
            autoCapitalize="none"
            error={errors.email?.message}
          />
        )}
      />

      <Controller
        control={control}
        name="password"
        render={({ field: { onChange, value } }) => (
          <FormInput
            label="Password"
            value={value}
            onChangeText={onChange}
            secureTextEntry
            error={errors.password?.message}
          />
        )}
      />

      {error && (
        <ErrorMessage message="Email o password incorrectos" />
      )}

      <Button
        mode="contained"
        onPress={handleSubmit(onSubmit)}
        loading={isPending}
        disabled={isPending}
      >
        Entrar
      </Button>
    </Screen>
  )
}
```

---

## 8. Guard de Authentication

```typescript
// app/_layout.tsx
import { useEffect } from 'react'
import { Stack } from 'expo-router'
import { useAuthStore } from '@/features/auth'

export default function RootLayout() {
  const { isAuthenticated, isLoading, initialize } = useAuthStore()

  // Initializar session al arrancar
  useEffect(() => {
    initialize()
  }, [])

  if (isLoading) {
    return <SplashScreen />
  }

  return (
    <Stack screenOptions={{ headerShown: false }}>
      {isAuthenticated ? (
        <Stack.Screen name="(app)" />
      ) : (
        <Stack.Screen name="(auth)" />
      )}
    </Stack>
  )
}
```

---

## 9. Multi-Tenant

### Selector de tenant

```typescript
// app/(auth)/select-tenant.tsx
export default function SelectTenantScreen() {
  const { availableTenants, switchTenant } = useAuthStore()
  const router = useRouter()

  const handleSelect = async (tenant: Tenant) => {
    await switchTenant(tenant)
    router.replace('/(app)/(tabs)/')
  }

  return (
    <Screen>
      <Text variant="headlineSmall">What negocio quieres gestionar?</Text>
      {availableTenants.map((tenant) => (
        <TenantCard
          key={tenant.id}
          tenant={tenant}
          onPress={() => handleSelect(tenant)}
        />
      ))}
    </Screen>
  )
}
```

### Cambio de tenant desde la app

El tenant activo se muestra en el header. Si el user pertenece a more de un tenant, puede cambiar desde el menu de perfil:

```typescript
// src/features/auth/components/TenantSwitcher.tsx
export function TenantSwitcher() {
  const { tenant, availableTenants, switchTenant } = useAuthStore()

  if (availableTenants.length <= 1) return null

  return (
    <Menu>
      <Menu.Anchor>
        <Chip icon="store">{tenant?.name}</Chip>
      </Menu.Anchor>
      {availableTenants.map((t) => (
        <Menu.Item
          key={t.id}
          title={t.name}
          onPress={() => switchTenant(t)}
          leadingIcon={t.id === tenant?.id ? 'check' : 'store'}
        />
      ))}
    </Menu>
  )
}
```

---

## 10. Flujo de Token en Requests

El token se inyecta automaticmente en cada request via interceptor de Axios. Ver `API_INTEGRATION.md` para implementation completa.

### Flujo de error 401

```
Request → Backend responde 401 (token invalid/expirado)
    │
    ▼
Interceptor detecta 401
    │
    ▼
authStore.logout() — limpiar SecureStore + estado
    │
    ▼
Expo Router detecta isAuthenticated = false
    │
    ▼
Redirect automatic a /(auth)/login
```

---

## 11. Roles y Permisos en Mobile

El rol del user determina what actions son visibles en la app.

```typescript
// src/features/auth/hooks/usePermissions.ts
export function usePermissions() {
  const role = useAuthStore((s) => s.user?.role)

  return {
    canCreateOrders: ['owner', 'admin', 'sales'].includes(role ?? ''),
    canManageProduction: ['owner', 'admin', 'operations'].includes(role ?? ''),
    canViewFinancials: ['owner', 'admin', 'finance'].includes(role ?? ''),
    canManageProducts: ['owner', 'admin'].includes(role ?? ''),
    isOwner: role === 'owner',
  }
}

// Uso en componente:
const { canCreateOrders } = usePermissions()
{canCreateOrders && <FAB onPress={navigateToCreate} />}
```

---

*Creado: 2026-02-19*
*Actualizado: 2026-02-23*
