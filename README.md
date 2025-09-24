<img width="500" height="150" alt="logo" src="https://github.com/user-attachments/assets/4b789ec2-8cc5-4919-9b76-56821393b0e1" />

> [!WARNING]
> This project is currently in progress and may not be feature complete.
> 
# Optiflow

A modern web application especializing in operations management for Optical Stores and similar businesses. Built with Laravel and React.

## Features

- **Multi-Workspace Management:** - Separate business contexts with role-based access
- **Product Catalog** - Complete product management with pricing and stock tracking
-  **Inventory Management** - Real-time stock tracking, movements, and transfers between workspaces
-  **Contact Management** - Comprehensive customer and supplier directory
-  **Tax Management** - Flexible tax rates and calculations
-  **Quotations** - Create and manage quotations with conversion to invoices
-  **Invoicing** - Complete invoicing system with PDF generation
-  **Multi-Tenancy** - Full tenant isolation using Laravel Tenancy

## Tech stack

- Laravel 12 with PHP 8.3+.
- React 19 and Inertia.js v2+.
- SQLite database.
- Tailwind for styiling.
- Modern PHP tooling such as Laravel Pint, PHPStan, Rector...
- Testing with Pest V4


## Get started

### Prerequisites

- PHP 8.3+
- Node.js 20+
- Composer
- Laravel Herd (recommended) or any local development environment

### Installation

1. Clone the repository:
```bash
git clone <https://github.com/luisscruza/optiflow/>
cd optiflow
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Set up environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Run migrations and seed data:
```bash
php artisan migrate
```

5. You may run the DGII data sync with:
```bash
php artisan sync:dgii
```

### Access the application

- **Tenant aware**: https://tenant.optiflow.test (You first may create a Tenant within the central application)
- **Admin Panel**: https://optiflow.test/admin (Central application)

## Development
```bash

# Code quality
composer run lint       # Laravel Pint formatting
composer run test      # Run all tests
composer run refactor  # Rector refactoring

# Testing
composer run @test:unit

# You may run the entire test suite...
composer run test
```

### Multi-Tenancy

The application uses Laravel Tenancy for complete tenant isolation:

For more information regarding Laravel Tenancy, visit the docs https://tenancyforlaravel.com/ 

## Contributing

1. Follow the existing code style (Laravel Pint handles formatting)
2. Write comprehensive tests using Pest
3. Use type declarations and PHPDoc blocks
4. Follow the established Action pattern for business logic
5. Ensure all new features are workspace-scoped

## License

This project is proprietary software. All rights reserved.

---

Built with ❤️ by Luis Cruz
