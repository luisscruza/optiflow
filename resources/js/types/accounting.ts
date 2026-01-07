export interface ChartAccount {
    id: number;
    parent_id?: number | null;
    code: string;
    name: string;
    type: 'asset' | 'liability' | 'equity' | 'income' | 'expense';
    is_active: boolean;
    created_at: string;
    updated_at: string;
    parent?: ChartAccount | null;
    children?: ChartAccount[];
}

export interface PaymentConcept {
    id: number;
    name: string;
    code: string;
    chart_account_id: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    chart_account?: ChartAccount;
}

export interface WithholdingType {
    id: number;
    name: string;
    code: string;
    percentage: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface PaymentLine {
    id: number;
    payment_id: number;
    payment_concept_id?: number | null;
    chart_account_id: number;
    description: string;
    quantity: number;
    unit_price: number;
    subtotal: number;
    tax_id?: number | null;
    tax_amount: number;
    total: number;
    sort_order: number;
    created_at: string;
    updated_at: string;
    chart_account?: ChartAccount;
    payment_concept?: PaymentConcept;
}

export interface PaymentWithholding {
    id: number;
    payment_id: number;
    withholding_type_id: number;
    base_amount: number;
    percentage: number;
    amount: number;
    created_at: string;
    updated_at: string;
    withholding_type?: WithholdingType;
}
