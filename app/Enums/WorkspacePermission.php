<?php

declare(strict_types=1);

namespace App\Enums;

enum WorkspacePermission: string
{
    case VIEW_DASHBOARD = 'view_dashboard';

    // Invoices
    case CREATE_INVOICES = 'create_invoices';
    case VIEW_INVOICES = 'view_invoices';
    case EDIT_INVOICES = 'edit_invoices';
    case DELETE_INVOICES = 'delete_invoices';
    case EDIT_PRODUCT_PRICE_ON_INVOICES = 'edit_product_price_on_invoices';
    case CREATE_NEW_PRODUCTS_ON_INVOICES = 'create_new_products_on_invoices';

    // Quotations
    case CREATE_QUOTATIONS = 'create_quotations';
    case VIEW_QUOTATIONS = 'view_quotations';
    case EDIT_QUOTATIONS = 'edit_quotations';
    case DELETE_QUOTATIONS = 'delete_quotations';
    case CONVERT_QUOTATIONS_TO_INVOICES = 'convert_quotations_to_invoices';
    case EDIT_PRODUCT_PRICE_ON_QUOTATIONS = 'edit_product_price_on_quotations';
    case CREATE_NEW_PRODUCTS_ON_QUOTATIONS = 'create_new_products_on_quotations';

    // Payments
    case CREATE_PAYMENTS = 'create_payments';
    case VIEW_PAYMENTS = 'view_payments';
    case EDIT_PAYMENTS = 'edit_payments';
    case DELETE_PAYMENTS = 'delete_payments';

    // Contacts
    case CREATE_CONTACTS = 'create_contacts';
    case VIEW_CONTACTS = 'view_contacts';
    case EDIT_CONTACTS = 'edit_contacts';
    case DELETE_CONTACTS = 'delete_contacts';

    // Products
    case CREATE_PRODUCTS = 'create_products';
    case VIEW_PRODUCTS = 'view_products';
    case EDIT_PRODUCTS = 'edit_products';
    case DELETE_PRODUCTS = 'delete_products';
}
