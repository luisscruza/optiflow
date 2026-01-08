import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href?: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    items?: NavItem[];
}

export interface Currency {
    id: number;
    name: string;
    code: string;
    symbol: string;
    is_default: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface CommentData {
    id: number;
    comment: string;
    created_at: string;
    edited_at?: string | null;
    commentator: {
        id: number;
        name: string;
        email: string;
    };
    comments?: CommentData[];
}

export interface BankAccount {
    id: number;
    name: string;
    type: string;
    type_label?: string;
    currency_id: number;
    account_number?: string | null;
    initial_balance: number;
    initial_balance_date: string;
    description: string;
    balance: number;
    is_system_account: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    currency?: Currency;
    recent_payments?: Array<{
        id: number;
        amount: number;
        payment_date: string;
        payment_method: string;
    }>;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    workspace?: WorkspaceData;
    userPermissions: string[];
    sidebarOpen: boolean;
    companyDetails: {
        company_name: string;
        address?: string | null;
        phone?: string | null;
        email?: string | null;
        tax_id?: string | null;
        currency?: string | null;
        logo?: string | null;
    };
    defaultCurrency?: Currency | null;
    newlyCreatedContact?: Contact | null;
    workspaceUsers: User[];
    unreadNotifications: number;
    impersonating: boolean;
    flash?: {
        success?: string;
        error?: string;
        info?: string;
        warning?: string;
    };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    current_workspace_id?: number | null;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Salesman {
    id: number;
    name: string;
    surname: string;
    full_name: string;
    user_id?: number | null;
    workspace_id: number;
    created_at?: string;
    updated_at?: string;
}

export interface Workspace {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    owner_id: number;
    settings?: Record<string, unknown> | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    owner?: User;
    users?: User[];
    users_count?: number;
    is_owner?: boolean;
    pivot?: {
        role: string;
        joined_at: string;
    };
}

export interface WorkspaceData {
    current: Workspace | null;
    available: Workspace[];
}

export interface Tax {
    id: number;
    name: string;
    type: string;
    rate: number;
    is_default: boolean;
    created_at: string;
    updated_at: string;
    products_count?: number;
    document_items_count?: number;
    invoice_items_count?: number;
    quotation_items_count?: number;
}

export interface PaginatedTaxes {
    data: Tax[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    links: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
}

export interface TaxFilters {
    search?: string;
}

export interface ProductStock {
    id: number;
    product_id: number;
    workspace_id: number;
    quantity: number;
    minimum_quantity: number;
    created_at: string;
    updated_at: string;
    product?: Product;
}

export interface StockMovement {
    id: number;
    product_id: number;
    workspace_id: number;
    from_workspace_id?: number | null;
    to_workspace_id?: number | null;
    type: 'in' | 'out' | 'adjustment' | 'transfer' | 'initial' | 'transfer_in' | 'transfer_out' | 'set_quantity' | 'add_quantity' | 'remove_quantity';
    quantity: number;
    reference_number?: string | null;
    notes?: string | null;
    unit_cost?: number | null;
    created_at: string;
    updated_at: string;
    created_by?: User;
    product?: Product;
    from_workspace?: Workspace;
    to_workspace?: Workspace;
}

export interface StockAdjustment {
    id: number;
    product_id: number;
    workspace_id: number;
    adjustment_type: 'set_quantity' | 'add_quantity' | 'remove_quantity';
    quantity: number;
    previous_quantity?: number;
    new_quantity?: number;
    note?: string | null;
    created_by: number;
    created_at: string;
    updated_at: string;
    product?: Product;
    created_by?: User;
}

export interface StockTransfer {
    id: number;
    product_id: number;
    from_workspace_id: number;
    to_workspace_id: number;
    quantity: number;
    note?: string | null;
    created_by: number;
    created_at: string;
    updated_at: string;
    product?: Product;
    from_workspace?: Workspace;
    to_workspace?: Workspace;
    created_by?: User;
}

export interface PaginatedStockMovements {
    data: StockMovement[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    links: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
}

export interface PaginatedStockAdjustments {
    data: ProductStock[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    links: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
}

export interface Product {
    id: number;
    name: string;
    sku: string;
    description?: string | null;
    price: number;
    cost?: number | null;
    track_stock: boolean;
    default_tax_id?: number | null;
    created_at: string;
    updated_at: string;
    default_tax?: Tax;
    stock_in_current_workspace?: ProductStock;
    stocks?: ProductStock[];
    stock_movements?: StockMovement[];
    current_stock?: ProductStock;
    stock_quantity?: number;
    minimum_quantity?: number;
    stock_status?: 'not_tracked' | 'out_of_stock' | 'low_stock' | 'in_stock';
}

export interface PaginationLink {
    url: string | null;
    label: string;
    page?: number | null;
    active: boolean;
}

export interface PaginatedResponse<T = any> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    first_page_url: string;
    last_page_url: string;
    next_page_url: string | null;
    prev_page_url: string | null;
    path: string;
    links: PaginationLink[];
}

export interface PaginatedProducts extends PaginatedResponse<Product> {}

export interface ProductFilters {
    search?: string;
    track_stock?: boolean;
    low_stock?: boolean;
}

export interface Address {
    id: number;
    contact_id: number;
    type: string;
    province?: string | null;
    municipality?: string | null;
    country?: string | null;
    description?: string | null;
    is_primary: boolean;
    created_at: string;
    updated_at: string;
    full_address?: string | null;
}

export interface Contact {
    id: number;
    workspace_id: number;
    name: string;
    identification_type?: string | null;
    identification_number?: string | null;
    email?: string | null;
    phone_primary?: string | null;
    phone_secondary?: string | null;
    mobile?: string | null;
    fax?: string | null;
    contact_type: 'customer' | 'supplier';
    status: 'active' | 'inactive';
    observations?: string | null;
    credit_limit: number;
    created_at: string;
    updated_at: string;
    addresses?: Address[];
    primary_address?: Address | null;
    identification_object?: {
        type: string;
        number: string;
    } | null;
    documents_count?: number;
    supplied_stocks_count?: number;
}

export interface MasterTableData {
    id: number;
    name: string;
    alias: string;
    description?: string;
    items: Array<{
        id: number;
        mastertable_id: number;
        name: string;
    }>;
}

// Alias for consistency
export type Mastertable = MasterTableData;

export interface MastertableItem {
    id: number;
    mastertable_id: number;
    name: string;
    description?: string | null;
    position?: number;
    is_active?: boolean;
}

export interface PaginatedContacts {
    data: Contact[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    links: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
}

export interface ContactFilters {
    search?: string;
}

export interface PrescriptionFilters {
    search?: string;
}

export interface IdentificationTypeOption {
    value: string;
    label: string;
}

export type ContactType = 'customer' | 'supplier' | 'optometrist';
export type IdentificationType = 'cedula' | 'pasaporte' | 'rnc' | 'nif' | 'nie';

export type ContactType = 'customer' | 'supplier';
export type IdentificationType = 'cedula' | 'nit' | 'passport' | 'rut' | 'other';

export interface DocumentSubtype {
    id: number;
    name: string;
    prefix: string;
    next_number: number;
    is_default: boolean;
    valid_until_date?: string | null;
    start_number: number;
    end_number: number;
    created_at: string;
    updated_at: string;
}

/**
 * Represents a tax applied to an item in the pivot table.
 */
export interface ItemTax {
    id: number;
    name: string;
    type: string;
    rate: number;
    is_default: boolean;
    pivot: {
        rate: number;
        amount: number;
    };
}

/**
 * Tax type group with metadata for multi-select component.
 */
export interface TaxTypeGroup {
    label: string;
    isExclusive: boolean;
    taxes: Tax[];
}

/**
 * Taxes grouped by type for the multi-select component.
 */
export type TaxesGroupedByType = Record<string, TaxTypeGroup>;

export interface DocumentItem {
    id: number;
    invoice_id: number;
    product_id?: number | null;
    description: string;
    quantity: string | number;
    unit_price: string | number;
    discount_rate: string | number;
    discount_amount: string | number;
    tax_rate: string | number;
    tax_amount: string | number;
    total: string | number;
    product?: Product;
    tax?: Tax;
    /** Multiple taxes per item (many-to-many relationship) */
    taxes?: ItemTax[];
}

export interface Invoice {
    id: number;
    workspace_id: number;
    contact_id: number;
    type: 'invoice' | 'quotation' | 'receipt';
    document_subtype_id: number;
    status:
        | 'draft'
        | 'sent'
        | 'paid'
        | 'overdue'
        | 'cancelled'
        | 'accepted'
        | 'rejected'
        | 'expired'
        | 'converted'
        | 'pending_payment'
        | 'partially_paid';
    amount_due: number;
    document_number: string;
    issue_date: string;
    due_date: string;
    total_amount: number;
    subtotal_amount: number;
    tax_amount: number;
    discount_amount: number;
    notes?: string | null;
    created_by: number;
    currency_id: number;
    created_at: string;
    updated_at: string;
    contact: Contact;
    document_subtype: DocumentSubtype;
    created_by_user?: User;
    items?: DocumentItem[];
    status_config: InvoiceStatusConfig;
    payment_term: string;
    payments?: Payment[];
    comments?: CommentData[];
    salesmen?: Salesman[];
}

import type { PaymentLine, PaymentWithholding } from './accounting';

export interface Payment {
    id: number;
    payment_type: 'invoice' | 'other_income';
    payment_number: string;
    invoice_id?: number | null;
    contact_id?: number | null;
    bank_account_id: number;
    currency_id: number;
    amount: number;
    subtotal_amount: number;
    tax_amount: number;
    withholding_amount: number;
    payment_date: string;
    payment_method: 'cash' | 'transfer' | 'check' | 'credit_card' | 'debit_card' | 'other';
    note?: string | null;
    status: 'completed' | 'voided';
    created_at: string;
    updated_at: string;
    bank_account?: BankAccount;
    currency?: Currency;
    invoice?: Invoice;
    contact?: Contact;
    lines?: PaymentLine[];
    withholdings?: PaymentWithholding[];
}

export interface BankAccount {
    id: number;
    name: string;
    account_number?: string | null;
    bank_name?: string | null;
    is_system_account: boolean;
    created_at: string;
    updated_at: string;
}

export interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
    created_at: string;
    updated_at: string;
}

export interface Prescription {
    id: number;
    patient: Contact;
    optometrist?: Contact;
    workspace: Workspace;
    created_at: string;
    human_readable_date: string;

    // Many-to-many relationships
    motivos?: MastertableItem[];
    estadoActual?: MastertableItem[];
    historiaOcularFamiliar?: MastertableItem[];
    lentesRecomendados?: MastertableItem[];
    gotasRecomendadas?: MastertableItem[];
    monturasRecomendadas?: MastertableItem[];

    // Custom clinical history text fields
    motivos_consulta_otros?: string;
    estado_salud_actual_otros?: string;
    historia_ocular_familiar_otros?: string;

    // Lensometría
    lensometria_od?: string;
    lensometria_oi?: string;
    lensometria_add?: string;

    // Agudeza Visual - Lejana
    av_lejos_sc_od?: string;
    av_lejos_sc_oi?: string;
    av_lejos_cc_od?: string;
    av_lejos_cc_oi?: string;
    av_lejos_ph_od?: string;
    av_lejos_ph_oi?: string;

    // Agudeza Visual - Cercana
    av_cerca_sc_od?: string;
    av_cerca_sc_oi?: string;
    av_cerca_cc_od?: string;
    av_cerca_cc_oi?: string;
    av_cerca_ph_od?: string;
    av_cerca_ph_oi?: string;

    // Biomicroscopía
    biomicroscopia_od_cejas?: string;
    biomicroscopia_od_parpados?: string;
    biomicroscopia_od_pestanas?: string;
    biomicroscopia_od_conjuntiva?: string;
    biomicroscopia_od_cornea?: string;
    biomicroscopia_od_camara_anterior?: string;
    biomicroscopia_od_iris?: string;
    biomicroscopia_od_cristalino?: string;

    biomicroscopia_oi_cejas?: string;
    biomicroscopia_oi_parpados?: string;
    biomicroscopia_oi_pestanas?: string;
    biomicroscopia_oi_conjuntiva?: string;
    biomicroscopia_oi_cornea?: string;
    biomicroscopia_oi_camara_anterior?: string;
    biomicroscopia_oi_iris?: string;
    biomicroscopia_oi_cristalino?: string;

    // Oftalmoscopía
    oftalmoscopia_od_papila?: string;
    oftalmoscopia_od_excavacion?: string;
    oftalmoscopia_od_vasos?: string;
    oftalmoscopia_od_retina?: string;
    oftalmoscopia_od_macula?: string;
    oftalmoscopia_od_fijacion?: string;

    oftalmoscopia_oi_papila?: string;
    oftalmoscopia_oi_excavacion?: string;
    oftalmoscopia_oi_vasos?: string;
    oftalmoscopia_oi_retina?: string;
    oftalmoscopia_oi_macula?: string;
    oftalmoscopia_oi_fijacion?: string;

    oftalmoscopia_observaciones?: string;

    // Queratometría
    quera_od_horizontal?: string;
    quera_od_vertical?: string;
    quera_od_eje?: string;
    quera_od_dif?: string;

    quera_oi_horizontal?: string;
    quera_oi_vertical?: string;
    quera_oi_eje?: string;
    quera_oi_dif?: string;

    // Presión Intraocular
    presion_od?: string;
    presion_od_hora?: string;
    presion_oi?: string;
    presion_oi_hora?: string;

    // Refracción
    refraccion_od_esfera?: string;
    refraccion_od_cilindro?: string;
    refraccion_od_eje?: string;
    refraccion_subjetivo_od_adicion?: string;

    refraccion_oi_esfera?: string;
    refraccion_oi_cilindro?: string;
    refraccion_oi_eje?: string;
    refraccion_subjetivo_oi_adicion?: string;

    retinoscopia_dinamica?: boolean;
    retinoscopia_estatica?: boolean;
    refraccion_observaciones?: string;

    // Refracción Modal Fields
    cicloplegia_medicamento?: string;
    cicloplegia_num_gotas?: string;
    cicloplegia_hora_aplicacion?: string;
    cicloplegia_hora_examen?: string;

    refraccion_cicloplejica_od_esfera?: string;
    refraccion_cicloplejica_od_cilindro?: string;
    refraccion_cicloplejica_od_eje?: string;
    refraccion_cicloplejica_od_av?: string;

    refraccion_cicloplejica_oi_esfera?: string;
    refraccion_cicloplejica_oi_cilindro?: string;
    refraccion_cicloplejica_oi_eje?: string;
    refraccion_cicloplejica_oi_av?: string;

    refraccion_subjetivo_lejos_od_esfera?: string;
    refraccion_subjetivo_lejos_od_cilindro?: string;
    refraccion_subjetivo_lejos_od_eje?: string;
    refraccion_subjetivo_lejos_od_av?: string;

    refraccion_subjetivo_lejos_oi_esfera?: string;
    refraccion_subjetivo_lejos_oi_cilindro?: string;
    refraccion_subjetivo_lejos_oi_eje?: string;
    refraccion_subjetivo_lejos_oi_av?: string;

    refraccion_subjetivo_cerca_od_esfera?: string;
    refraccion_subjetivo_cerca_od_cilindro?: string;
    refraccion_subjetivo_cerca_od_eje?: string;
    refraccion_subjetivo_cerca_od_adicion?: string;
    refraccion_subjetivo_cerca_od_av?: string;

    refraccion_subjetivo_cerca_oi_esfera?: string;
    refraccion_subjetivo_cerca_oi_cilindro?: string;
    refraccion_subjetivo_cerca_oi_eje?: string;
    refraccion_subjetivo_cerca_oi_adicion?: string;
    refraccion_subjetivo_cerca_oi_av?: string;
}

export interface InvoiceStatusConfig {
    value: 'paid' | 'partially_paid' | 'pending_payment' | 'cancelled';
    label: string;
    variant: 'default' | 'secondary' | 'destructive' | 'outline' | null | undefined;
    className: string;
}

export interface PaginatedInvoices extends PaginatedResponse<Invoice> {}

export interface PaginatedPrescriptions extends PaginatedResponse<Prescription> {}

export interface InvoiceFilters {
    search?: string;
    status?: string;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    links: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
}

export interface ProductImport {
    id: number;
    filename: string;
    original_filename: string;
    file_path: string;
    status: 'pending' | 'mapping' | 'processing' | 'completed' | 'failed';
    headers?: string[] | null;
    column_mapping?: Record<string, string> | null;
    import_data?: any[] | null;
    validation_errors?: any[] | null;
    import_summary?: {
        products_created?: number;
        products_updated?: number;
        errors?: string[];
    } | null;
    total_rows?: number | null;
    processed_rows?: number | null;
    error_count: number;
    completed_at?: string | null;
    created_at: string;
    updated_at: string;
}

// Workflow Types (Kanban for lens processing)
export type WorkflowJobPriority = 'low' | 'medium' | 'high' | 'urgent';
export type WorkflowFieldType = 'text' | 'textarea' | 'number' | 'date' | 'select';

export interface WorkflowField {
    id: string;
    workflow_id: string;
    name: string;
    key: string;
    type: WorkflowFieldType;
    mastertable_id?: number | null;
    is_required: boolean;
    placeholder?: string | null;
    default_value?: string | null;
    position: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    workflow?: Workflow;
    mastertable?: Mastertable;
}

export interface Workflow {
    id: string;
    name: string;
    is_active: boolean;
    invoice_requirement: 'optional' | 'required' | null;
    prescription_requirement: 'optional' | 'required' | null;
    created_at: string;
    updated_at: string;
    stages?: WorkflowStage[];
    stages_count?: number;
    fields?: WorkflowField[];
    fields_count?: number;
    jobs_count?: number;
    pending_jobs_count?: number;
    completed_jobs_count?: number;
    overdue_jobs_count?: number;
}

export interface WorkflowStage {
    id: string;
    workflow_id: string;
    name: string;
    description?: string | null;
    color: string;
    position: number;
    is_active: boolean;
    is_initial: boolean;
    is_final: boolean;
    created_at: string;
    updated_at: string;
    workflow?: Workflow;
    jobs?: WorkflowJob[];
    jobs_count?: number;
    pending_jobs_count?: number;
    completed_jobs_count?: number;
}

// Cursor paginated response from Inertia::scroll()
export interface CursorPaginatedData<T> {
    data: T[];
}

// Media Library types
export interface Media {
    id: number;
    model_type: string;
    model_id: string;
    uuid: string;
    collection_name: string;
    name: string;
    file_name: string;
    mime_type: string;
    disk: string;
    size: number;
    manipulations: Record<string, unknown>[];
    custom_properties: Record<string, unknown>;
    generated_conversions: Record<string, boolean>;
    responsive_images: Record<string, unknown>[];
    order_column: number;
    created_at: string;
    updated_at: string;
    original_url: string;
    preview_url: string;
}

export interface WorkflowJob {
    id: string;
    workflow_id: string;
    workflow_stage_id: string;
    contact_id?: number | null;
    invoice_id?: number | null;
    prescription_id?: number | null;
    notes?: string | null;
    metadata?: Record<string, string | number | boolean | null> | null;
    priority?: WorkflowJobPriority | null;
    due_date?: string | null;
    started_at?: string | null;
    completed_at?: string | null;
    canceled_at?: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    workflow?: Workflow;
    workflow_stage?: WorkflowStage;
    contact?: Contact;
    invoice?: Invoice;
    prescription?: Prescription;
    comments?: CommentData[];
    events?: WorkflowEvent[];
    workspace: Workspace;
    media?: Media[];
}

export type WorkflowEventType = 'stage_changed' | 'note_added' | 'priority_updated' | 'metadata_updated' | 'images_added' | 'images_removed';

export interface WorkflowEventMetadata {
    from_priority?: string | null;
    to_priority?: string | null;
    changed_fields?: Record<string, { from: unknown; to: unknown }>;
    count?: number;
    file_names?: string[];
}

export interface WorkflowEvent {
    id: string;
    workflow_job_id: string;
    from_stage_id?: string | null;
    to_stage_id?: string | null;
    event_type: WorkflowEventType;
    event_type_label: string;
    user_id?: number | null;
    metadata?: WorkflowEventMetadata | null;
    created_at: string;
    updated_at: string;
    workflow_job?: WorkflowJob;
    from_stage?: WorkflowStage | null;
    to_stage?: WorkflowStage | null;
    user?: User | null;
}
