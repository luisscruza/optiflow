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
                <AppLogoIcon className=" text-primary-600" />
            )
        }
        </>
    );
}
