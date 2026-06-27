# MOBILE_ARCHITECTURE.md — SaaS Template Mobile

> Arquitectura escalable por features para la app mobile de SaaS Template.

---

## 1. Principios de Arquitectura

| Principio | Application |
|-----------|-----------|
| **Feature-first** | El code se organiza por dominio de negocio, no por tipo de file |
| **Separation de capas** | UI → Logic → Datos. Cada capa conoce solo la que tiene debajo |
| **Unidirectional data flow** | Estado fluye hacia abajo, eventos suben via callbacks/stores |
| **Server state separado** | React Query para datos del servidor. Zustand para estado UI/local |
| **Sin logic en componentes** | Los componentes renderizan. Los hooks encapsulan la logic |

---

## 2. Estructura de Carpetas

```
saas-template-mobile/
├── app/                          # Expo Router (rutas = files)
│   ├── (auth)/                   # Grupo: pantallas sin authentication
│   │   ├── login.tsx
│   │   ├── forgot-password.tsx
│   │   └── _layout.tsx
│   ├── (app)/                    # Grupo: pantallas autenticadas
│   │   ├── (tabs)/               # Tab navigator
│   │   │   ├── index.tsx         # Dashboard (tab home)
│   │   │   ├── orders.tsx        # Orders
│   │   │   ├── customers.tsx     # Customers
│   │   │   ├── quotes.tsx        # Quotes
│   │   │   └── _layout.tsx       # Tab bar config
│   │   ├── orders/
│   │   │   ├── [id].tsx          # Detalle orden
│   │   │   └── create.tsx        # Nueva orden
│   │   ├── customers/
│   │   │   ├── [id].tsx          # Detalle customer
│   │   │   └── create.tsx        # Nuevo customer
│   │   ├── quotes/
│   │   │   ├── [id].tsx          # Detalle quote
│   │   │   └── create.tsx        # Nueva quote
│   │   └── _layout.tsx           # Auth guard + drawer
│   ├── _layout.tsx               # Root layout (providers)
│   └── +not-found.tsx            # 404
│
├── src/
│   ├── features/                 # Dominio de negocio
│   │   ├── auth/
│   │   │   ├── components/       # LoginForm, etc.
│   │   │   ├── hooks/            # useLogin, useLogout, useAuth
│   │   │   ├── services/         # authService.ts (llamadas API)
│   │   │   ├── stores/           # authStore.ts (Zustand)
│   │   │   └── types.ts          # LoginRequest, AuthUser, etc.
│   │   ├── dashboard/
│   │   │   ├── components/       # StatsCard, RevenueChart
│   │   │   ├── hooks/            # useDashboardStats
│   │   │   └── types.ts
│   │   ├── orders/
│   │   │   ├── components/       # OrderCard, OrderStatusBadge, OrderList
│   │   │   ├── hooks/            # useOrders, useOrder, useCreateOrder
│   │   │   ├── services/         # ordersService.ts
│   │   │   └── types.ts          # Order, OrderLine, OrderStatus
│   │   ├── customers/
│   │   │   ├── components/       # CustomerCard, CustomerSearch
│   │   │   ├── hooks/            # useCustomers, useCustomer
│   │   │   ├── services/         # customersService.ts
│   │   │   └── types.ts
│   │   ├── quotes/
│   │   │   ├── components/       # PricingCalculator, QuoteCard
│   │   │   ├── hooks/            # useQuotes, useCalculatePrice
│   │   │   ├── services/         # quotesService.ts
│   │   │   └── types.ts
│   │   └── notifications/
│   │       ├── hooks/            # usePushNotifications
│   │       ├── services/         # notificationsService.ts
│   │       └── types.ts
│   │
│   ├── lib/                      # Infraestructura compartida
│   │   ├── api/
│   │   │   ├── client.ts         # Instancia Axios configurada
│   │   │   ├── interceptors.ts   # Auth headers, error handling
│   │   │   └── queryClient.ts    # React Query config
│   │   ├── storage/
│   │   │   └── secureStorage.ts  # Wrapper de expo-secure-store
│   │   └── constants/
│   │       ├── queryKeys.ts      # React Query keys tipadas
│   │       ├── routes.ts         # Rutas de navigation tipadas
│   │       └── config.ts         # API URL, timeouts, etc.
│   │
│   ├── components/               # UI compartido
│   │   ├── ui/
│   │   │   ├── Button.tsx        # Button con variants
│   │   │   ├── Card.tsx          # Tarjeta base
│   │   │   ├── Badge.tsx         # Status badges
│   │   │   ├── LoadingSpinner.tsx
│   │   │   ├── EmptyState.tsx
│   │   │   └── ErrorMessage.tsx
│   │   ├── forms/
│   │   │   ├── FormInput.tsx     # Input + label + error
│   │   │   ├── FormSelect.tsx
│   │   │   └── FormDatePicker.tsx
│   │   └── layout/
│   │       ├── Screen.tsx        # Wrapper con SafeArea
│   │       ├── Header.tsx
│   │       └── TabBar.tsx
│   │
│   ├── hooks/                    # Hooks globales
│   │   ├── useDebounce.ts
│   │   ├── useRefreshOnFocus.ts
│   │   └── useAppState.ts
│   │
│   └── types/                    # Types globales
│       ├── api.ts                # ApiResponse<T>, PaginatedResponse<T>
│       └── navigation.ts         # RootStackParamList
│
├── assets/                       # Images, fuentes, iconos
│   ├── fonts/
│   ├── images/
│   └── icons/
│
├── __tests__/                    # Tests globales
│   └── setup.ts
│
├── app.json
├── eas.json
├── package.json
├── tsconfig.json
└── .env.development
```

---

## 3. Capas de la Application

```
┌──────────────────────────────────────────┐
│             PRESENTATION LAYER            │
│   app/ routes + src/components/ui/       │
│   Expo Router screens + shared UI        │
└─────────────────┬────────────────────────┘
                  │ usa
┌─────────────────▼────────────────────────┐
│              FEATURE LAYER                │
│   src/features/*/hooks/ + components/    │
│   Custom hooks, feature-specific UI      │
└─────────────────┬────────────────────────┘
                  │ usa
┌─────────────────▼────────────────────────┐
│              SERVICE LAYER                │
│   src/features/*/services/               │
│   Llamadas HTTP, transformaciones        │
└─────────────────┬────────────────────────┘
                  │ usa
┌─────────────────▼────────────────────────┐
│           INFRASTRUCTURE LAYER            │
│   src/lib/api/client.ts                  │
│   Axios instance, interceptors           │
└──────────────────────────────────────────┘
```

### Regla de dependencias

- `app/` conoce `src/features/` y `src/components/`
- `src/features/` conoce `src/lib/` y `src/components/`
- `src/lib/` no conoce `src/features/` (no hay imports circulares)
- `src/components/` no conoce `src/features/`

---

## 4. Management of Estado

### Dos tipos de estado

| Tipo | Herramienta | Examples |
|------|------------|---------|
| **Server state** | React Query | Orders, customers, precios del backend |
| **UI/local state** | Zustand | User autenticado, tenant activo, filtros, modal open |

### Zustand — stores globales

```typescript
// src/features/auth/stores/authStore.ts
interface AuthState {
  user: AuthUser | null
  tenant: Tenant | null
  token: string | null
  isAuthenticated: boolean
  setAuth: (user: AuthUser, tenant: Tenant, token: string) => void
  clearAuth: () => void
  setTenant: (tenant: Tenant) => void
}

// Persistir token en SecureStore, no en memoria
// Ver AUTHENTICATION_FLOW.md para implementation completa
```

### React Query — server state

```typescript
// src/features/orders/hooks/useOrders.ts
export function useOrders(filters?: OrderFilters) {
  return useQuery({
    queryKey: queryKeys.orders.list(filters),
    queryFn: () => ordersService.getOrders(filters),
    staleTime: 30 * 1000,  // 30 segundos
  })
}

// src/features/orders/hooks/useUpdateOrderStatus.ts
export function useUpdateOrderStatus() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, status }: UpdateStatusParams) =>
      ordersService.updateStatus(id, status),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.orders.detail(id) })
      queryClient.invalidateQueries({ queryKey: queryKeys.orders.lists() })
    },
  })
}
```

---

## 5. Navigation con Expo Router

### Flujo de authentication

```typescript
// app/_layout.tsx
export default function RootLayout() {
  const { isAuthenticated, isLoading } = useAuthStore()

  if (isLoading) return <SplashScreen />

  return (
    <Stack>
      {isAuthenticated ? (
        <Stack.Screen name="(app)" options={{ headerShown: false }} />
      ) : (
        <Stack.Screen name="(auth)" options={{ headerShown: false }} />
      )}
    </Stack>
  )
}
```

### Rutas tipadas

```typescript
// src/lib/constants/routes.ts
export const Routes = {
  AUTH: {
    LOGIN: '/(auth)/login',
    FORGOT_PASSWORD: '/(auth)/forgot-password',
  },
  APP: {
    DASHBOARD: '/(app)/(tabs)/',
    ORDERS: '/(app)/(tabs)/orders',
    ORDER_DETAIL: (id: number) => `/(app)/orders/${id}`,
    ORDER_CREATE: '/(app)/orders/create',
    CUSTOMERS: '/(app)/(tabs)/customers',
    CUSTOMER_DETAIL: (id: number) => `/(app)/customers/${id}`,
    QUOTES: '/(app)/(tabs)/quotes',
    QUOTE_CREATE: '/(app)/quotes/create',
  },
} as const

// Uso:
router.push(Routes.APP.ORDER_DETAIL(orderId))
```

---

## 6. Patrones de Componentes

### Screen wrapper (SafeArea + padding)

```typescript
// src/components/layout/Screen.tsx
interface ScreenProps {
  children: React.ReactNode
  title?: string
  scrollable?: boolean
  padding?: boolean
}

export function Screen({ children, scrollable = true, padding = true }: ScreenProps) {
  const Component = scrollable ? ScrollView : View
  return (
    <SafeAreaView style={styles.safe}>
      <Component style={[styles.container, padding && styles.padding]}>
        {children}
      </Component>
    </SafeAreaView>
  )
}
```

### Feature hook con loading/error

```typescript
// src/features/orders/hooks/useOrders.ts
export function useOrders() {
  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: queryKeys.orders.list(),
    queryFn: ordersService.getOrders,
  })

  return {
    orders: data?.data ?? [],
    isLoading,
    isError,
    refetch,
  }
}
```

### Service layer limpio

```typescript
// src/features/orders/services/ordersService.ts
export const ordersService = {
  getOrders: async (filters?: OrderFilters): Promise<PaginatedResponse<Order>> => {
    const { data } = await apiClient.get('/orders', { params: filters })
    return data
  },
  getOrder: async (id: number): Promise<Order> => {
    const { data } = await apiClient.get(`/orders/${id}`)
    return data.data
  },
  updateStatus: async (id: number, status: OrderStatus): Promise<Order> => {
    const { data } = await apiClient.patch(`/orders/${id}/status`, { status })
    return data.data
  },
}
```

---

## 7. Convenciones de Code

### Nomenclatura

| Tipo | Convention | Example |
|------|-----------|---------|
| Componentes | PascalCase | `OrderCard.tsx` |
| Hooks | camelCase con `use` | `useOrders.ts` |
| Services | camelCase con `Service` | `ordersService.ts` |
| Stores | camelCase con `Store` | `authStore.ts` |
| Types | PascalCase | `Order`, `OrderStatus` |
| Constantes | UPPER_SNAKE_CASE | `MAX_RETRY_COUNT` |
| Routes | `app/` file-based | `app/(app)/orders/[id].tsx` |

### Exports

```typescript
// SIEMPRE named exports en features
export function OrderCard() { ... }
export function useOrders() { ... }

// Barrel exports en index.ts de features
// src/features/orders/index.ts
export * from './components/OrderCard'
export * from './hooks/useOrders'
export * from './types'
```

---

## 8. Tipos Globales de API

```typescript
// src/types/api.ts

export interface ApiResponse<T> {
  data: T
  message?: string
}

export interface PaginatedResponse<T> {
  data: T[]
  count: number
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
}
```

---

*Creado: 2026-02-19*
*Actualizado: 2026-02-23*
