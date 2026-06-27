# CORE_FEATURES.md — SaaS Template Mobile

> MVP Mobile: las 6 features esenciales para gestionar una business desde el celular.

---

## Resumen del MVP

| Feature | Prioridad | Rol que lo usa |
|---------|-----------|----------------|
| 1. Dashboard | Alta | Owner, Admin |
| 2. Orders | Alta | Owner, Admin, Sales, Operations |
| 3. Customers | Alta | Owner, Admin, Sales |
| 4. Quotes | Alta | Owner, Admin, Sales |
| 5. Perfil + Settings | Media | Todos |
| 6. Notificaciones Push | Media | Todos |

---

## Feature 1 — Dashboard

### What it shows

- Statistics del day: orders nuevos, ingresos, anticipos
- Orders pendientes de attention (recibidos sin confirmar)
- Graphic de orders de los lasts 7 days
- Saldo pendiente total (cobros por hacer)

### Hook

> **Backend note:** El dashboard stats del backend tiene caching de 60s para stats y 5min para usage counts. El endpoint `GET /api/dashboard/stats` necesita ser implementado para consumo mobile (actualmente los stats se calculan internamente para el panel Filament).

```typescript
// src/features/dashboard/hooks/useDashboardStats.ts
export function useDashboardStats() {
  return useQuery({
    queryKey: queryKeys.dashboard.stats(),
    queryFn: dashboardService.getStats,
    staleTime: 60 * 1000,         // 1 minuto (alineado con cache del backend)
    refetchInterval: 5 * 60 * 1000, // Auto-refresh cada 5 min
  })
}

// Response esperada del backend (GET /api/dashboard/stats)
interface DashboardStats {
  orders_today: number
  revenue_today: number       // centavos
  deposit_today: number       // centavos
  pending_balance: number     // centavos — saldo por cobrar
  pending_orders: number      // recibido sin confirmar
  orders_in_production: number
  orders_ready: number
  weekly_chart: Array<{
    date: string              // "2026-02-19"
    orders: number
    revenue: number
  }>
}
```

### Pantalla

```typescript
// app/(app)/(tabs)/index.tsx
export default function DashboardScreen() {
  const { data: stats, isLoading, refetch } = useDashboardStats()
  const { tenant } = useAuthStore()

  return (
    <Screen>
      <Header title={tenant?.name ?? 'Dashboard'} />

      {isLoading ? (
        <LoadingSpinner />
      ) : (
        <>
          {/* Fila de stats */}
          <View style={styles.statsRow}>
            <StatsCard
              label="Orders hoy"
              value={stats?.orders_today ?? 0}
              icon="shopping"
            />
            <StatsCard
              label="Ingresos hoy"
              value={formatCurrency(stats?.revenue_today ?? 0)}
              icon="cash"
            />
          </View>

          <StatsCard
            label="Saldo pendiente"
            value={formatCurrency(stats?.pending_balance ?? 0)}
            icon="alert-circle"
            variant="warning"
          />

          {/* Orders que necesitan attention */}
          <PendingOrdersAlert count={stats?.pending_orders ?? 0} />

          {/* Mini graphic */}
          <WeeklyChart data={stats?.weekly_chart ?? []} />
        </>
      )}
    </Screen>
  )
}
```

---

## Feature 2 — Orders

### Sub-features

1. Lista de orders con filtros por status
2. Detalle de orden (items, payments, timeline)
3. Cambiar status de orden
4. Ver PDF de orden
5. Compartir por Messaging

### Tipos

```typescript
// src/features/orders/types.ts
export type OrderStatus =
  | 'received'
  | 'confirmed'
  | 'in_progress'
  | 'ready'
  | 'delivered'
  | 'cancelled'

export interface OrderLine {
  id: number
  product_id: number
  product_title: string
  size: string
  color: string
  quantity: number
  unit_price: number
  subtotal: number
  is_produced: boolean
}

export interface Order {
  id: number
  customer_name: string
  customer_phone: string
  status: OrderStatus
  total: number
  deposit: number
  balance: number            // total - deposit (accessor)
  notes?: string
  delivery_type: 'pickup' | 'shipping'
  branch_name?: string
  order_lines: OrderLine[]
  created_at: string
  updated_at: string
}
```

### Lista de orders con filtros

```typescript
// app/(app)/(tabs)/orders.tsx
export default function OrdersScreen() {
  const [activeStatus, setActiveStatus] = useState<OrderStatus | 'all'>('all')
  const { orders, isLoading, refetch } = useOrders({ status: activeStatus })

  return (
    <Screen padding={false}>
      {/* Filtros por status */}
      <ScrollView horizontal style={styles.filters}>
        {STATUS_FILTERS.map((filter) => (
          <Chip
            key={filter.value}
            selected={activeStatus === filter.value}
            onPress={() => setActiveStatus(filter.value)}
          >
            {filter.label}
          </Chip>
        ))}
      </ScrollView>

      {/* Lista */}
      <FlatList
        data={orders}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => (
          <OrderCard
            order={item}
            onPress={() => router.push(Routes.APP.ORDER_DETAIL(item.id))}
          />
        )}
        onRefresh={refetch}
        refreshing={isLoading}
        ListEmptyComponent={<EmptyState message="No hay orders" />}
      />

      {/* FAB crear orden */}
      <FAB
        icon="plus"
        onPress={() => router.push(Routes.APP.ORDER_CREATE)}
        style={styles.fab}
      />
    </Screen>
  )
}
```

### OrderCard

```typescript
// src/features/orders/components/OrderCard.tsx
export function OrderCard({ order, onPress }: OrderCardProps) {
  return (
    <Card onPress={onPress} style={styles.card}>
      <Card.Content>
        <View style={styles.header}>
          <Text variant="titleMedium">{order.customer_name}</Text>
          <OrderStatusBadge status={order.status} />
        </View>
        <Text variant="bodySmall" style={styles.phone}>
          {order.customer_phone}
        </Text>
        <View style={styles.footer}>
          <Text variant="bodyMedium">
            {order.order_lines.length} items
          </Text>
          <Text variant="titleSmall" style={styles.total}>
            {formatCurrency(order.total)}
          </Text>
        </View>
        {order.balance > 0 && (
          <Text variant="bodySmall" style={styles.balance}>
            Saldo pendiente: {formatCurrency(order.balance)}
          </Text>
        )}
      </Card.Content>
    </Card>
  )
}
```

### Badge de status con colors

```typescript
// src/features/orders/components/OrderStatusBadge.tsx
const STATUS_CONFIG: Record<OrderStatus, { label: string; color: string }> = {
  recibido: { label: 'Recibido', color: '#6B7280' },
  confirmado: { label: 'Confirmado', color: '#3B82F6' },
  en_operations: { label: 'En operations', color: '#F59E0B' },
  listo: { label: 'Listo', color: '#10B981' },
  entregado: { label: 'Entregado', color: '#059669' },
  cancelado: { label: 'Cancelado', color: '#EF4444' },
}

export function OrderStatusBadge({ status }: { status: OrderStatus }) {
  const config = STATUS_CONFIG[status]
  return (
    <Chip
      style={{ backgroundColor: config.color + '20' }}
      textStyle={{ color: config.color, fontSize: 11 }}
    >
      {config.label}
    </Chip>
  )
}
```

### Detalle de orden con actions

```typescript
// app/(app)/orders/[id].tsx
export default function OrderDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>()
  const { order, isLoading } = useOrder(Number(id))
  const { mutate: updateStatus, isPending } = useUpdateOrderStatus()
  const { canManageProduction } = usePermissions()

  const handleMessaging = () => {
    const message = `Hola ${order?.customer_name}, tu order is ${order?.status}.`
    const url = `messaging://send?phone=52${order?.customer_phone}&text=${encodeURIComponent(message)}`
    Linking.openURL(url)
  }

  const handlePdf = () => {
    // Abrir URL del PDF en browser
    Linking.openURL(`${API_URL}/orders/${id}/pdf`)
  }

  return (
    <Screen>
      {/* Header con actions */}
      <View style={styles.actions}>
        <Button icon="messaging" onPress={handleMessaging}>Messaging</Button>
        <Button icon="file-pdf-box" onPress={handlePdf}>PDF</Button>
      </View>

      {/* Info del customer */}
      <CustomerInfo order={order} />

      {/* Items */}
      <OrderLinesList items={order?.order_lines ?? []} />

      {/* Totales */}
      <OrderTotals order={order} />

      {/* Cambiar status */}
      {canManageProduction && (
        <StatusUpdater
          currentStatus={order?.status}
          onUpdate={(status) => updateStatus({ id: Number(id), status })}
          isLoading={isPending}
        />
      )}
    </Screen>
  )
}
```

---

## Feature 3 — Customers

### Sub-features

1. Lista con search por name/phone
2. Detalle del customer con historial de orders
3. Llamar / Messaging directo desde la app

### Hook de search con debounce

```typescript
// src/features/customers/hooks/useCustomers.ts
export function useCustomers(search?: string) {
  const debouncedSearch = useDebounce(search, 300)

  return useQuery({
    queryKey: queryKeys.customers.list({ search: debouncedSearch }),
    queryFn: () => customersService.getCustomers({ search: debouncedSearch }),
    placeholderData: keepPreviousData,  // No flash mientras escribe
  })
}
```

### Pantalla de search

```typescript
// app/(app)/(tabs)/customers.tsx
export default function CustomersScreen() {
  const [search, setSearch] = useState('')
  const { customers, isLoading } = useCustomers(search)

  return (
    <Screen padding={false}>
      <Searchbar
        placeholder="Buscar por name o phone"
        value={search}
        onChangeText={setSearch}
        style={styles.search}
      />

      <FlatList
        data={customers}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => (
          <CustomerCard
            customer={item}
            onPress={() => router.push(Routes.APP.CUSTOMER_DETAIL(item.id))}
          />
        )}
        ListEmptyComponent={
          <EmptyState
            message={search ? 'Sin resultados' : 'No hay customers still'}
          />
        }
      />
    </Screen>
  )
}
```

### Acciones de communication directa

```typescript
// src/features/customers/components/CustomerContactActions.tsx
export function CustomerContactActions({ phone }: { phone: string }) {
  const handleCall = () => Linking.openURL(`tel:${phone}`)
  const handleMessaging = () => Linking.openURL(`messaging://send?phone=52${phone}`)

  return (
    <View style={styles.actions}>
      <IconButton icon="phone" onPress={handleCall} />
      <IconButton icon="messaging" onPress={handleMessaging} />
    </View>
  )
}
```

---

## Feature 4 — Quotes

### Sub-features

1. Calculadora de precios en tiempo real
2. Crear quote y compartir PDF / Messaging
3. Historial de quotes
4. Convertir quote en orden

### Calculadora con API de precios

```typescript
// src/features/quotes/hooks/useCalculatePrice.ts
export function useCalculatePrice(params: {
  model?: string
  size?: string
  quantity?: number
}) {
  return useQuery({
    queryKey: queryKeys.pricing.calculate(params),
    queryFn: () => pricingService.calculate(params),
    enabled: !!(params.model && params.size && params.quantity && params.quantity > 0),
    staleTime: 5 * 60 * 1000,   // Precios cambian poco
  })
}
```

### Pantalla de calculadora

```typescript
// app/(app)/quotes/create.tsx
export default function CreateQuoteScreen() {
  const [model, setModel] = useState('basicas')
  const [size, setSize] = useState('MD')
  const [quantity, setQuantity] = useState(1)

  const { data: pricing } = useCalculatePrice({ model, size, quantity })
  const { mutate: createQuote, isPending } = useCreateQuote()

  return (
    <Screen>
      <Text variant="headlineSmall">Nueva quote</Text>

      {/* Selector de model */}
      <SegmentedButtons
        value={model}
        onValueChange={setModel}
        buttons={[
          { value: 'basicas', label: 'Basic' },
          { value: 'premium', label: 'Premium' },
        ]}
      />

      {/* Selector de talla */}
      <SizeSelector value={size} onChange={setSize} />

      {/* Cantidad con +/- */}
      <QuantityInput value={quantity} onChange={setQuantity} min={1} max={999} />

      {/* Precios calculados */}
      {pricing && (
        <Card style={styles.priceCard}>
          <Card.Content>
            <PriceRow label="Precio unitario" value={pricing.unitPrice} />
            <PriceRow label="Subtotal" value={pricing.subtotal} bold />
            <PriceRow
              label="Anticipo 50%"
              value={pricing.deposit50}
              variant="secondary"
            />
          </Card.Content>
        </Card>
      )}

      {/* Sugerencia de ahorro */}
      {pricing?.suggestion?.hasSuggestion && (
        <SavingsSuggestion suggestion={pricing.suggestion} />
      )}

      {/* Guardar y compartir */}
      <Button
        mode="contained"
        onPress={() => createQuote({ model, size, quantity })}
        loading={isPending}
      >
        Crear quote
      </Button>
    </Screen>
  )
}
```

### Compartir por Messaging

```typescript
// src/features/quotes/hooks/useShareQuote.ts
export function useShareQuote() {
  const shareViaMessaging = async (quote: Quote, phone?: string) => {
    const message = [
      `*Quote SaaS Template*`,
      ``,
      `Item: ${quote.model} - Talla ${quote.size}`,
      `Cantidad: ${quote.quantity} piezas`,
      `Precio unitario: $${quote.unit_price}`,
      `*Total: $${quote.subtotal}*`,
      `Anticipo (50%): $${quote.deposit50}`,
      ``,
      `Ver PDF: ${API_URL}/quotes/${quote.id}/pdf`,
    ].join('\n')

    const url = phone
      ? `messaging://send?phone=52${phone}&text=${encodeURIComponent(message)}`
      : `messaging://send?text=${encodeURIComponent(message)}`

    const canOpen = await Linking.canOpenURL(url)
    if (canOpen) {
      await Linking.openURL(url)
    } else {
      // Messaging no instalado — usar Share nativo
      await Share.share({ message })
    }
  }

  return { shareViaMessaging }
}
```

---

## Feature 5 — Perfil y Settings

### Sub-features

1. Info del user y tenant activo
2. Cambio de tenant (si aplica)
3. Plan actual y limits
4. Cerrar session

```typescript
// app/(app)/profile.tsx
export default function ProfileScreen() {
  const { user, tenant, availableTenants, logout } = useAuthStore()

  return (
    <Screen>
      {/* Avatar e info */}
      <View style={styles.userInfo}>
        <Avatar.Text label={user?.name.slice(0, 2) ?? 'U'} size={60} />
        <Text variant="titleLarge">{user?.name}</Text>
        <Text variant="bodyMedium">{user?.email}</Text>
        <RoleBadge role={user?.role ?? ''} />
      </View>

      {/* Negocio activo */}
      <List.Item
        title={tenant?.name}
        description={`Plan ${tenant?.plan?.toUpperCase()}`}
        left={() => <List.Icon icon="store" />}
      />

      {/* Switcher de tenant */}
      {availableTenants.length > 1 && (
        <TenantSwitcher />
      )}

      {/* Plan y limits */}
      <PlanLimitsCard plan={tenant?.plan} />

      {/* Cerrar session */}
      <Button
        mode="outlined"
        onPress={logout}
        icon="logout"
        style={styles.logoutBtn}
      >
        Cerrar session
      </Button>
    </Screen>
  )
}
```

---

## Feature 6 — Notificaciones Push

### Flujo

```
Backend actualiza status de orden
    │
    ▼
Laravel Queue dispara notification
    │
    ▼
POST /api/notifications/push (Expo Push API)
    │
    ▼
Expo Push Service → APNS (iOS) / FCM (Android)
    │
    ▼
Push notification en el celular del user
    │
    ▼
Tap en notification → navegar al detalle de la orden
```

### Registration del token push

```typescript
// src/features/notifications/hooks/usePushNotifications.ts
export function usePushNotifications() {
  const { user } = useAuthStore()

  useEffect(() => {
    if (!user) return
    registerForPushNotifications()
  }, [user])

  const registerForPushNotifications = async () => {
    // Check si es dispositivo physical
    if (!Device.isDevice) return

    // Solicitar permisos
    const { status: existingStatus } = await Notifications.getPermissionsAsync()
    let finalStatus = existingStatus

    if (existingStatus !== 'granted') {
      const { status } = await Notifications.requestPermissionsAsync()
      finalStatus = status
    }

    if (finalStatus !== 'granted') return

    // Obtener token Expo
    const pushToken = await Notifications.getExpoPushTokenAsync({
      projectId: Constants.expoConfig?.extra?.eas?.projectId,
    })

    // Registrar token en el backend
    await notificationsService.registerToken(pushToken.data)
  }
}

// Manejar notification al hacer tap
export function useNotificationNavigation() {
  useEffect(() => {
    const subscription = Notifications.addNotificationResponseReceivedListener((response) => {
      const data = response.notification.request.content.data

      if (data.type === 'order_status_changed') {
        router.push(Routes.APP.ORDER_DETAIL(data.order_id))
      }
    })

    return () => subscription.remove()
  }, [])
}
```

### Endpoint backend (referencia)

```
POST /api/auth/push-token
Authorization: Bearer {token}
Body: { "push_token": "ExponentPushToken[xxx]" }
```

---

## Formato de Moneda

Function utilitaria compartida por todas las features:

```typescript
// src/lib/utils/currency.ts
export function formatCurrency(cents: number, currency = 'USD'): string {
  // El backend sends centavos — dividir entre 100
  const amount = cents / 100
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(amount)
}

// Si el backend sends pesos directamente (no centavos):
export function formatPesos(amount: number): string {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'USD',
  }).format(amount)
}
```

> **Check con el backend:** Confirmar si los precios vienen en centavos o pesos antes de implementar.

---

*Creado: 2026-02-19*
*Actualizado: 2026-02-23*
