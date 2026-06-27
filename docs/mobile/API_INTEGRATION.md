# API_INTEGRATION.md — SaaS Template Mobile

> Customer HTTP, manejo de tokens, interceptors y modo offline.

---

## 1. Instancia Axios

```typescript
// src/lib/api/client.ts
import axios from 'axios'
import Constants from 'expo-constants'

const API_URL = process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8000/api'

export const apiClient = axios.create({
  baseURL: API_URL,
  timeout: 15_000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-App-Version': Constants.expoConfig?.version ?? '1.0.0',
    'X-Platform': Platform.OS,
  },
})
```

---

## 2. Interceptors

### Request — inyectar token + tenant

```typescript
// src/lib/api/interceptors.ts
import { secureStorage } from '@/lib/storage/secureStorage'

// Request interceptor
apiClient.interceptors.request.use(
  async (config) => {
    const token = await secureStorage.getToken()
    const tenantId = await secureStorage.getTenantId()

    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    if (tenantId) {
      config.headers['X-Tenant-ID'] = String(tenantId)
    }

    return config
  },
  (error) => Promise.reject(error)
)
```

### Response — manejo de errores globales

```typescript
// Response interceptor
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status

    if (status === 401) {
      // Token invalid o expirado
      // Limpiar session y redirigir al login
      const { logout } = useAuthStore.getState()
      await logout()
      // Expo Router will detect isAuthenticated = false y will redirect
      return Promise.reject(error)
    }

    if (status === 403) {
      // Sin permisos para esta action
      return Promise.reject(new PermissionError('No tienes permiso para esta action'))
    }

    if (status === 422) {
      // Error de validation — pasar al componente para mostrar
      const validationErrors = error.response.data.errors
      return Promise.reject(new ValidationError(error.response.data.message, validationErrors))
    }

    if (status === 429) {
      // Rate limit — esperar y reintentar
      return Promise.reject(new RateLimitError('Demasiadas requestes. Intenta en un momento.'))
    }

    if (!error.response) {
      // Sin connection
      return Promise.reject(new NetworkError('Sin connection a internet'))
    }

    return Promise.reject(error)
  }
)
```

### Clases de error custom

```typescript
// src/lib/api/errors.ts
export class NetworkError extends Error {
  constructor(message = 'Sin connection a internet') {
    super(message)
    this.name = 'NetworkError'
  }
}

export class ValidationError extends Error {
  constructor(
    message: string,
    public errors: Record<string, string[]>
  ) {
    super(message)
    this.name = 'ValidationError'
  }
}

export class PermissionError extends Error {
  constructor(message = 'Sin permisos') {
    super(message)
    this.name = 'PermissionError'
  }
}

export class RateLimitError extends Error {
  constructor(message: string) {
    super(message)
    this.name = 'RateLimitError'
  }
}
```

---

## 3. React Query Configuration

```typescript
// src/lib/api/queryClient.ts
import { QueryClient } from '@tanstack/react-query'

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Reintentos
      retry: (failureCount, error) => {
        // No reintentar errores de customer (4xx)
        if (error instanceof ValidationError) return false
        if (error instanceof PermissionError) return false
        // Reintentar hasta 2 veces errores de red / servidor
        return failureCount < 2
      },
      retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 10_000),

      // Stale time por defecto
      staleTime: 30 * 1000,       // 30 segundos

      // Refetch automatic
      refetchOnWindowFocus: true,  // Al volver a la pantalla
      refetchOnReconnect: true,    // Al recuperar connection
    },
    mutations: {
      retry: false,  // No reintentar mutaciones
    },
  },
})
```

### Query Keys tipadas

```typescript
// src/lib/constants/queryKeys.ts
export const queryKeys = {
  dashboard: {
    all: ['dashboard'] as const,
    stats: () => [...queryKeys.dashboard.all, 'stats'] as const,
  },

  orders: {
    all: ['orders'] as const,
    lists: () => [...queryKeys.orders.all, 'list'] as const,
    list: (filters?: object) => [...queryKeys.orders.lists(), filters] as const,
    details: () => [...queryKeys.orders.all, 'detail'] as const,
    detail: (id: number) => [...queryKeys.orders.details(), id] as const,
  },

  customers: {
    all: ['customers'] as const,
    lists: () => [...queryKeys.customers.all, 'list'] as const,
    list: (filters?: object) => [...queryKeys.customers.lists(), filters] as const,
    detail: (id: number) => [...queryKeys.customers.all, 'detail', id] as const,
  },

  pricing: {
    all: ['pricing'] as const,
    rules: () => [...queryKeys.pricing.all, 'rules'] as const,
    tiers: (params: object) => [...queryKeys.pricing.all, 'tiers', params] as const,
    calculate: (params: object) => [...queryKeys.pricing.all, 'calculate', params] as const,
  },

  quotes: {
    all: ['quotes'] as const,
    lists: () => [...queryKeys.quotes.all, 'list'] as const,
    list: (filters?: object) => [...queryKeys.quotes.lists(), filters] as const,
    detail: (id: number) => [...queryKeys.quotes.all, 'detail', id] as const,
  },
}
```

---

## 4. Mapeo de Endpoints API

### Endpoints actuales del backend

```typescript
// src/lib/constants/endpoints.ts

// Referencia: docs/api-reference.md del proyecto backend

export const ENDPOINTS = {
  // Health (implementado — GET /api/health con checks de DB, cache, storage)
  HEALTH: '/health',

  // Auth (por implementar en backend para mobile)
  AUTH: {
    LOGIN: '/auth/login',
    LOGOUT: '/auth/logout',
    ME: '/auth/me',
    PUSH_TOKEN: '/auth/push-token',
  },

  // Pricing (implementados — publics, sin auth)
  PRICING: {
    RULES: '/pricing/rules',
    LIMITS: '/pricing/limits',
    TIERS: '/pricing/tiers',
    CALCULATE: '/pricing/calculate',
    SUGGESTIONS: '/pricing/suggestions',
    VALIDATE_DIMENSIONS: '/pricing/validate-dimensions',
  },

  // Products (implementados — publics, sin auth)
  PRODUCTS: {
    LIST: '/products',
    DETAIL: (id: number) => `/products/${id}`,
    CREATE: '/products',
    UPDATE: (id: number) => `/products/${id}`,
    DELETE: (id: number) => `/products/${id}`,
    SIZES: (id: number) => `/products/${id}/sizes`,
  },

  // Orders (implementados — publics, sin auth)
  ORDERS: {
    LIST: '/orders',
    CREATE: '/orders',
    DETAIL: (id: number) => `/orders/${id}`,
    UPDATE_STATUS: (id: number) => `/orders/${id}/status`,
  },

  // Quotes (implementados — publics, sin auth)
  QUOTES: {
    LIST: '/quotes',
    CREATE: '/quotes',
    DETAIL: (id: number) => `/quotes/${id}`,
    PDF: (id: number) => `/quotes/${id}/pdf`,
    GENERATE_PDF: (id: number) => `/quotes/${id}/generate-pdf`,
  },

  // API v1 — Tenant-scoped (requiere auth:sanctum + X-Tenant-ID)
  // Plan Pro required — rate limited (throttle:api-tenant)
  V1: {
    ORDERS: '/v1/orders',
    ORDER_DETAIL: (id: number) => `/v1/orders/${id}`,
    CUSTOMERS: '/v1/customers',
    CUSTOMER_DETAIL: (id: number) => `/v1/customers/${id}`,
    PRODUCTS: '/v1/products',
    PRODUCT_DETAIL: (id: number) => `/v1/products/${id}`,
    PAYMENTS: '/v1/payments',
    PAYMENT_DETAIL: (id: number) => `/v1/payments/${id}`,
  },

  // Dashboard (por implementar para mobile)
  DASHBOARD: {
    STATS: '/dashboard/stats',
  },

  // Customers — API public (por implementar; existe en v1)
  CUSTOMERS: {
    LIST: '/customers',
    DETAIL: (id: number) => `/customers/${id}`,
    CREATE: '/customers',
    UPDATE: (id: number) => `/customers/${id}`,
    ORDERS: (id: number) => `/customers/${id}/orders`,
  },
} as const
```

> **Note:** Los endpoints publics (`/api/pricing/*`, `/api/products/*`, `/api/orders/*`, `/api/quotes/*`) no requieren authentication. Para la app mobile, usar los endpoints V1 (`/api/v1/*`) que requieren `auth:sanctum` + header `X-Tenant-ID` y are limitados al plan Pro. Los endpoints de auth y dashboard stats para mobile are pendientes de implementar.

---

## 5. Services por Feature

### Orders Service

```typescript
// src/features/orders/services/ordersService.ts
import { apiClient } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/constants/endpoints'

export const ordersService = {
  getOrders: async (filters?: {
    status?: string
    pending?: boolean
    active?: boolean
    page?: number
  }): Promise<PaginatedResponse<Order>> => {
    const { data } = await apiClient.get(ENDPOINTS.ORDERS.LIST, {
      params: filters,
    })
    return data
  },

  getOrder: async (id: number): Promise<Order> => {
    const { data } = await apiClient.get(ENDPOINTS.ORDERS.DETAIL(id))
    return data.data
  },

  updateStatus: async (id: number, status: OrderStatus): Promise<Order> => {
    const { data } = await apiClient.patch(ENDPOINTS.ORDERS.UPDATE_STATUS(id), {
      status,
    })
    return data.data
  },

  getPdfUrl: (id: number): string => {
    const baseUrl = process.env.EXPO_PUBLIC_API_URL ?? ''
    return `${baseUrl}${ENDPOINTS.ORDERS.PDF(id)}`
  },
}
```

### Pricing Service

```typescript
// src/features/quotes/services/pricingService.ts
export const pricingService = {
  calculate: async (params: {
    model: string
    size: string
    quantity: number
  }) => {
    const { data } = await apiClient.get(ENDPOINTS.PRICING.CALCULATE, {
      params,
    })
    return data
  },

  getTiers: async (params: { model?: string; size?: string }) => {
    const { data } = await apiClient.get(ENDPOINTS.PRICING.TIERS, { params })
    return data
  },

  getSuggestions: async (params: {
    model: string
    size: string
    quantity: number
  }) => {
    const { data } = await apiClient.get(ENDPOINTS.PRICING.SUGGESTIONS, {
      params,
    })
    return data
  },
}
```

---

## 6. Manejo de Errores en UI

### Hook para extraer mensajes de error

```typescript
// src/lib/api/useApiError.ts
export function useApiError(error: unknown): string | null {
  if (!error) return null

  if (error instanceof ValidationError) {
    // Primer mensaje de validation
    const firstField = Object.values(error.errors)[0]
    return firstField?.[0] ?? error.message
  }

  if (error instanceof NetworkError) {
    return 'Sin connection. Verifica tu internet.'
  }

  if (error instanceof PermissionError) {
    return 'No tienes permiso para esta action.'
  }

  if (error instanceof RateLimitError) {
    return 'Demasiadas requestes. Espera un momento.'
  }

  if (error instanceof Error) {
    return error.message
  }

  return 'Error inesperado. Intenta de nuevo.'
}
```

### Uso en componentes

```typescript
// En cualquier pantalla con mutation
const { mutate, isPending, error } = useMutation(...)
const errorMessage = useApiError(error)

{errorMessage && (
  <Snackbar
    visible={!!errorMessage}
    onDismiss={() => {}}
    duration={4000}
  >
    {errorMessage}
  </Snackbar>
)}
```

---

## 7. Modo Offline Basic

### Estrategia: Optimistic Updates + Cache

React Query maneja automaticmente el cache. Para offline basic:

```typescript
// Datos available offline (desde cache React Query)
// Si no hay connection, React Query sirve datos cacheados
// No se necesita implementation extra para lectura

// Para mutaciones offline — mostrar error claro
export function useUpdateOrderStatus() {
  return useMutation({
    mutationFn: ordersService.updateStatus,
    onError: (error) => {
      if (error instanceof NetworkError) {
        Alert.alert(
          'Sin connection',
          'No se pudo actualizar el status. Verifica tu internet e intenta de nuevo.'
        )
      }
    },
    // Optimistic update para UI more agile
    onMutate: async ({ id, status }) => {
      await queryClient.cancelQueries({ queryKey: queryKeys.orders.detail(id) })
      const previousOrder = queryClient.getQueryData(queryKeys.orders.detail(id))

      queryClient.setQueryData(queryKeys.orders.detail(id), (old: Order) => ({
        ...old,
        status,
      }))

      return { previousOrder }
    },
    onError: (_, { id }, context) => {
      // Revertir optimistic update si falla
      queryClient.setQueryData(queryKeys.orders.detail(id), context?.previousOrder)
    },
    onSettled: (_, __, { id }) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.orders.detail(id) })
    },
  })
}
```

### Indicador de estado de red

```typescript
// src/hooks/useNetworkStatus.ts
import NetInfo from '@react-native-community/netinfo'

export function useNetworkStatus() {
  const [isConnected, setIsConnected] = useState<boolean | null>(true)

  useEffect(() => {
    const unsubscribe = NetInfo.addEventListener((state) => {
      setIsConnected(state.isConnected)
    })
    return unsubscribe
  }, [])

  return { isConnected }
}

// Instalar: npx expo install @react-native-community/netinfo
```

---

## 8. Pagination

```typescript
// Hook con pagination infinita (para listas largas)
export function useInfiniteOrders(filters?: OrderFilters) {
  return useInfiniteQuery({
    queryKey: queryKeys.orders.list(filters),
    queryFn: ({ pageParam = 1 }) =>
      ordersService.getOrders({ ...filters, page: pageParam }),
    getNextPageParam: (lastPage) => {
      const { meta } = lastPage
      if (!meta) return undefined
      return meta.current_page < meta.last_page ? meta.current_page + 1 : undefined
    },
    initialPageParam: 1,
  })
}

// FlatList con load more
<FlatList
  data={orders}
  onEndReached={() => {
    if (hasNextPage && !isFetchingNextPage) {
      fetchNextPage()
    }
  }}
  onEndReachedThreshold={0.3}
  ListFooterComponent={isFetchingNextPage ? <ActivityIndicator /> : null}
/>
```

---

*Creado: 2026-02-19*
*Actualizado: 2026-02-23*
