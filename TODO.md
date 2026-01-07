# Multi-Tax Feature Implementation

## Completed ✅

- [x] Create migration to add `type` column to taxes table
- [x] Create `invoice_item_tax` pivot table migration
- [x] Create `quotation_item_tax` pivot table migration
- [x] Update `TaxType` enum with `options()` method
- [x] Update `Tax` model with type field, relationships, and `isInUse()` method
- [x] Update `InvoiceItem` model with `taxes()` BelongsToMany relationship
- [x] Update `QuotationItem` model with `taxes()` BelongsToMany relationship
- [x] Update Tax edit page with type field and isInUse restrictions
- [x] Update TypeScript `Tax` interface with `type` field
- [x] Define in `TaxType` which types are accumulative vs exclusive (`isExclusive()`, `isAccumulative()` methods)
- [x] Update `TaxController@index` to return taxes grouped by type
- [x] Update `CreateInvoiceItemAction` to handle multiple taxes per item (sync pivot table)
- [x] Update `UpdateInvoiceItemAction` to handle multiple taxes per item
- [x] Update `CreateQuotationAction` to handle multiple taxes per item
- [x] Update `UpdateQuotationAction` to handle multiple taxes per item
- [x] Create `ItemTax` interface for pivot data
- [x] Create `TaxTypeGroup` and `TaxesGroupedByType` TypeScript interfaces
- [x] Update `DocumentItem` TypeScript interface with `taxes` field
- [x] Create `TaxMultiSelect` component with grouped dropdown by tax type
- [x] Update `InvoiceController` create/edit to pass `taxesGroupedByType`
- [x] Update `QuotationController` create/edit to pass `taxesGroupedByType`
- [x] Update `invoices/create.tsx` with multi-tax selection
- [x] Update `quotations/create.tsx` with multi-tax selection

## Remaining Tasks 📋

### Database & Backend

- [ ] Remove the legacy `tax_id`, `tax_rate`, `tax_amount` columns from `invoice_items` table
- [ ] Remove the legacy `tax_id`, `tax_rate`, `tax_amount` columns from `quotation_items` table
- [ ] Update invoice/quotation item resources to include taxes relationship

### Invoice/Quotation Edit Pages

- [ ] Update `invoices/edit.tsx`:
    - Replace single `tax_rate` with multi-tax selection
    - Load existing taxes from pivot relationship

- [ ] Update `quotations/edit.tsx`:
    - Replace single `tax_rate` with multi-tax selection
    - Load existing taxes from pivot relationship

### Testing

- [ ] Write feature tests for multi-tax invoice creation
- [ ] Write feature tests for multi-tax quotation creation
- [ ] Write unit tests for tax calculation logic
- [ ] Run `vendor/bin/pint --dirty` to fix formatting
- [ ] Run full test suite

### Cleanup

- [ ] Remove legacy `tax_id`, `tax_rate`, `tax_amount` columns from invoice_items (after migration)
- [ ] Remove legacy `tax_id`, `tax_rate`, `tax_amount` columns from quotation_items (after migration)
- [ ] Remove legacy `tax()` BelongsTo relationships from models

## Notes

- Taxes are grouped by `TaxType` enum: `itbis`, `isc`, `propina_legal`, `exento`, `no_facturable`, `other`. We may add more types later.
- Pivot tables store `rate` and `amount` snapshots per item-tax relationship
- Taxes with existing usage (`isInUse()`) should have restricted editing
