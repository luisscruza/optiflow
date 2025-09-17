import { usePage } from '@inertiajs/react';
import AppLogoIcon from './app-logo-icon';
import { SharedData } from '@/types';

export default function AppLogo() {
        const companyDetails = usePage<SharedData>().props.companyDetails;
    
    return (
        <>  {
companyDetails.logo ? (
                <img src={companyDetails.logo} alt="Company Logo" className="h-8 w-8 rounded-full object-cover" />
            ) : (
                <AppLogoIcon className="h-8 w-8 text-primary-600" />
            )
        }
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">{companyDetails.company_name}</span>
            </div>
        </>
    );
}
