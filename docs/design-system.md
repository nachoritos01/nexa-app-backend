# Guia de Color y Sistema de Diseno - SaaS Template

> **Proposito:** Estandar visual unificado para todos los componentes y paginas de la plataforma. Todo nuevo desarrollo debe respetar esta guia para mantener coherencia visual.

> **Stack visual:** Tailwind CSS v4 + Inter font + Heroicons SVG inline

> **File fuente:** `resources/css/app.css` (clases custom con `@layer components`)

---

## Table de Contenidos

1. [Paleta de Colors](#1-paleta-de-colors)
2. [Gradientes](#2-gradientes)
3. [Glassmorphism](#3-glassmorphism)
4. [Tipografia](#4-tipografia)
5. [Botones](#5-botones)
6. [Tarjetas](#6-tarjetas)
7. [Formularios](#7-formularios)
8. [Badges y Tags](#8-badges-y-tags)
9. [Navegacion](#9-navegacion)
10. [Iconografia](#10-iconografia)
11. [Sombras y Elevacion](#11-sombras-y-elevacion)
12. [Bordes y Redondeo](#12-bordes-y-redondeo)
13. [Animaciones y Transiciones](#13-animaciones-y-transiciones)
14. [Spacing y Layout](#14-spacing-y-layout)
15. [Breakpoints Responsive](#15-breakpoints-responsive)
16. [Elementos Especiales](#16-elementos-especiales)
17. [Estados Interactivos](#17-estados-interactivos)
18. [Patrones de Pagina](#18-patrones-de-pagina)
19. [Referencia Rapida de Clases Custom](#19-referencia-rapida-de-clases-custom)

---

## 1. Paleta de Colors

### 1.1 Primary - Blue (Identidad de marca)

Definidos en `@theme` dentro de `resources/css/app.css`:

```
primary-50   #eff6ff   Fondos sutiles, hover states
primary-100  #dbeafe   Badges, circulos numerados, fondos ligeros
primary-200  #bfdbfe   Bordes de acento, rings de focus
primary-300  #93c5fd   Hover en bordes
primary-400  #60a5fa   Acentos medios
primary-500  #3b82f6   Iconos, cards de contacto, footer logo
primary-600  #2563eb   PRINCIPAL - botones, estados activos, CTA    <-- Color estrella
primary-700  #1d4ed8   Hover de botones, texto sobre fondos claros
primary-800  #1e40af   Logo text, enfasis maximo
primary-900  #1e3a8a   Uso reservado
```

**Uso por contexto:**
- **Botones primarios:** `bg-primary-600` (base) / `bg-primary-700` (hover)
- **Texto sobre fondo claro:** `text-primary-700`
- **Iconos en headers:** `text-primary-600`
- **Fondos activos:** `bg-primary-50`
- **Rings de seleccion:** `ring-primary-200`
- **Logo:** `bg-primary-600` (icono) + `text-primary-800` (texto)

### 1.2 Success - Green

```
success-500  #22c55e   Boton Messaging base
success-600  #16a34a   Boton Messaging hover, iconos de exito
success-700  #15803d   Texto de exito oscuro
```

**Colors Tailwind complementarios usados:**

```
green-50     Fondo tarjeta Messaging en contacto
green-100    Badges verdes, circulos de paso final, feature icons
green-400    Hover de icono Messaging en footer (sobre fondo oscuro)
green-500    Boton Messaging primario
green-600    Textos de ahorro/descuento, checkmarks de validacion, iconos feature
green-700    Badge text verde
```

### 1.3 Warning - Amber

```
warning-50   #fffbeb   Fondo de sugerencias de ahorro
warning-100  #fef3c7   Badges amber
warning-500  #f59e0b   Highlights de advertencia
warning-600  #d97706   Iconos amber, feature "precios por volumen"
warning-700  #b45309   Texto de advertencia oscuro
```

**Colors Tailwind complementarios usados:**

```
amber-50     Cards de sugerencia de ahorro
amber-100    Iconos feature, badges
amber-200    Borde cards de sugerencia
amber-600    Icono de advertencia talla 2XG
amber-700    Texto en boton "Aplicar" sugerencia
amber-800    Texto enfatico en sugerencias
amber-900    Hover de texto amber
```

### 1.4 Error - Red

```
red-50       Fondo de mensajes de error
red-200      Ring de focus en inputs con error
red-500      Borde de inputs con error
red-600      Texto de error en validacion
```

### 1.5 Neutrales - Gray

```
slate-50  #f8fafc   Body background, wave separator fill, secciones alternas
gray-50   #f9fafb   Fondos de items inactivos, price summary bar, pickup cards
gray-100  #f3f4f6   Botones inactivos, size-btn-inactive, qty-preset-inactive
gray-200  #e5e7eb   Bordes default en inputs y model-cards, separadores
gray-300  #d1d5db   Circulos vacios de progreso, iconos placeholder
gray-400  #9ca3af   Iconos secundarios, texto muted en footer
gray-500  #6b7280   Texto helper, labels secundarios, iconos neutrales, footer logo
gray-600  #4b5563   Texto body principal, nav-links, descripciones
gray-700  #374151   Texto enfatico body, size-btn-inactive text
gray-800  #1f2937   Borde footer copyright
gray-900  #111827   Headings principales, footer background, CTA section dark
```

### 1.6 Colors sobre Fondos Oscuros

Para secciones con `bg-gray-900` o gradientes:

```
text-white           Headings y body principal
text-gray-400        Parrafos, links hover target
text-gray-500        Copyright, texto terciario
text-blue-100        Subtitulos sobre gradiente hero
text-blue-200        Labels de stats, metadata sobre gradiente
text-green-400       Hover de icono Messaging en footer
text-primary-400     Hover de icono email en footer
```

### 1.7 Colors con Opacidad

```
bg-white/10          Overlay sutil sobre gradiente
bg-white/20          Glassmorphism standard (.glass)
bg-white/30          Hover de glassmorphism
bg-black/10          Glassmorphism oscuro (.glass-dark)
border-white/30      Bordes de botones glass sobre hero
border-blue-500/30   Separadores dentro de pricing-card
```

---

## 2. Gradientes

### 2.1 Hero Principal

```css
.gradient-hero {
    @apply bg-gradient-to-r from-primary-600 to-indigo-600;
}
```

Barrido horizontal de **azul a indigo**. Se usa en:
- Hero section de la homepage
- Secciones principales de entrada

```html
<section class="gradient-hero text-white py-16 lg:py-24">
```

### 2.2 Hero Oscuro

```css
.gradient-hero-dark {
    @apply bg-gradient-to-r from-primary-800 to-indigo-800;
}
```

Variante mas profunda para contextos con mayor contraste.

### 2.3 Pricing Card (Diagonal)

```css
.pricing-card {
    @apply bg-gradient-to-br from-primary-600 to-indigo-700 rounded-2xl p-6 text-white;
}
```

Barrido diagonal (top-left a bottom-right) para tarjetas de cotizacion:

```html
<div class="pricing-card">
    <h3 class="text-lg font-semibold text-blue-100 mb-4">Tu cotizacion</h3>
    <span class="text-blue-200">Precio unitario:</span>
    <span class="font-bold">${{ $price }} USD</span>
    <!-- Separador translucido -->
    <div class="pt-3 border-t border-blue-500/30">...</div>
</div>
```

### 2.4 Regla de Gradientes

| Contexto | Clase | Direccion |
|----------|-------|-----------|
| Hero sections | `.gradient-hero` | `to-r` (horizontal) |
| Hero oscuro | `.gradient-hero-dark` | `to-r` (horizontal) |
| Pricing cards | `.pricing-card` | `to-br` (diagonal) |

**Nunca** usar otros gradientes. Solo estos 3 patrones mantienen la identidad visual.

---

## 3. Glassmorphism

### 3.1 Glass Standard

```css
.glass {
    @apply bg-white/20 backdrop-blur-md;
}
```

Efecto de cristal esmerilado sobre gradientes. Uso:

```html
<!-- Badge sobre hero -->
<span class="inline-block glass px-4 py-2 rounded-full text-sm font-medium">
    Products SaaS por Encargo
</span>

<!-- Stats sobre hero -->
<div class="glass rounded-xl p-4">
    <div class="text-3xl font-bold">3+</div>
    <div class="text-sm text-blue-200">Piezas Minimo</div>
</div>
```

### 3.2 Glass Dark

```css
.glass-dark {
    @apply bg-black/10 backdrop-blur-md;
}
```

Para overlays sobre fondos claros.

### 3.3 Boton Ghost sobre Gradiente

No es una clase custom, pero es un patron recurrente para CTAs secundarios sobre hero:

```html
<a class="btn-secondary bg-white/10 border-white/30 text-white hover:bg-white/20">
    Ver Catalogo
</a>
```

Y para el boton "Agregar al carrito" dentro de pricing-card:

```html
<button class="w-full py-3 px-4 bg-white/20 hover:bg-white/30 rounded-xl font-medium transition-all">
    Agregar al carrito
</button>
```

---

## 4. Tipografia

### 4.1 Fuente

```css
--font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif,
             'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
```

Cargada desde `fonts.bunny.net` con pesos **400, 500, 600, 700, 800**.

### 4.2 Jerarquia de Textos

| Elemento | Clases | Example |
|----------|--------|---------|
| H1 Hero | `text-4xl lg:text-5xl font-bold` | "Personaliza tu Estilo" |
| H1 Pagina | `text-3xl font-bold text-gray-900` | "Cotizador Inteligente" |
| H2 Seccion | `text-3xl font-bold text-gray-900` | "Por que elegir..." |
| H2 Card | `text-lg font-semibold text-gray-900` | "Configuracion del item" |
| H3 Feature | `text-xl font-semibold text-gray-900` | "Alta Calidad" |
| H3 Sub-item | `font-semibold text-gray-900` | "Plaza Kukulkan" |
| H3 FAQ | `font-medium text-gray-900` | "Cual es el mejor medio?" |
| Body | `text-gray-600` | Parrafos descriptivos |
| Body enfatico | `text-gray-700` | Textos con mas peso |
| Helper | `text-sm text-gray-500` | Labels, metadata |
| Micro | `text-xs text-gray-500` | Badges, fine print |
| Precio grande | `text-2xl font-bold text-primary-600` | "$200" |
| Precio hero | `text-3xl font-bold` (sobre gradiente) | "3+" |
| Cantidad | `text-4xl font-bold` | Input de cantidad |

### 4.3 Reglas Tipograficas

- **Headings:** Siempre `font-bold` o `font-semibold`
- **Botones:** `font-semibold` (primario/secundario) o `font-bold` (Messaging)
- **Labels de formulario:** `text-sm font-medium text-gray-700`
- **Texto sobre gradiente:** `text-white` para titulos, `text-blue-100/200` para descriptivos
- **Precios con descuento:** `text-green-600` para resaltar ahorros

---

## 5. Botones

### 5.1 Primario

```css
.btn-primary {
    @apply bg-primary-600 hover:bg-primary-700 text-white
           font-semibold py-3 px-6 rounded-xl
           transition-all duration-200 shadow-md hover:shadow-lg
           active:scale-95;
}
```

```html
<a href="/quote" class="btn-primary">Cotizar Ahora</a>
<a href="/quote" class="btn-primary text-lg">Cotizar Ahora</a>  <!-- variante grande -->
```

### 5.2 Secundario

```css
.btn-secondary {
    @apply bg-white hover:bg-gray-50 text-primary-700
           font-semibold py-3 px-6 rounded-xl
           border-2 border-primary-200
           transition-all duration-200;
}
```

```html
<!-- Normal -->
<a class="btn-secondary">Ver Catalogo</a>

<!-- Ghost sobre gradiente (override inline) -->
<a class="btn-secondary bg-white/10 border-white/30 text-white hover:bg-white/20">
    Ver Catalogo
</a>
```

### 5.3 Messaging

```css
.btn-messaging {
    @apply bg-green-500 hover:bg-green-600 text-white
           font-bold py-4 px-8 rounded-xl
           transition-all duration-200 shadow-lg hover:shadow-xl
           flex items-center justify-center gap-2;
}
```

```html
<a href="https://wa.me/529992369277" class="btn-messaging">
    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">...</svg>
    Consultar por Messaging
</a>
```

**Siempre** incluye el icono SVG de Messaging a la izquierda.

### 5.4 Botones de Talla

```css
.size-btn          { @apply w-14 h-14 rounded-xl font-bold text-lg transition-all duration-200; }
.size-btn-active   { @apply bg-primary-600 text-white shadow-lg scale-105; }
.size-btn-inactive { @apply bg-gray-100 text-gray-700 hover:bg-gray-200; }
```

```html
<button class="size-btn {{ $active ? 'size-btn-active' : 'size-btn-inactive' }}">
    MD
</button>
```

El activo escala al **105%** para micro-feedback visual.

### 5.5 Botones de Cantidad (+/-)

```css
.qty-btn          { @apply w-12 h-12 rounded-full font-bold text-xl transition-all duration-200 flex items-center justify-center; }
.qty-btn-plus     { @apply bg-primary-600 hover:bg-primary-700 text-white; }
.qty-btn-minus    { @apply bg-gray-100 hover:bg-gray-200 text-gray-700; }
.qty-btn-disabled { @apply bg-gray-50 text-gray-300 cursor-not-allowed; }
```

```html
<button class="qty-btn {{ $qty > 1 ? 'qty-btn-minus' : 'qty-btn-disabled' }}">-</button>
<button class="qty-btn qty-btn-plus">+</button>
```

### 5.6 Presets de Cantidad

```css
.qty-preset          { @apply px-4 py-2 rounded-full text-sm font-medium transition-all duration-200; }
.qty-preset-active   { @apply bg-primary-600 text-white; }
.qty-preset-inactive { @apply bg-gray-100 hover:bg-gray-200 text-gray-700; }
```

```html
<button class="qty-preset {{ $active ? 'qty-preset-active' : 'qty-preset-inactive' }}">
    6
</button>
```

---

## 6. Tarjetas

### 6.1 Card Standard

```css
.card {
    @apply bg-white rounded-2xl shadow-lg p-6;
}
```

Uso general para contenedores de informacion, formularios, listas:

```html
<div class="card">
    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-primary-600">...</svg>
        Titulo de Seccion
    </h2>
    <!-- contenido -->
</div>
```

### 6.2 Card con Hover

```css
.card-hover {
    @apply bg-white rounded-2xl shadow-lg p-6
           transition-all duration-200 hover:shadow-xl hover:-translate-y-1;
}
```

Para elementos clickeables o features que se resaltan. Se eleva **4px** al hacer hover:

```html
<div class="card-hover text-center">
    <div class="w-16 h-16 bg-primary-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-primary-600">...</svg>
    </div>
    <h3 class="text-xl font-semibold text-gray-900 mb-2">Alta Calidad</h3>
    <p class="text-gray-600">Descripcion del feature...</p>
</div>
```

### 6.3 Model Card (Seleccion)

```css
.model-card          { @apply p-4 rounded-xl border-2 text-left transition-all duration-200 cursor-pointer; }
.model-card-active   { @apply border-primary-600 bg-primary-50 ring-2 ring-primary-200; }
.model-card-inactive { @apply border-gray-200 hover:border-primary-300; }
```

Tarjetas de seleccion con estado visual claro:

```html
<button class="model-card {{ $active ? 'model-card-active' : 'model-card-inactive' }}">
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-lg {{ $active ? 'bg-primary-600' : 'bg-gray-100' }} flex items-center justify-center">
            <svg class="w-5 h-5 {{ $active ? 'text-white' : 'text-gray-500' }}">...</svg>
        </div>
        <div>
            <div class="font-semibold text-gray-900">Basicas</div>
            <div class="text-sm text-gray-500">Algodon 100%</div>
        </div>
    </div>
</button>
```

### 6.4 Pricing Card (Gradiente)

```css
.pricing-card {
    @apply bg-gradient-to-br from-primary-600 to-indigo-700 rounded-2xl p-6 text-white;
}
```

Ver seccion [Gradientes](#23-pricing-card-diagonal).

### 6.5 Suggestion Card

No es una clase custom, pero es un patron consistente:

```html
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 animate-fade-in">
    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-amber-600">...</svg>
        </div>
        <div>
            <p class="font-semibold text-amber-800">Mensaje de sugerencia</p>
            <button class="mt-2 text-sm font-medium text-amber-700 hover:text-amber-900 underline">
                Aplicar
            </button>
        </div>
    </div>
</div>
```

### 6.6 Contact Card

Patron para items de contacto con icono grande circular:

```html
<a class="flex items-center gap-4 p-4 bg-green-50 rounded-xl hover:bg-green-100 transition-colors">
    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
        <svg class="w-6 h-6 text-white">...</svg>
    </div>
    <div>
        <div class="font-semibold text-gray-900">Messaging</div>
        <div class="text-sm text-gray-600">Response inmediata</div>
        <div class="text-green-600 font-medium">+52 999 236 9277</div>
    </div>
</a>
```

Variantes de fondo: `bg-green-50` (Messaging), `bg-primary-50` (email), `bg-gray-50` (telefono).

---

## 7. Formularios

### 7.1 Input Standard

```css
.input {
    @apply w-full px-4 py-3 rounded-xl border border-gray-200
           focus:border-primary-500 focus:ring-2 focus:ring-primary-200
           transition-all duration-200 outline-none;
}
```

```html
<input type="text" class="input" placeholder="Name">
<select class="input">...</select>
```

### 7.2 Input con Error

```css
.input-error {
    @apply border-red-500 focus:border-red-500 focus:ring-red-200;
}
```

```html
<input class="input input-error" value="invalido">
<p class="mt-2 text-sm text-red-600 flex items-center gap-1">
    <svg class="w-4 h-4">...</svg>
    Mensaje de error
</p>
```

### 7.3 Labels

```html
<!-- Label principal -->
<label class="block text-sm font-medium text-gray-700 mb-3">Tipo de Product</label>

<!-- Sub-label (dentro de grid) -->
<label class="text-xs text-gray-500 mb-1 block">Ancho</label>
```

### 7.4 Mensajes de Validacion

```html
<!-- Error -->
<p class="mt-2 text-sm text-red-600 flex items-center gap-1">
    <svg class="w-4 h-4">icono exclamacion</svg>
    Las dimensiones exceden el limite
</p>

<!-- Exito -->
<p class="mt-2 text-sm text-green-600 flex items-center gap-1">
    <svg class="w-4 h-4">icono check</svg>
    Dimensions validas
</p>

<!-- Advertencia -->
<p class="mt-2 text-sm text-amber-600 flex items-center gap-1">
    <svg class="w-4 h-4">icono triangulo</svg>
    Talla 2XG tiene precio diferente
</p>
```

---

## 8. Badges y Tags

```css
.badge       { @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-medium; }
.badge-blue  { @apply bg-primary-100 text-primary-700; }
.badge-green { @apply bg-green-100 text-green-700; }
.badge-amber { @apply bg-amber-100 text-amber-700; }
```

```html
<span class="badge badge-blue">DTF</span>
<span class="badge badge-green">Alta calidad</span>
<span class="badge badge-amber">Entrega rapida</span>
```

### Circulos Numerados (Steps de proceso)

```html
<span class="w-6 h-6 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
    1
</span>

<!-- Step final (verde) -->
<span class="w-6 h-6 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
    4
</span>
```

---

## 9. Navegacion

### 9.1 Header

```html
<header class="bg-white shadow-sm sticky top-0 z-50">
```

- Fondo blanco con sombra sutil
- Sticky al top
- z-index 50
- Altura: `h-16`
- Container: `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8`

### 9.2 Nav Links

```css
.nav-link        { @apply text-gray-600 hover:text-primary-600 font-medium transition-colors duration-200; }
.nav-link-active { @apply text-primary-600 font-semibold; }
```

```html
<!-- Desktop -->
<a class="nav-link {{ $active ? 'nav-link-active' : '' }}">Inicio</a>

<!-- Mobile (dentro de menu desplegable) -->
<a class="px-4 py-2 rounded-lg {{ $active ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
    Inicio
</a>
```

### 9.3 Cart Badge

```html
<span class="absolute -top-1 -right-1 w-5 h-5 bg-primary-600 text-white text-xs font-bold rounded-full flex items-center justify-center">
    {{ $count }}
</span>
```

### 9.4 Logo

```html
<div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center">
    <svg class="w-6 h-6 text-white">icono pincel</svg>
</div>
<span class="text-xl font-bold text-primary-800 hidden sm:block">SaaS Template</span>
```

---

## 10. Iconografia

### 10.1 Sistema

**Biblioteca:** SVG inline estilo Heroicons (outline)

**Atributos base:**
```html
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="..." />
</svg>
```

- `fill="none"` + `stroke="currentColor"` (outline)
- `stroke-width="2"` standard, `stroke-width="1"` para iconos grandes decorativos

**Excepcion:** Messaging icon usa `fill="currentColor"` (es un logotipo, no outline).

### 10.2 Tamanos

| Tamano | Clase | Uso |
|--------|-------|-----|
| Tiny | `w-4 h-4` | Inline con texto, validacion, footer info |
| Standard | `w-5 h-5` | Botones, headers de card, nav |
| Medium | `w-6 h-6` | Header logo, cart, social links |
| Large | `w-8 h-8` | Page headers, feature icons |
| XL | `w-10 h-10` | Feature backgrounds (dentro de contenedor) |
| Decorativo | `w-12 h-12` | Contact cards, placeholder maps |

### 10.3 Colors de Iconos

| Contexto | Color |
|----------|-------|
| Accion primaria | `text-primary-600` |
| Exito / check | `text-green-600` |
| Advertencia | `text-amber-600` |
| Neutro | `text-gray-400` o `text-gray-500` |
| Sobre color | `text-white` |
| Sobre gradiente | `text-white` |

### 10.4 Icon + Text Pattern

Patron consistente para headers de card:

```html
<h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
    <svg class="w-5 h-5 text-primary-600">...</svg>
    Titulo
</h2>
```

---

## 11. Sombras y Elevacion

| Nivel | Clase | Uso |
|-------|-------|-----|
| Sutil | `shadow-sm` | Header |
| Base | `shadow-md` | Botones primarios |
| Elevado | `shadow-lg` | Cards (`.card`, `.card-hover`) |
| Destacado | `shadow-xl` | Hover de cards, Messaging btn hover |

**Transiciones de sombra:**

| Componente | Base | Hover |
|-----------|------|-------|
| btn-primary | `shadow-md` | `shadow-lg` |
| btn-messaging | `shadow-lg` | `shadow-xl` |
| card-hover | `shadow-lg` | `shadow-xl` |
| size-btn-active | `shadow-lg` | - |

---

## 12. Bordes y Redondeo

### 12.1 Border Radius

| Clase | Valor | Uso |
|-------|-------|-----|
| `rounded-full` | 50% | Botones qty, badges, avatares, cart badge |
| `rounded-2xl` | 1rem | Cards principales, pricing-card, feature icons |
| `rounded-xl` | 0.75rem | Botones, inputs, model-cards, stat boxes, logo |
| `rounded-lg` | 0.5rem | Mobile nav items, discount tiers |

### 12.2 Border Width

| Clase | Uso |
|-------|-----|
| `border` (1px) | Inputs, suggestion cards |
| `border-2` | Model cards, discount tiers activos, btn-secondary |
| `border-t` / `border-b` | Separadores horizontales (horarios, copyright) |

### 12.3 Border Colors

| Contexto | Color |
|----------|-------|
| Default | `border-gray-200` |
| Focus | `border-primary-500` |
| Activo | `border-primary-600` |
| Error | `border-red-500` |
| Sugerencia | `border-amber-200` |
| Separador footer | `border-gray-800` |
| Separador mobile nav | `border-gray-100` |
| Translucido (pricing) | `border-blue-500/30` |
| Glass border | `border-white/30` |

---

## 13. Animaciones y Transiciones

### 13.1 Animaciones Custom

Definidas en `@layer utilities` de `app.css`:

```css
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
/* from: opacity 0 → to: opacity 1 */

.animate-slide-up {
    animation: slideUp 0.3s ease-out;
}
/* from: opacity 0, translateY(10px) → to: opacity 1, translateY(0) */

.animate-pulse-slow {
    animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
```

**Uso:**
- `animate-fade-in`: Mobile menu al abrir, suggestion cards al aparecer
- `animate-slide-up`: Elementos que entran desde abajo
- `animate-pulse-slow`: Indicadores de atencion suave

### 13.2 Transiciones Standard

| Patron | Clases |
|--------|--------|
| Todo (default) | `transition-all duration-200` |
| Solo color | `transition-colors duration-200` |
| Solo transform | `transition-transform duration-300` |

### 13.3 Efectos Hover

| Componente | Efecto |
|-----------|--------|
| Botones | Cambio de bg + elevacion de sombra |
| card-hover | Lift `-translate-y-1` + `shadow-xl` |
| Links | `hover:text-primary-600` |
| Footer links | `hover:text-white` (sobre gray-400) |
| Active press | `active:scale-95` (botones primarios) |
| Size btn activo | `scale-105` |

---

## 14. Spacing y Layout

### 14.1 Contenedores

| Patron | Clases | Uso |
|--------|--------|-----|
| Full width | `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8` | Layout principal |
| Contenido | `max-w-4xl mx-auto` | CTA sections, content pages |
| Hero text | `max-w-3xl mx-auto` | Texto centrado de hero |
| Narrow | `max-w-2xl mx-auto` | Subtitulos, descripciones |

### 14.2 Padding de Secciones

| Seccion | Padding |
|---------|---------|
| Hero | `py-16 lg:py-24` |
| Features / CTA | `py-16` |
| Content sections | `py-12` |
| Page content | `py-8` |
| Footer | `py-12` |

### 14.3 Gaps

| Contexto | Gap |
|----------|-----|
| Botones inline | `gap-2` |
| Icon + text | `gap-2` |
| Nav items | `gap-8` (desktop), `gap-2` (mobile) |
| Size buttons | `gap-3` |
| Card grids | `gap-4` a `gap-8` |
| Layout 2-col | `gap-8` |

### 14.4 Space Between Elements

| Contexto | Clase |
|----------|-------|
| Items en lista | `space-y-2` |
| Cards en column | `space-y-4` o `space-y-6` |
| Secciones internas | `mb-4`, `mb-6`, `mb-8` |
| Separacion mayor | `mb-12`, `mt-12` |

---

## 15. Breakpoints Responsive

### 15.1 Puntos de Quiebre

```
sm:    640px    Tablets pequenas
md:    768px    Tablets
lg:    1024px   Laptops
xl:    1280px   Escritorios
2xl:   1536px   Pantallas grandes
```

### 15.2 Patrones Responsivos Comunes

```html
<!-- Grids -->
grid-cols-1 sm:grid-cols-2 lg:grid-cols-3    <!-- Items, features -->
grid-cols-1 md:grid-cols-4 gap-8             <!-- Footer -->
grid-cols-2 lg:grid-cols-4 gap-4             <!-- Stats hero -->
lg:grid-cols-2 gap-8                         <!-- 2-column layouts -->
lg:grid-cols-3 gap-8                         <!-- Calculator page -->

<!-- Flex direction -->
flex-col sm:flex-row gap-4                   <!-- CTAs hero -->

<!-- Texto responsive -->
text-4xl lg:text-5xl                         <!-- H1 hero -->

<!-- Visibilidad -->
hidden sm:block                              <!-- Logo text en mobile -->
hidden md:flex                               <!-- Desktop nav -->
md:hidden                                    <!-- Mobile menu button -->

<!-- Padding responsive -->
px-4 sm:px-6 lg:px-8                         <!-- Container horizontal -->
```

---

## 16. Elementos Especiales

### 16.1 Wave Separator

SVG que conecta el hero (gradiente) con la siguiente seccion (slate-50):

```html
<div class="relative -mb-1 mt-12">
    <svg viewBox="0 0 1440 120" fill="none" class="w-full">
        <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="#f8fafc"/>
    </svg>
</div>
```

- Fill: `#f8fafc` (slate-50) para unir con el fondo de la siguiente seccion
- `-mb-1` elimina el gap entre secciones

### 16.2 CTA Section Dark

Patron para call-to-action destacado:

```html
<section class="py-16 bg-gray-900 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold mb-4">Tienes dudas?</h2>
        <p class="text-gray-400 mb-8 max-w-2xl mx-auto">...</p>
        <a class="btn-messaging inline-flex">...</a>
    </div>
</section>
```

### 16.3 Feature Icon Container

Cuadrado redondeado con icono grande centrado:

```html
<div class="w-16 h-16 bg-primary-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
    <svg class="w-8 h-8 text-primary-600">...</svg>
</div>

<!-- Variantes de color -->
<div class="w-16 h-16 bg-green-100 ..."><svg class="... text-green-600">...</svg></div>
<div class="w-16 h-16 bg-amber-100 ..."><svg class="... text-amber-600">...</svg></div>
```

### 16.4 Discount Tier Row

```html
<!-- Activo -->
<div class="flex items-center justify-between p-3 rounded-lg bg-primary-50 border-2 border-primary-200">
    <div class="flex items-center gap-3">
        <div class="w-6 h-6 rounded-full bg-primary-600 flex items-center justify-center">
            <svg class="w-4 h-4 text-white">check</svg>
        </div>
        <span class="font-medium text-primary-700">6-12 piezas</span>
    </div>
    <div class="text-right">
        <div class="font-bold text-primary-700">$185 USD</div>
        <div class="text-xs text-green-600">Ahorra $15</div>
    </div>
</div>

<!-- Inactivo -->
<div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
    <div class="flex items-center gap-3">
        <div class="w-6 h-6 rounded-full border-2 border-gray-300"></div>
        <span class="font-medium text-gray-700">13+ piezas</span>
    </div>
    <div class="font-bold text-gray-900">$175 USD</div>
</div>
```

### 16.5 Price Summary Bar

```html
<div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
    <div>
        <div class="text-sm text-gray-500">Precio unitario: <span class="line-through">$200 USD</span></div>
        <div class="text-lg font-bold text-gray-900">Total: $1,000 USD</div>
    </div>
    <div class="text-right">
        <div class="text-2xl font-bold text-primary-600">$185</div>
        <div class="text-xs text-gray-500">por pieza</div>
    </div>
</div>
```

### 16.6 Sticky Sidebar

```html
<div class="sticky top-24 space-y-4">
    <!-- Pricing card + info cards -->
</div>
```

`top-24` compensa la altura del header sticky (h-16 + padding).

---

## 17. Estados Interactivos

### 17.1 Seleccion Activa

| Componente | Estado Activo |
|-----------|---------------|
| Model card | `border-primary-600 bg-primary-50 ring-2 ring-primary-200` |
| Size btn | `bg-primary-600 text-white shadow-lg scale-105` |
| Qty preset | `bg-primary-600 text-white` |
| Side button | `border-primary-600 bg-primary-50` + `text-primary-700` |
| Discount tier | `bg-primary-50 border-2 border-primary-200` |
| Nav link | `text-primary-600 font-semibold` |
| Mobile nav | `bg-primary-50 text-primary-600 font-semibold` |

### 17.2 Disabled

```html
<button class="qty-btn qty-btn-disabled" @disabled(true)>-</button>
```

Visual: `bg-gray-50 text-gray-300 cursor-not-allowed`

### 17.3 Loading (Livewire)

```html
<button wire:click="addToCart" wire:loading.attr="disabled">
    <span wire:loading.remove wire:target="addToCart">Agregar al carrito</span>
    <span wire:loading wire:target="addToCart">Agregando...</span>
</button>
```

---

## 18. Patrones de Pagina

### 18.1 Homepage

```
[Header sticky blanco]
[Hero: gradient-hero + glass stats + wave separator]
[Quick Calculator: bg-slate-50, 2-col grid]
[Features: bg-white, 3-col card-hover grid]
[CTA: bg-gray-900, centrado, btn-messaging]
[Footer: bg-gray-900]
```

### 18.2 Pagina Interior (Cotizar, Catalogo, Contacto, Carrito)

```
[Header sticky blanco]
[Page Header: centrado, icono w-8 text-primary-600 + h1 text-3xl + p text-gray-600]
[Contenido: cards sobre fondo slate-50]
[Footer: bg-gray-900]
```

Patron de header de pagina:

```html
<div class="text-center mb-8">
    <div class="flex items-center justify-center gap-2 mb-2">
        <svg class="w-8 h-8 text-primary-600">...</svg>
        <h1 class="text-3xl font-bold text-gray-900">Titulo</h1>
    </div>
    <p class="text-gray-600">Descripcion breve</p>
</div>
```

### 18.3 Fondo Global

```html
<body class="bg-slate-50 text-gray-900 min-h-screen font-sans antialiased">
```

---

## 19. Referencia Rapida de Clases Custom

Todas las clases definidas en `resources/css/app.css`:

### Botones
| Clase | Descripcion |
|-------|-------------|
| `.btn-primary` | Azul solido, sombra, scale on click |
| `.btn-secondary` | Blanco con borde azul |
| `.btn-messaging` | Verde, bold, con gap para icono |

### Tarjetas
| Clase | Descripcion |
|-------|-------------|
| `.card` | Blanca, rounded-2xl, shadow-lg, p-6 |
| `.card-hover` | Card + lift + shadow en hover |
| `.pricing-card` | Gradiente diagonal azul-indigo |
| `.model-card` | Borde seleccionable con cursor pointer |
| `.model-card-active` | Borde azul + fondo azul claro + ring |
| `.model-card-inactive` | Borde gris + hover azul suave |

### Gradientes y Efectos
| Clase | Descripcion |
|-------|-------------|
| `.gradient-hero` | Horizontal azul a indigo |
| `.gradient-hero-dark` | Horizontal azul oscuro a indigo oscuro |
| `.glass` | Fondo blanco 20% + blur |
| `.glass-dark` | Fondo negro 10% + blur |

### Formularios
| Clase | Descripcion |
|-------|-------------|
| `.input` | Full width, rounded, focus ring azul |
| `.input-error` | Borde rojo + ring rojo |

### Interactivos
| Clase | Descripcion |
|-------|-------------|
| `.size-btn` | Base 14x14, rounded-xl |
| `.size-btn-active` | Azul + sombra + scale 105% |
| `.size-btn-inactive` | Gris + hover |
| `.qty-btn` | Circular 12x12 |
| `.qty-btn-plus` | Azul solido |
| `.qty-btn-minus` | Gris claro |
| `.qty-btn-disabled` | Gris 50 + no-cursor |
| `.qty-preset` | Pill rounded-full |
| `.qty-preset-active` | Azul solido |
| `.qty-preset-inactive` | Gris + hover |

### Badges
| Clase | Descripcion |
|-------|-------------|
| `.badge` | Pill inline, text-xs |
| `.badge-blue` | Fondo azul 100, texto azul 700 |
| `.badge-green` | Fondo verde 100, texto verde 700 |
| `.badge-amber` | Fondo amber 100, texto amber 700 |

### Navegacion
| Clase | Descripcion |
|-------|-------------|
| `.nav-link` | Gris 600, hover azul, medium |
| `.nav-link-active` | Azul 600, semibold |

### Animaciones
| Clase | Descripcion |
|-------|-------------|
| `.animate-fade-in` | Fade in 0.3s |
| `.animate-slide-up` | Slide up + fade 0.3s |
| `.animate-pulse-slow` | Pulse suave 3s loop |
