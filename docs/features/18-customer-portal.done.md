# Fase F: Portal del Customer (Mi Cuenta)

**Date:** 2026-02-14
**Status:** Completado
**Priority:** —
**Dependencia:** Fase E (Payments Payment Gateway) completada
**Branch:** feature/customer-portal
**PR:** #14

---

## Problema Actual

El customer NO tiene visibilidad de sus orders. Todo es via Messaging:
1. Admin crea order en Filament
2. Le manda link de payment por Messaging
3. Customer pregunta "¿como va mi order?" por Messaging
4. Admin revisa manualmente y responde
5. No hay historial, no hay tracking, no hay comprobantes

**Consecuencias:**
- Admin saturado respondiendo "¿ya esta mi order?"
- Customer sin autonomia para consultar estado
- Sin comprobantes de payment descargables
- Sin forma de ver sus designs/files

---

## Solucion: Portal del Customer

Panel web accesible en `/my-account` donde el customer puede:
- Ver todos sus orders y su estado en tiempo real
- Consultar el avance de operations
- Ver y pagar saldos pendientes (link Payment Gateway)
- Descargar/ver sus designs
- Gestionar su perfil y direcciones
- Recibir tracking de envio

### Autenticacion

Login por **telefono + password** (el telefono ya es el identificador unico del Customer).
- Registration: se crea automaticamente al hacer primer order en Filament (admin asigna password temporal)
- Login: `/login` con telefono + password
- Reset: via Messaging (admin resetea password manualmente en MVP)

---

## Arquitectura

### Tabs del Portal

| # | Tab | ID | Icono | Descripcion |
|---|-----|----|-------|-------------|
| 1 | Mis Orders | `orders` | heroicon-o-shopping-bag | Historial + status en tiempo real |
| 2 | Perfil | `profile` | heroicon-o-user | Name, telefono, email |
| 3 | Direcciones | `addresses` | heroicon-o-map-pin | CRUD de direcciones de entrega |
| 4 | Payments | `payments` | heroicon-o-credit-card | Historial de payments, saldos pendientes |
| 5 | Configuracion | `settings` | heroicon-o-cog-6-tooth | Notificaciones, password |

### Layout

- **Desktop:** 2 columns (sidebar 1/4 sticky + contenido 3/4)
- **Mobile:** 1 column, tabs horizontales scrolleables
- **Header:** Avatar con iniciales + name + telefono
- **Sidebar:** Navegacion entre tabs + boton "Cerrar Sesion"
- **No autenticado:** Card centrada con "Acceso Restringido" + boton "Iniciar Sesion"

---

## TAB 1: Mis Orders (orders)

El tab principal y mas importante. Muestra todos los orders del customer con estado en tiempo real.

### Lista de Orders

Cada card de order muestra:
- **Header:** `Order #42` + badge de estado + fecha (formato: "13 feb 2026, 14:00")
- **Items:** Lista con item, talla, color, cantidad (max 3 visibles, despues "... y X mas")
- **Design:** Thumbnail del design (si existe) — click para ver en grande
- **Footer:** Total + saldo pendiente (rojo si > 0, verde si pagado) + actions

### Badges de Estado

| Estado | Texto | Color | Icono |
|--------|-------|-------|-------|
| recibido | Recibido | gray | heroicon-o-inbox |
| pending | Pending | warning/yellow | heroicon-o-clock |
| confirmado | Confirmado | info/blue | heroicon-o-check |
| en_operations | En Operations | primary/indigo | heroicon-o-cog-6-tooth |
| listo | Listo | success/green | heroicon-o-check-circle |
| entregado | Entregado | success/green | heroicon-o-truck |
| cancelado | Cancelado | danger/red | heroicon-o-x-circle |

### Detalle de Order (expandible o pagina separada)

Al hacer click en "Ver Detalles":

#### Timeline de Estado (vertical, lado izquierdo)
```
● Recibido .................. 13 feb 2026, 10:00
│
● Confirmado ................ 13 feb 2026, 10:15
│  Payment de $500 recibido via tarjeta
│
● En Operations ............. 13 feb 2026, 14:00
│  Progreso: 3/5 items (60%)
│  ████████████░░░░░░░░ 60%
│
○ Listo ..................... Pendiente
│
○ Entregado ................. Pendiente
```

- Steps completados: circulo relleno (●) + linea solida + timestamp
- Step actual: circulo relleno + animacion pulse
- Steps pendientes: circulo vacio (○) + linea punteada + "Pendiente"

#### Seccion de Items
Table/lista con:
- Item (name + model)
- Talla / Color
- Cantidad x Precio = Subtotal
- Imagen del design (thumbnail clickeable)
- Estado de operations por item: "Producido" (verde) o "Pendiente" (gris)

#### Seccion de Entrega
- **Pickup:** Name de location + direccion + mapa link
- **Envio:** Direccion completa + carrier + tracking number (link clickeable) + status

#### Seccion de Payments
- Table: fecha, monto, metodo, referencia
- **Subtotal** / **Envio** / **Total** / **Pagado** / **Saldo**
- Si saldo > 0: boton "Pagar Saldo" (abre link de Payment Gateway si existe, o muestra instrucciones)

### Filtros
- **Status:** Todos | Activos (recibido→listo) | Entregados | Cancelados
- **Periodo:** Todo | Ultima semana | Ultimo mes | Ultimos 3 meses

### Estado Vacio
Icono grande + "No tienes orders aun" + "Contactanos por Messaging para hacer tu primer order" + boton Messaging

---

## TAB 2: Perfil (profile)

Card con formulario de datos personales.

### Campos
- **Avatar:** Circulo con iniciales (fondo primary, texto blanco) — solo lectura en MVP
- **Name Completo:** input text, required
- **Telefono:** input tel, **readonly** (es el identificador, no se puede cambiar)
- **Email:** input email, optional
- **Direccion (default):** texto, readonly, link a tab Direcciones

### Acciones
- Boton "Guardar Cambios" (primary)
- Boton "Cambiar Password" (secondary) — abre modal con: password actual + nuevo + confirmar

---

## TAB 3: Direcciones (addresses)

CRUD de direcciones de entrega. Reutilizables al hacer orders futuros.

### Formulario
- **Etiqueta:** select — Casa / Oficina / Otro
- **Calle y Numero:** text, required
- **Colonia:** text, required
- **Ciudad:** text, required
- **Status:** select (MEXICO_STATES de EnviaShippingService)
- **C.P.:** text, required, maxlength 5
- **Referencias:** textarea, optional
- **Predeterminada:** checkbox

### Lista de Direcciones
Cada card:
- Icono segun etiqueta + name
- Badge "PREDETERMINADA" si es default
- Direccion completa
- Acciones: Predeterminar | Editar | Eliminar (con confirmacion)

### Estado Vacio
"No tienes direcciones guardadas" + boton "+ Agregar direccion"

---

## TAB 4: Payments (payments)

Historial de payments y saldos pendientes.

### Resumen (cards superiores)
- **Total Pagado:** suma de todos los payments (verde)
- **Saldo Pendiente:** suma de balances de orders activos (rojo si > 0)
- **Orders Activos:** count de orders no entregados/cancelados

### Orders con Saldo Pendiente
Lista de orders con balance > 0:
- `Order #42 — Saldo: $500` + boton "Pagar" (abre link Payment Gateway o instrucciones)

### Historial de Payments
Table con todos los payments del customer:
- Fecha
- Order (#)
- Monto
- Metodo (badge: Tarjeta/Bank Transfer/Wire Transfer/Transfer/Cash)
- Referencia

### Metodos de Payment Aceptados
Info card con iconos:
- Tarjeta (Visa, Mastercard, Amex)
- Bank Transfer (cash en tienda)
- Wire Transfer (bank transfer)
- Cash (en location)
- Transfer directa

---

## TAB 5: Configuracion (settings)

### Seccion: Notificaciones
- Checkbox: "Recibir actualizaciones por Messaging" (default: on)
- Checkbox: "Recibir confirmacion de payment por Messaging" (default: on)
- Checkbox: "Recibir ofertas y promociones" (default: off)

### Seccion: Security
- Boton "Cambiar Password"
- Boton "Cerrar Sesion en Todos los Dispositivos" (rojo)

### Seccion: Datos
- Boton "Descargar Mis Datos" (outline)
- Boton "Eliminar Mi Cuenta" (rojo, requiere confirmacion doble)
- Texto: "Al eliminar tu cuenta se borran todos tus datos. Esta accion no se puede deshacer."

---

## Migrations

### 1. Agregar auth a `customers`

```php
Schema::table('customers', function (Blueprint $table) {
    $table->string('password')->nullable()->after('email');
    $table->rememberToken()->after('password');
    $table->boolean('notifications_messaging')->default(true);
    $table->boolean('notifications_payment')->default(true);
    $table->boolean('notifications_promos')->default(false);
});
```

### 2. Crear `customer_addresses`

```php
Schema::create('customer_addresses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained()->onDelete('cascade');
    $table->string('label', 20)->default('casa'); // casa, oficina, otro
    $table->string('street');
    $table->string('district')->nullable(); // colonia
    $table->string('city');
    $table->string('state', 5)->nullable();
    $table->string('zip', 10);
    $table->text('references')->nullable();
    $table->boolean('is_default')->default(false);
    $table->timestamps();

    $table->index('customer_id');
});
```

---

## Models

### Customer.php (modificar)

```php
// Agregar a fillable:
'password', 'notifications_messaging', 'notifications_payment', 'notifications_promos'

// Agregar casts:
'password' => 'hashed',
'notifications_messaging' => 'boolean',
'notifications_payment' => 'boolean',
'notifications_promos' => 'boolean',

// Implementar Authenticatable:
use Illuminate\Foundation\Auth\User as Authenticatable;
class Customer extends Authenticatable

// Nueva relacion:
public function addresses(): HasMany
{
    return $this->hasMany(CustomerAddress::class);
}

public function defaultAddress(): HasOne
{
    return $this->hasOne(CustomerAddress::class)->where('is_default', true);
}
```

### CustomerAddress.php (crear)

```php
class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id', 'label', 'street', 'district',
        'city', 'state', 'zip', 'references', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
```

---

## Autenticacion

### Guard personalizado para Customer

```php
// config/auth.php
'guards' => [
    'customer' => [
        'driver' => 'session',
        'provider' => 'customers',
    ],
],
'providers' => [
    'customers' => [
        'driver' => 'eloquent',
        'model' => App\Models\Customer::class,
    ],
],
```

### Middleware

```php
// bootstrap/app.php o middleware personalizado
Route::middleware('auth:customer')->prefix('mi-cuenta')->group(...)
```

---

## Rutas

```php
// routes/web.php

// Customer Auth
Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
Route::post('/login', [CustomerAuthController::class, 'login']);
Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

// Customer Portal (auth:customer)
Route::middleware('auth:customer')->prefix('mi-cuenta')->name('customer.')->group(function () {
    Route::get('/', [CustomerPortalController::class, 'index'])->name('dashboard');

    // Orders
    Route::get('/orders', [CustomerPortalController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [CustomerPortalController::class, 'orderDetail'])->name('orders.show');

    // Profile
    Route::put('/perfil', [CustomerPortalController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [CustomerPortalController::class, 'updatePassword'])->name('password.update');

    // Addresses
    Route::post('/direcciones', [CustomerAddressController::class, 'store'])->name('addresses.store');
    Route::put('/direcciones/{address}', [CustomerAddressController::class, 'update'])->name('addresses.update');
    Route::delete('/direcciones/{address}', [CustomerAddressController::class, 'destroy'])->name('addresses.destroy');
    Route::put('/direcciones/{address}/default', [CustomerAddressController::class, 'setDefault'])->name('addresses.default');

    // Settings
    Route::put('/configuracion/notificaciones', [CustomerPortalController::class, 'updateNotifications'])->name('settings.notifications');
    Route::delete('/cuenta', [CustomerPortalController::class, 'deleteAccount'])->name('account.delete');
});
```

---

## Files a Crear

| File | Descripcion |
|---------|-------------|
| `app/Models/CustomerAddress.php` | Model de direcciones |
| `app/Http/Controllers/CustomerAuthController.php` | Login/logout del customer |
| `app/Http/Controllers/CustomerPortalController.php` | Dashboard, orders, perfil, config |
| `app/Http/Controllers/CustomerAddressController.php` | CRUD de direcciones |
| `database/migrations/xxx_add_auth_to_customers.php` | Password + notificaciones en customers |
| `database/migrations/xxx_create_customer_addresses.php` | Table customer_addresses |
| `resources/views/customer/login.blade.php` | Pagina de login |
| `resources/views/customer/portal.blade.php` | Layout principal del portal |
| `resources/views/customer/tabs/orders.blade.php` | Tab orders |
| `resources/views/customer/tabs/order-detail.blade.php` | Detalle con timeline |
| `resources/views/customer/tabs/profile.blade.php` | Tab perfil |
| `resources/views/customer/tabs/addresses.blade.php` | Tab direcciones |
| `resources/views/customer/tabs/payments.blade.php` | Tab payments |
| `resources/views/customer/tabs/settings.blade.php` | Tab configuracion |

## Files a Modificar

| File | Cambio |
|---------|--------|
| `app/Models/Customer.php` | Extender Authenticatable, +password, +relationships |
| `config/auth.php` | +guard customer, +provider customers |
| `routes/web.php` | +rutas customer portal |
| `app/Filament/Resources/CustomerResource.php` | +campo para asignar password temporal |

---

## Sub-fases de Implementacion

### F1: Auth + Migrations (base)
1. Migracion: agregar `password` + campos notificacion a `customers`
2. Migracion: crear `customer_addresses`
3. Modificar `Customer.php` → extender `Authenticatable`
4. Crear `CustomerAddress.php`
5. Configurar guard `customer` en `config/auth.php`
6. Crear `CustomerAuthController` (login, logout)
7. Vista `customer/login.blade.php`

### F2: Layout + Tab Orders
8. Crear layout `customer/portal.blade.php` (sidebar + contenido)
9. Crear `CustomerPortalController` con metodo `index` y `orders`
10. Vista `customer/tabs/orders.blade.php` (lista con badges, filtros)
11. Vista `customer/tabs/order-detail.blade.php` (timeline + items + payments)
12. Rutas protegidas con `auth:customer`

### F3: Tab Perfil + Direcciones
13. Vista `customer/tabs/profile.blade.php` (formulario + cambio password)
14. Crear `CustomerAddressController` (CRUD completo)
15. Vista `customer/tabs/addresses.blade.php` (lista + formulario)

### F4: Tab Payments + Configuracion
16. Vista `customer/tabs/payments.blade.php` (resumen + historial + link Payment Gateway)
17. Vista `customer/tabs/settings.blade.php` (notificaciones + security)
18. Accion en Filament para asignar password temporal al crear order

### F5: Pulido + Mobile
19. Responsive: tabs horizontales en mobile
20. Estado vacio para cada tab
21. Toasts de confirmacion (Alpine.js)
22. Probar flujo completo: login → ver order → pagar saldo → ver status actualizado

---

## Flujo Completo (Example)

```
1. Admin crea order #42 en Filament para "Juan" (tel: 9991234567)
   → Customer "Juan" se crea (o ya existe) con password temporal
   → Admin le manda por Messaging: "Tu cuenta: 9991234567 / pass: temp123"

2. Juan abre https://saas-template.com/login
   → Ingresa telefono + password
   → Redirigido a /my-account

3. Juan ve sus orders:
   → Order #42 — Estado: "En Operations" — Saldo: $500
   → Click "Ver Detalles"

4. Timeline muestra:
   ● Recibido .......... 13 feb, 10:00
   ● Confirmado ........ 13 feb, 10:15 (payment $500 via tarjeta)
   ● En Operations ..... 13 feb, 14:00 (3/5 items — 60%)
   ○ Listo ............. Pendiente
   ○ Entregado ......... Pendiente

5. Juan ve que tiene saldo pendiente de $500
   → Click "Pagar Saldo" → Abre link de Payment Gateway
   → Paga con Bank Transfer

6. Webhook llega → Payment registrado → Saldo $0
   → Juan refresca la pagina → ve "Pagado" en verde

7. Cuando el order esta listo:
   → Timeline se actualiza automaticamente
   → Si es envio: aparece link de tracking
```

---

## Notas Tecnicas

- **Livewire vs Alpine.js:** Usar Alpine.js para tabs (sin recarga). Livewire para formularios complejos (direcciones CRUD).
- **Polling:** Para status en tiempo real, usar `wire:poll.10s` en el detalle del order (cada 10 segundos) o Alpine + fetch.
- **Security:** El customer solo puede ver SUS orders (`where('customer_id', auth('customer')->id())`).
- **Fechas:** Formatear con Carbon locale `es_MX` ("13 feb 2026, 14:00").
- **Design system:** Usar las clases existentes en `docs/design-system.md` (`.card`, `.badge`, `.btn-primary`, etc.).
- **Guard separado:** El guard `customer` es independiente del guard `web` (admin Filament). Un customer no puede acceder a `/admin` y viceversa.

---

## Verificacion

1. `php artisan migrate` — migrations sin error
2. Login con telefono + password → acceso al portal
3. Ver orders con status correcto y timeline
4. CRUD de direcciones funcional
5. Pagar saldo desde el portal (link Payment Gateway)
6. Responsive en mobile
7. `composer analyse` sin errores nuevos
8. `composer format` limpio

---

*Documento generado: 2026-02-13*
