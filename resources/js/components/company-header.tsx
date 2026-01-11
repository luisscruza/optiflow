import { usePage } from '@inertiajs/react';

import type { SharedData } from '@/types';

export function CompanyHeader() {
    const { companyDetails } = usePage<SharedData>().props;

    return (
        <div className="flex items-start gap-6">
            {/* Company Logo */}
            {companyDetails.logo && (
                <div className="flex-shrink-0">
                    <img src={companyDetails.logo} alt={companyDetails.company_name || 'Company Logo'} className="h-20 w-auto object-contain" />
                </div>
            )}

            {/* Company Details */}
            <div className="space-y-1">
                <h1 className="text-2xl font-bold text-gray-900">{companyDetails.company_name || 'Nombre de la empresa'}</h1>
                {companyDetails.tax_id && <p className="text-sm text-gray-600">RNC o Cédula: {companyDetails.tax_id}</p>}
                {companyDetails.address && <p className="text-sm text-gray-600">{companyDetails.address}</p>}
                {companyDetails.phone && <p className="text-sm text-gray-600">{companyDetails.phone}</p>}
                {companyDetails.email && <p className="text-sm text-gray-600">{companyDetails.email}</p>}
            </div>
        </div>
    );
}
