# Resumen de cambios: modulo de productos

Fecha: 2026-02-09

## 1) Pantalla de detalle (`show`)

Se actualizo `resources/js/pages/products/show.tsx` para acercarlo al estilo visual solicitado:

- Reorganizacion del bloque superior con informacion clave del producto.
- Panel de precio/costo mas claro.
- Tabla de stock por almacen/sucursal para productos inventariables.
- Correccion del calculo de impuestos cuando `price` viene tax-exclusive.

### Logica de impuesto aplicada en `show`

- `Precio sin impuesto` = `product.price`.
- `Impuesto` = `price * (taxRate / 100)`.
- `Precio total` = `price + impuesto`.

## 2) ProductType con enum

Se agrego enum y persistencia para tipo de producto:

- Nuevo enum: `app/Enums/ProductType.php`
    - `product`
    - `service`
- Nueva migracion: `database/migrations/tenant/2026_02_09_120337_add_product_type_to_products_table.php`
    - Agrega columna `product_type` con default `product`.
    - Migra productos existentes con `track_stock = false` a `service`.

### Integracion backend

Se actualizaron:

- `app/Models/Product.php`
    - Cast de `product_type` a enum `ProductType`.
- `app/Http/Requests/CreateProductRequest.php`
- `app/Http/Requests/UpdateProductRequest.php`
    - Validacion con `Rule::enum(ProductType::class)`.
    - Normalizacion de `track_stock` segun `product_type`.
    - Soporte para `allow_negative_stock`.
- `app/Actions/CreateProductAction.php`
- `app/Actions/UpdateProductAction.php`
    - Guardan `product_type`.
    - Ajustan `track_stock`/`allow_negative_stock` segun tipo.
- `database/factories/ProductFactory.php`
    - Incluye `product_type` y estados coherentes en factory.

## 3) Formulario `create` y `edit` (UI nueva)

Se redisenaron:

- `resources/js/pages/products/create.tsx`
- `resources/js/pages/products/edit.tsx`

Cambios principales:

- Selector de tipo de producto (Producto/Servicio) estilo tarjeta.
- Sidebar de resumen con switches:
    - Control de cantidades.
    - Venta en negativo.
- Bloque colapsable de opciones avanzadas con detalle por workspace.

## 4) Precio dinamico en formulario

Implementado en `create` y `edit`:

- Formato de captura tipo:
    - `Precio base + Impuesto = Precio total`
- Si cambias `Precio base`, recalcula `total`.
- Si cambias `Precio total`, recalcula `base` con:
    - `base = total / (1 + taxRate/100)`.
- Se corrigio el error de runtime:
    - `tax.rate.toFixed is not a function`
    - ahora se usa `Number(tax.rate).toFixed(2)`.

## 5) Cantidad inicial por workspace

Se habilito en `create` dentro de opciones avanzadas:

- Por cada workspace se puede escribir `Cantidad inicial`.
- Campo enviado como `workspace_initial_quantities` (mapa por `workspace_id`).

Backend relacionado:

- `app/Http/Controllers/ProductController.php`
    - Envia `workspace_stocks` a `create` y `edit`.
- `app/Http/Requests/CreateProductRequest.php`
    - Valida `workspace_initial_quantities` y sus valores.
- `app/Actions/CreateProductAction.php`
    - Crea stock inicial por cada workspace permitido con valor informado.
    - Mantiene fallback al flujo anterior (`initial_quantity`) para compatibilidad.

## 6) Tipado frontend

Actualizado `resources/js/types/index.d.ts`:

- `Product.product_type: 'product' | 'service'`
- `Product.allow_negative_stock?: boolean`
- `Product.unit?: string | null`

## 7) Verificaciones ejecutadas

- `vendor/bin/pint --dirty`
- `npx eslint resources/js/pages/products/create.tsx resources/js/pages/products/edit.tsx`
- `php -l` sobre archivos PHP modificados de producto

No se incluyo corrida de test suite completa en este paso.
