import { usePage } from '@inertiajs/react';

import type { SharedData, Workspace } from '@/types';

export function CompanyHeader({ workspace }: { workspace?: Workspace }) {
    const { companyDetails } = usePage<SharedData>().props;
    const address = workspace?.address ?? companyDetails.address;
    const phone = workspace?.phone ?? companyDetails.phone;

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
                <p className="text-sm text-gray-600"> {workspace?.name || ''}</p>
                {companyDetails.tax_id && <p className="text-sm text-gray-600">RNC o Cédula: {companyDetails.tax_id}</p>}
                {address && <p className="text-sm text-gray-600">{address}</p>}
                {phone && <p className="text-sm text-gray-600">{phone}</p>}
            </div>
        </div>
    );
}
