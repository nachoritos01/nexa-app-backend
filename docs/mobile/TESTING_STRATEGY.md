# TESTING_STRATEGY.md — SaaS Template Mobile

> Estrategia de testing para la app React Native de SaaS Template.

---

## 1. Pyramid de Testing

```
          ┌─────────────────┐
          │    E2E Tests     │  ← Pocos, lentos, maximum valor
          │   (Maestro)      │    ~10-20 flujos critical
          └────────┬────────┘
         ┌─────────▼──────────┐
         │ Integration Tests  │  ← Medio, hooks + services
         │ (React Native TL)  │    ~50-100 tests
         └─────────┬──────────┘
      ┌────────────▼──────────────┐
      │      Unit Tests            │  ← Muchos, fasts, logic pura
      │  (Jest + TypeScript)       │    ~200+ tests
      └────────────────────────────┘
```

### What to test en cada nivel

| Nivel | What to test | Herramienta |
|-------|-------------|-------------|
| Unit | Utils, transformaciones, validaciones, stores | Jest |
| Integration | Hooks con React Query, componentes con mocks | @testing-library/react-native |
| E2E | Flujos completos: login → crear orden → cambiar status | Maestro |

---

## 2. Configuration de Jest

### jest.config.js

```javascript
// jest.config.js
module.exports = {
  preset: 'jest-expo',
  transformIgnorePatterns: [
    'node_modules/(?!((jest-)?react-native|@react-native(-community)?)|expo(nent)?|@expo(nent)?/.*|@expo-google-fonts/.*|react-navigation|@react-navigation/.*|@unimodules/.*|unimodules|sentry-expo|native-base|react-native-svg)',
  ],
  setupFilesAfterFramework: ['<rootDir>/__tests__/setup.ts'],
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1',
  },
  collectCoverageFrom: [
    'src/**/*.{ts,tsx}',
    '!src/**/*.d.ts',
    '!src/**/types.ts',
    '!src/**/__tests__/**',
  ],
  coverageThresholds: {
    global: {
      branches: 70,
      functions: 80,
      lines: 80,
      statements: 80,
    },
  },
}
```

### Setup de tests

```typescript
// __tests__/setup.ts
import '@testing-library/jest-native/extend-expect'

// Mock de expo-secure-store
jest.mock('expo-secure-store', () => ({
  setItemAsync: jest.fn(),
  getItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}))

// Mock de expo-router
jest.mock('expo-router', () => ({
  useRouter: () => ({ push: jest.fn(), replace: jest.fn(), back: jest.fn() }),
  useLocalSearchParams: () => ({}),
  router: { push: jest.fn(), replace: jest.fn() },
}))

// Mock de expo-linking
jest.mock('expo-linking', () => ({
  openURL: jest.fn(),
  canOpenURL: jest.fn(() => Promise.resolve(true)),
}))

// Silenciar warnings de act() en tests
global.console.warn = (msg: string) => {
  if (msg.includes('act(')) return
  console.warn(msg)
}
```

---

## 3. Unit Tests

### Tests de utilidades

```typescript
// src/lib/utils/__tests__/currency.test.ts
import { formatCurrency, formatPesos } from '../currency'

describe('formatCurrency', () => {
  it('formatea pesos mexicanos correctamente', () => {
    expect(formatPesos(1850)).toBe('$1,850')
    expect(formatPesos(299)).toBe('$299')
    expect(formatPesos(0)).toBe('$0')
  })

  it('formatea valores con decimales', () => {
    expect(formatPesos(1850.5)).toBe('$1,850.50')
  })
})
```

### Tests de validaciones (Zod schemas)

```typescript
// src/features/auth/__tests__/loginSchema.test.ts
import { loginSchema } from '../schemas'

describe('loginSchema', () => {
  it('acepta email y password valids', () => {
    const result = loginSchema.safeParse({
      email: 'test@saas-template.mx',
      password: '12345678',
    })
    expect(result.success).toBe(true)
  })

  it('rechaza email invalid', () => {
    const result = loginSchema.safeParse({
      email: 'no-es-email',
      password: '12345678',
    })
    expect(result.success).toBe(false)
    expect(result.error?.issues[0].message).toBe('Email invalid')
  })

  it('rechaza password corta', () => {
    const result = loginSchema.safeParse({
      email: 'test@saas-template.mx',
      password: '123',
    })
    expect(result.success).toBe(false)
  })
})
```

### Tests del auth store

```typescript
// src/features/auth/__tests__/authStore.test.ts
import { useAuthStore } from '../stores/authStore'
import * as SecureStore from 'expo-secure-store'

const mockUser = { id: 1, name: 'Juan', email: 'juan@test.mx', role: 'owner' }
const mockTenant = { id: 1, name: 'SaaS Template', slug: 'acme', plan: 'growth' as const, is_active: true }

describe('authStore', () => {
  beforeEach(() => {
    useAuthStore.setState({
      user: null,
      tenant: null,
      isAuthenticated: false,
    })
    jest.clearAllMocks()
  })

  it('setAuth guarda user y token', async () => {
    await useAuthStore.getState().setAuth(mockUser, mockTenant, [mockTenant], 'token-abc')

    const state = useAuthStore.getState()
    expect(state.user).toEqual(mockUser)
    expect(state.tenant).toEqual(mockTenant)
    expect(state.isAuthenticated).toBe(true)
    expect(SecureStore.setItemAsync).toHaveBeenCalledWith(
      'saas-template_auth_token',
      'token-abc'
    )
  })

  it('logout limpia estado y SecureStore', async () => {
    useAuthStore.setState({ user: mockUser, isAuthenticated: true })

    await useAuthStore.getState().logout()

    const state = useAuthStore.getState()
    expect(state.user).toBeNull()
    expect(state.isAuthenticated).toBe(false)
    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('saas-template_auth_token')
  })
})
```

### Tests de hooks con React Query

```typescript
// src/features/orders/__tests__/useOrders.test.ts
import { renderHook, waitFor } from '@testing-library/react-native'
import { createWrapper } from '@/__tests__/testUtils'
import { useOrders } from '../hooks/useOrders'
import { ordersService } from '../services/ordersService'

jest.mock('../services/ordersService')

const mockOrders = [
  { id: 1, customer_name: 'Juan', status: 'received', total: 1000 },
  { id: 2, customer_name: 'Maria', status: 'confirmed', total: 2000 },
]

describe('useOrders', () => {
  it('retorna orders del backend', async () => {
    ;(ordersService.getOrders as jest.Mock).mockResolvedValue({
      data: mockOrders,
      count: 2,
    })

    const { result } = renderHook(() => useOrders(), {
      wrapper: createWrapper(),
    })

    await waitFor(() => expect(result.current.isLoading).toBe(false))

    expect(result.current.orders).toHaveLength(2)
    expect(result.current.orders[0].customer_name).toBe('Juan')
  })

  it('filtra por status cuando se pasa filter', async () => {
    ;(ordersService.getOrders as jest.Mock).mockResolvedValue({
      data: [mockOrders[0]],
      count: 1,
    })

    const { result } = renderHook(() => useOrders({ status: 'received' }), {
      wrapper: createWrapper(),
    })

    await waitFor(() => expect(result.current.isLoading).toBe(false))

    expect(ordersService.getOrders).toHaveBeenCalledWith({ status: 'received' })
  })
})
```

### Test utility: wrapper con providers

```typescript
// __tests__/testUtils.tsx
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

export function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  })

  return function Wrapper({ children }: { children: React.ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>
        {children}
      </QueryClientProvider>
    )
  }
}
```

---

## 4. Integration Tests (Componentes)

### Test de LoginScreen

```typescript
// src/features/auth/__tests__/LoginScreen.test.tsx
import { render, fireEvent, waitFor } from '@testing-library/react-native'
import LoginScreen from '@/app/(auth)/login'
import { authService } from '@/features/auth/services/authService'

jest.mock('@/features/auth/services/authService')

describe('LoginScreen', () => {
  it('muestra error para email invalid', async () => {
    const { getByPlaceholderText, getByText } = render(<LoginScreen />)

    fireEvent.changeText(getByPlaceholderText('Email'), 'no-es-email')
    fireEvent.press(getByText('Entrar'))

    await waitFor(() => {
      expect(getByText('Email invalid')).toBeTruthy()
    })
  })

  it('llama al service con credenciales correctas', async () => {
    ;(authService.login as jest.Mock).mockResolvedValue({
      user: { id: 1, name: 'Juan', email: 'juan@test.mx', role: 'owner' },
      tenants: [{ id: 1, name: 'Acme', slug: 'acme', plan: 'growth', is_active: true }],
      token: 'abc123',
    })

    const { getByPlaceholderText, getByText } = render(<LoginScreen />)

    fireEvent.changeText(getByPlaceholderText('Email'), 'juan@test.mx')
    fireEvent.changeText(getByPlaceholderText('Password'), 'password123')
    fireEvent.press(getByText('Entrar'))

    await waitFor(() => {
      expect(authService.login).toHaveBeenCalledWith({
        email: 'juan@test.mx',
        password: 'password123',
      })
    })
  })
})
```

### Test de OrderCard

```typescript
// src/features/orders/__tests__/OrderCard.test.tsx
import { render } from '@testing-library/react-native'
import { OrderCard } from '../components/OrderCard'

const mockOrder = {
  id: 1,
  customer_name: 'Juan Perez',
  customer_phone: '9991234567',
  status: 'confirmed' as const,
  total: 1850,
  deposit: 925,
  balance: 925,
  order_lines: [{ id: 1 }, { id: 2 }],
  created_at: '2026-02-19',
}

describe('OrderCard', () => {
  it('muestra name del customer', () => {
    const { getByText } = render(
      <OrderCard order={mockOrder} onPress={() => {}} />
    )
    expect(getByText('Juan Perez')).toBeTruthy()
  })

  it('muestra cantidad de items', () => {
    const { getByText } = render(
      <OrderCard order={mockOrder} onPress={() => {}} />
    )
    expect(getByText('2 items')).toBeTruthy()
  })

  it('muestra saldo pendiente si existe', () => {
    const { getByText } = render(
      <OrderCard order={mockOrder} onPress={() => {}} />
    )
    expect(getByText(/saldo pendiente/i)).toBeTruthy()
  })

  it('no muestra saldo si is liquidado', () => {
    const { queryByText } = render(
      <OrderCard order={{ ...mockOrder, balance: 0 }} onPress={() => {}} />
    )
    expect(queryByText(/saldo pendiente/i)).toBeNull()
  })
})
```

---

## 5. E2E Tests con Maestro

### Por what Maestro y no Detox

| Criterio | Detox | Maestro |
|---------|-------|---------|
| Setup | Complejo (configurar nativo) | Simple (YAML) |
| Sintaxis | JavaScript (code) | YAML (declarativo) |
| Velocidad de escritura | Lenta | Quick |
| CI | Requiere emulador | Requiere emulador |
| Ideal para | Teams grandes | Startups / equipos smalls |

### Installation

```bash
# Instalar Maestro CLI
curl -Ls "https://get.maestro.mobile.dev" | bash

# Check
maestro --version
```

### Flujo de login

```yaml
# .maestro/flows/01_login.yaml
appId: mx.saas-template.mobile
---
- launchApp:
    clearState: true

- assertVisible: "Iniciar session"

- tapOn:
    text: "Email"
- inputText: "test@saas-template.mx"

- tapOn:
    text: "Password"
- inputText: "password123"

- tapOn:
    text: "Entrar"

- assertVisible: "Dashboard"
- assertVisible: "Orders hoy"
```

### Flujo de crear quote

```yaml
# .maestro/flows/02_create_quote.yaml
appId: mx.saas-template.mobile
---
- runFlow: 01_login.yaml

- tapOn:
    text: "Quotes"

- tapOn:
    id: "fab-create"

- assertVisible: "Nueva quote"

- tapOn:
    text: "Premium"

- tapOn:
    text: "GD"

- clearText:
    id: "quantity-input"
- inputText: "10"

- assertVisible: "$185"      # Precio unitario esperado
- assertVisible: "$1,850"    # Subtotal

- tapOn:
    text: "Crear quote"

- assertVisible: "Quote creada"
```

### Flujo de cambiar status de orden

```yaml
# .maestro/flows/03_update_order_status.yaml
appId: mx.saas-template.mobile
---
- runFlow: 01_login.yaml

- tapOn:
    text: "Orders"

- tapOn:
    index: 0  # Primera orden en la lista

- assertVisible: "Recibido"

- tapOn:
    text: "Confirmar order"

- assertVisible: "Confirmado"
```

### Correr E2E

```bash
# Correr un flujo
maestro test .maestro/flows/01_login.yaml

# Correr todos los flujos
maestro test .maestro/flows/

# Modo debug (muestra actions en pantalla)
maestro test --debug .maestro/flows/01_login.yaml
```

---

## 6. Mocking del Backend

### MSW para tests de integration

```bash
npm install --save-dev msw
```

```typescript
// __tests__/mocks/handlers.ts
import { http, HttpResponse } from 'msw'

export const handlers = [
  http.post('/api/auth/login', () => {
    return HttpResponse.json({
      user: { id: 1, name: 'Test User', email: 'test@test.mx', role: 'owner' },
      tenants: [{ id: 1, name: 'Test Tenant', slug: 'test', plan: 'growth', is_active: true }],
      token: 'test-token-123',
    })
  }),

  http.get('/api/orders', () => {
    return HttpResponse.json({
      data: [
        { id: 1, customer_name: 'Juan', status: 'received', total: 1850 },
      ],
      count: 1,
    })
  }),

  http.get('/api/pricing/calculate', ({ request }) => {
    const url = new URL(request.url)
    const quantity = Number(url.searchParams.get('quantity'))

    return HttpResponse.json({
      model: 'basicas',
      size: 'MD',
      quantity,
      unitPrice: quantity >= 6 ? 185 : 200,
      subtotal: quantity * (quantity >= 6 ? 185 : 200),
      deposit50: quantity * (quantity >= 6 ? 185 : 200) / 2,
    })
  }),
]
```

---

## 7. Coverage y CI

### Objetivo de coverage

| Capa | Minimum | Objetivo |
|------|--------|---------|
| Utils / lib | 90% | 95% |
| Feature hooks | 80% | 90% |
| Feature services | 80% | 90% |
| Componentes UI | 60% | 75% |
| E2E flujos critical | 5 flujos | 10 flujos |

### Command en CI

```bash
# Correr tests con coverage
npm run test:ci

# Output esperado:
# ✓ 247 tests passed
# Coverage: 82.4% statements, 79.1% branches
```

---

*Creado: 2026-02-19*
*Actualizado: 2026-02-23*
