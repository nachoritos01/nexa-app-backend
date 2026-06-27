# Test Plan — Bugfix: Customer Address Label Column Too Short

**Version:** 1.0
**Date:** 2026-03-04
**Feature:** Fix varchar(20) truncation on `customer_addresses.label` column
**Branch:** develop
**Bugfix:** Migration to increase `label` from varchar(20) to varchar(100)

## 1. Objetivo

Validar que la creacion de orders con envio a domicilio funciona correctamente cuando el distrito/colonia del customer excede 20 caracteres, ya que este valor se usa como `label` en `customer_addresses`.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Creacion de order | Wizard completo con envio a domicilio y direccion con distrito largo |
| Guardar direccion | Checkbox "Guardar esta direccion para el customer" persiste correctamente |
| Reutilizar direccion | Dropdown de direcciones guardadas autocompleta campos en nuevo order |
| Cotizador de envio | Modal de cotizacion con Envia.com funciona post-fix |

### Fuera de alcance

- Edicion de direcciones guardadas
- Portal del customer
- Cotizador publico `/quote`

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Manual (MCP) | Chrome DevTools via MCP | Flujo completo de creacion de order |
| Regresion | Creacion de multiples orders | Check que direcciones se guardan y reutilizan |

## 4. Criterios de entrada

- [x] Migracion `alter_customer_addresses_increase_label_length` creada
- [x] Column `label` ampliada de varchar(20) a varchar(100)
- [x] Migracion ejecutada en local

## 5. Criterios de salida

- [x] Order con distrito >20 chars se crea sin error SQLSTATE[22001]
- [x] Direccion se guarda correctamente en `customer_addresses`
- [x] Direccion guardada aparece en dropdown al crear nuevo order
- [x] Campos se autocompletar al seleccionar direccion guardada
- [x] Cotizador de envio funciona con direccion guardada

## 6. Riesgos

| Riesgo | Mitigacion |
|--------|------------|
| Labels existentes mayores a 100 chars | Improbable — colonias/distritos rara vez exceden 50 chars |
| Migracion destructiva en rollback | Down migration restaura varchar(20), datos largos se truncarian |
