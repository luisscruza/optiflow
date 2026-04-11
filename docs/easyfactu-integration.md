# EasyFactu Integration Plan for OpticanNet

> Integration of EasyFactu's External API v1 into OpticanNet to support Dominican Republic electronic invoicing (e-CF).

## Overview

OpticanNet currently handles regular fiscal invoicing with locally-managed NCF sequences (B01, B02, etc.). This integration adds electronic invoicing (e-CF) support by connecting to EasyFactu's External API v1, which manages eNCF sequences, XML signing, DGII submission, and status tracking.

Only two e-CF types are supported initially:
- **E31** -- Factura de Credito Fiscal Electronica (B2B)
- **E32** -- Factura de Consumo Electronica (B2C/consumers)

---

## Architecture Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Config storage | `CompanyDetail` key-value store | Consistent with existing patterns (e.g., `terms_conditions`). Only ~5 config values needed. |
| Status flow | New statuses (`Submitted`, `DgiiAccepted`, `DgiiRejected`) | More granular tracking of the DGII submission lifecycle. |
| E31/E32 creation | Migration with `DB::table` insert | Guaranteed to run on every environment without manual seeder execution. |
| Settings location | Dedicated "Facturacion Electronica" settings page | Clean separation, easy to find in navigation. |
| Implementation | All at once | Single pass implementation. |

---

## New Invoice Statuses

Adding 3 new cases to `InvoiceStatus` enum:

| Status | Value | Label | Badge Color | Purpose |
|--------|-------|-------|-------------|---------|
| `Submitted` | `submitted` | Enviada a DGII | blue | Invoice sent to DGII, awaiting response |
| `DgiiAccepted` | `dgii_accepted` | Aceptada por DGII | green | DGII accepted the e-CF |
| `DgiiRejected` | `dgii_rejected` | Rechazada por DGII | red | DGII rejected the e-CF |

### Status Flow for Electronic Invoices

```
Draft --> (Edit draft, sync with EasyFactu) --> Emit --> Submitted --> DgiiAccepted --> PendingPayment --> Paid
                                                                   --> DgiiRejected (dead end, needs investigation)
```

The existing `Draft` status is reused for the initial state. Once `DgiiAccepted`, the invoice transitions to `PendingPayment` and the normal payment flow takes over.

### Impact on Existing Methods

- `canBeEdited()`: Also block when `Submitted`, `DgiiAccepted`, `DgiiRejected`
- `canBeDeleted()`: Also block when `Submitted`, `DgiiAccepted`
- `canRegisterPayment()`: Allow for `DgiiAccepted` (in addition to existing allowed statuses)
- `InvoiceController::edit()`: Currently checks `status !== PendingPayment` -- needs to also allow `Draft` for electronic invoices

---

## Sequence Reconciliation Strategy

For electronic invoices (E31/E32), the **EasyFactu API is the source of truth** for sequences:

- `DocumentSubtype.next_number` for E31/E32 is NOT used
- When creating: call `GET /v1/sequences/next?ecf_type=31` to preview the eNCF
- When saving: the `POST /v1/invoices` response returns the actual `encf` -- store this on the invoice
- The `document_number` field on OpticanNet's invoice stores the eNCF returned by EasyFactu (not locally generated)

---

## CompanyDetail Configuration Keys

| Key | Example Value | Purpose |
|-----|---------------|---------|
| `easyfactu_environment` | `TesteCF` | Active DGII environment |
| `easyfactu_api_key_testecf` | `ef_testecf_abc123...` | API key for test environment |
| `easyfactu_api_key_certecf` | `ef_certecf_def456...` | API key for certification environment |
| `easyfactu_api_key_ecf` | `ef_ecf_ghi789...` | API key for production environment |
| `easyfactu_base_url` | `https://app.easyfactu.com/api` | API base URL |

---

## File-by-File Implementation Plan

### 1. Migration: `add_electronic_invoicing_columns_to_invoices_table.php`

Location: `database/migrations/tenant/`

New nullable columns on the `invoices` table:

| Column | Type | Notes |
|--------|------|-------|
| `easyfactu_invoice_id` | string, nullable, indexed | EasyFactu's invoice ID |
| `encf` | string, nullable | eNCF number returned by EasyFactu (e.g., `E310000000001`) |
| `dgii_status` | string, nullable | DGII processing status |
| `dgii_track_id` | string, nullable | DGII tracking ID |
| `dgii_security_code` | string, nullable | Security code for verification |
| `dgii_qr_code_url` | text, nullable | QR code URL for the e-CF |
| `dgii_environment` | string, nullable | Which environment was used (TesteCF/CerteCF/eCF) |
| `is_electronic` | boolean, default: false | Quick flag to distinguish e-CF from regular NCF invoices |

### 2. Migration: `add_is_electronic_to_document_subtypes_table.php`

Location: `database/migrations/tenant/`

Adds `is_electronic` (boolean, default: false) to `document_subtypes` table.

Also inserts two new document subtypes:

| Name | Prefix | Type | is_electronic |
|------|--------|------|---------------|
| Factura de Credito Fiscal Electronica | E31 | invoice | true |
| Factura de Consumo Electronica | E32 | invoice | true |

### 3. `app/Services/EasyFactuService.php` (new)

HTTP client for EasyFactu External API v1. Uses Laravel's `Http::` facade.

**Public methods:**
- `createDraftInvoice(array $payload): array` -- `POST /v1/invoices` with `draft: true`
- `updateDraftInvoice(string $id, array $payload): array` -- `PUT /v1/invoices/{id}`
- `submitInvoice(string $id): array` -- `POST /v1/invoices/{id}/submit`
- `getInvoice(string $id): array` -- `GET /v1/invoices/{id}`
- `getInvoiceStatus(string $id): array` -- `GET /v1/invoices/{id}/status`
- `getNextSequence(string $ecfType): array` -- `GET /v1/sequences/next?ecf_type={type}`
- `isConfigured(): bool` -- checks if API key and environment are set

**Private helpers:**
- `resolveApiKey(): string` -- reads active environment from `CompanyDetail`, returns the corresponding API key
- `buildHeaders(): array` -- `Authorization: Bearer {key}`, `Accept: application/json`, `Idempotency-Key: {ulid}`
- `baseUrl(): string` -- reads from `CompanyDetail` or falls back to default

Generates `Idempotency-Key` header (ULID) for POST requests. Throws typed exceptions on API errors.

### 4. `app/Support/EasyFactuPayloadTransformer.php` (new)

Transforms OpticanNet invoice data into EasyFactu API format.

**Public methods:**
- `toCreatePayload(Invoice $invoice, string $ecfType): array`

**Mapping:**
- `contact.identification_number` --> `buyer_rnc`
- `contact.name` --> `buyer_name`
- `contact.email` --> `buyer_email`
- Invoice items --> EasyFactu items (description, quantity, unit_price, tax_rate, discount_rate)
- Payment method mapping (OpticanNet payment methods --> EasyFactu format)

### 5. `app/Enums/InvoiceStatus.php` (modify)

Add three new cases:

```php
case Submitted = 'submitted';
case DgiiAccepted = 'dgii_accepted';
case DgiiRejected = 'dgii_rejected';
```

With corresponding `label()`, `badgeVariant()`, `badgeClassName()`, and `options()` entries. All labels in Spanish.

### 6. `app/Models/Invoice.php` (modify)

- Update `canBeEdited()`: block for `Submitted`, `DgiiAccepted`, `DgiiRejected`
- Update `canBeDeleted()`: block for `Submitted`, `DgiiAccepted`
- Update `canRegisterPayment()`: allow for `DgiiAccepted`
- Add `isElectronic(): bool` -- checks `is_electronic` column
- Add `isDraft(): bool` -- checks `status === InvoiceStatus::Draft`
- Add `canBeEmitted(): bool` -- electronic + draft + has `easyfactu_invoice_id`
- Add to activity log fields: `dgii_status`, `encf`
- Add new columns to casts if needed

### 7. `app/Models/DocumentSubtype.php` (modify)

- Add `is_electronic` to casts (boolean)
- Add scope `scopeElectronic(Builder $query)` / `scopeNonElectronic(Builder $query)`
- Modify `getNextNcfNumber()` / `generateNCF()` to handle electronic types: for electronic subtypes, return null or a placeholder (sequence comes from EasyFactu API, not local)

### 8. `app/Actions/CreateInvoiceAction.php` (modify)

After creating the local invoice, if `$documentSubtype->is_electronic`:

1. Build payload via `EasyFactuPayloadTransformer::toCreatePayload()`
2. Call `EasyFactuService::createDraftInvoice()`
3. Store response on invoice: `easyfactu_invoice_id`, `encf`, `dgii_environment`
4. Set `is_electronic = true`, status = `Draft`
5. Update `document_number` with the eNCF from response
6. Skip `updateNumerator()` (EasyFactu manages the eNCF sequence)

### 9. `app/Actions/UpdateInvoiceAction.php` (modify)

If `$invoice->is_electronic` and status is `Draft`:

1. After local update, sync changes to EasyFactu via `EasyFactuService::updateDraftInvoice()`
2. Block updates entirely if status is `Submitted`, `DgiiAccepted`, or `DgiiRejected`

### 10. `app/Http/Controllers/InvoiceController.php` (modify)

- `create()`: For electronic document subtypes, fetch NCF from `EasyFactuService::getNextSequence()` instead of `DocumentSubtype::generateNCF()`
- `edit()`: Allow editing when status is `Draft` (for electronic invoices, currently only allows `PendingPayment`)
- Pass `is_electronic` flag and EasyFactu configuration status to frontend props

### 11. `app/Http/Controllers/EmitInvoiceController.php` (new)

Single-action controller: `POST /invoices/{invoice}/emit`

- Validates: invoice is electronic, status is Draft, has `easyfactu_invoice_id`
- Calls `EasyFactuService::submitInvoice()`
- Updates invoice with DGII response (track ID, security code, QR URL, status)
- Transitions status: `Draft` --> `Submitted` (or directly to `DgiiAccepted` if immediate response)
- Activity log entry

### 12. `app/Http/Controllers/RefreshInvoiceStatusController.php` (new)

Single-action controller: `POST /invoices/{invoice}/refresh-status`

- Calls `EasyFactuService::getInvoiceStatus()`
- Updates local `dgii_status`, `dgii_track_id`, `dgii_security_code`, `dgii_qr_code_url`
- If DGII status is "accepted", transitions to `DgiiAccepted` then auto-transitions to `PendingPayment`
- If DGII status is "rejected", transitions to `DgiiRejected`

### 13. `app/Http/Controllers/EasyFactuSettingsController.php` (new)

- `show()`: Renders settings page with current config from `CompanyDetail`
- `update()`: Validates and saves API keys and active environment to `CompanyDetail`
- `testConnection()`: Tests the API key by calling `EasyFactuService::getNextSequence('31')` and returning success/error

### 14. Routes in `routes/tenant.php` (modify)

Add to the authenticated route group:

```php
// Electronic invoicing
Route::post('invoices/{invoice}/emit', EmitInvoiceController::class)->name('invoices.emit');
Route::post('invoices/{invoice}/refresh-status', RefreshInvoiceStatusController::class)->name('invoices.refresh-status');

// EasyFactu settings
Route::get('settings/electronic-invoicing', [EasyFactuSettingsController::class, 'show'])->name('settings.electronic-invoicing');
Route::post('settings/electronic-invoicing', [EasyFactuSettingsController::class, 'update'])->name('settings.electronic-invoicing.update');
Route::post('settings/electronic-invoicing/test', [EasyFactuSettingsController::class, 'testConnection'])->name('settings.electronic-invoicing.test');
```

### 15. Frontend: `resources/js/pages/settings/electronic-invoicing.tsx` (new)

Settings page with:

- **Environment selector**: Radio group for TesteCF / CerteCF / eCF with descriptions
- **API key inputs**: One per environment, masked display with paste-to-set. Only the active environment's key is required.
- **Base URL**: Text input with default value (advanced, collapsible)
- **Test Connection button**: Calls the test endpoint, shows success (next sequence number) or error
- **Explanation text**: Brief description of each DGII environment

### 16. Frontend: `resources/js/components/invoices/invoice-form.tsx` (modify)

When an electronic document subtype is selected:

- Disable manual NCF editing (hide the edit pencil icon on `EditNcfModal` trigger)
- Show "Factura Electronica" badge/indicator near the NCF display
- NCF preview fetched from backend (which calls EasyFactu API internally)
- If EasyFactu is not configured, show a warning with link to settings page

### 17. Frontend: `resources/js/pages/invoices/show.tsx` (modify)

- **"Emitir a DGII" button**: Visible when `is_electronic && status === 'draft'`. Triggers `POST /invoices/{id}/emit` with confirmation dialog.
- **"Refrescar Estado" button**: Visible when `is_electronic && status === 'submitted'`. Triggers `POST /invoices/{id}/refresh-status`.
- **DGII Status section**: New card/section showing eNCF, track ID, security code, QR code image, DGII environment badge
- **Immutability banner**: When emitted (submitted/accepted/rejected), show info banner: "Esta factura electronica ha sido emitida y no puede ser modificada"
- **Hide Edit button**: When status prevents editing (submitted, accepted, rejected)
- **Update `getStatusBadge()`**: Add badge definitions for `submitted`, `dgii_accepted`, `dgii_rejected`

### 18. Frontend: `resources/js/pages/invoices/index.tsx` (modify)

- Add small electronic invoice indicator (icon or badge) in the table rows
- Show DGII status column for electronic invoices (or a combined status indicator)

### 19. Frontend: Navigation (modify)

- Add "Facturacion Electronica" link under settings in the sidebar/navigation component

### 20. Tests

| File | Type | What it tests |
|------|------|---------------|
| `tests/Feature/Tenant/EasyFactuServiceTest.php` | Feature | All service methods with mocked HTTP responses |
| `tests/Feature/Tenant/CreateElectronicInvoiceTest.php` | Feature | Electronic invoice creation flow end-to-end |
| `tests/Feature/Tenant/EmitInvoiceTest.php` | Feature | Emit controller with mocked EasyFactu responses |
| `tests/Feature/Tenant/RefreshInvoiceStatusTest.php` | Feature | Status refresh controller |
| `tests/Unit/Tenant/EasyFactuPayloadTransformerTest.php` | Unit | Payload transformation correctness |
| `tests/Unit/Tenant/InvoiceStatusTest.php` | Unit | Update existing test for new statuses |

---

## EasyFactu External API v1 Reference

### Base URL

Configured per-tenant via `CompanyDetail`. Default: `https://app.easyfactu.com/api`

### Authentication

All requests require `Authorization: Bearer {api_key}` header. API key format: `ef_{environment}_{32_random_chars}`.

### Endpoints Used

| Method | Endpoint | Purpose | Idempotency Required |
|--------|----------|---------|---------------------|
| `POST` | `/v1/invoices` | Create invoice (with `draft: true`) | Yes (`Idempotency-Key` header) |
| `PUT` | `/v1/invoices/{id}` | Update draft invoice | No |
| `POST` | `/v1/invoices/{id}/submit` | Submit draft to DGII | No |
| `GET` | `/v1/invoices/{id}` | Get invoice details | No |
| `GET` | `/v1/invoices/{id}/status` | Refresh DGII status | No |
| `GET` | `/v1/sequences/next?ecf_type={type}` | Preview next eNCF sequence | No |

### Create Invoice Payload (POST /v1/invoices)

```json
{
  "draft": true,
  "ecf_type": "31",
  "issue_date": "2026-04-06",
  "buyer_rnc": "123456789",
  "buyer_name": "Empresa SRL",
  "buyer_email": "empresa@email.com",
  "payment_method": "cash",
  "currency": "DOP",
  "notes": "Optional notes",
  "items": [
    {
      "description": "Product description",
      "quantity": 2,
      "unit_price": 100.00,
      "tax_rate": 18,
      "discount_rate": 0
    }
  ]
}
```

### Invoice Response Format

```json
{
  "invoice": {
    "id": "01JREXXXXXXXXXXXXXXXXXXXXXXX",
    "encf": "E310000000001",
    "ecf_type": "31",
    "status": "draft",
    "dgii_status": null,
    "dgii_track_id": null,
    "security_code": null,
    "qr_code_url": null,
    "total_amount": "236.00",
    "issue_date": "2026-04-06",
    "created_at": "2026-04-06T12:00:00.000000Z"
  }
}
```

### Status Response (after DGII submission)

```json
{
  "invoice": {
    "id": "01JREXXXXXXXXXXXXXXXXXXXXXXX",
    "encf": "E310000000001",
    "status": "accepted",
    "dgii_status": "Aceptado",
    "dgii_track_id": "track-uuid-from-dgii"
  }
}
```

---

## Payment Method Mapping

| OpticanNet | EasyFactu |
|------------|-----------|
| `cash` | `cash` |
| `transfer` | `transfer` |
| `check` | `check` |
| `credit_card` | `credit_card` |
| `debit_card` | `debit_card` |
| `other` | `other` |

> Note: Verify these mappings match EasyFactu's accepted values. The EasyFactu API stores `payment_method` as a free string, so these should work directly.

---

## Open Questions / Future Work

1. **Certificate status endpoint**: May need to be added to EasyFactu API first. Not part of initial integration.
2. **Credit notes / debit notes**: Only E31 and E32 are supported initially. E33 (nota de credito) and E34 (nota de debito) can be added later.
3. **Webhook support**: EasyFactu does not currently have webhooks for status changes. Polling via "Refresh Status" button is the initial approach. Could add a scheduled job later.
4. **Multi-workspace**: Each workspace could theoretically have different EasyFactu configurations. Currently using tenant-level `CompanyDetail` which is shared across workspaces. If per-workspace config is needed, would need to move to workspace settings JSON.
5. **Error recovery**: If the EasyFactu API call fails during invoice creation, the local invoice is still created but without `easyfactu_invoice_id`. Need a retry mechanism or manual re-sync button.
