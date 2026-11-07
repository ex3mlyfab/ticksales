import type { Auth } from '@/types/auth';
import type { Branch, Branding, Tenant } from '@/types/navigation';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            currency: string;
            auth: Auth;
            sidebarOpen: boolean;
            tenant?: Tenant | null;
            tenants?: Tenant[];
            branch?: Branch | null;
            branding?: Branding;
            permissions?: string[];
            [key: string]: unknown;
        };
    }
}
