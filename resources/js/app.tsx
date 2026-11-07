import { createInertiaApp, router } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { registerSW } from 'virtual:pwa-register';

const appName = import.meta.env.VITE_APP_NAME || 'Sokora';

type TenantBrandingProps = {
    branding?: {
        accent_color?: string | null;
    } | null;
};

/*
 | Tenant accent bridge: the sole place a tenant customisation touches
 | the token system. It overrides the single extension token
 | `--tenant-accent` with an accent chosen from Sokora's curated list;
 | every foundational token stays under platform control.
 */
function applyTenantAccent(props: TenantBrandingProps | undefined) {
    const accent = props?.branding?.accent_color;

    if (typeof document === 'undefined') {
        return;
    }

    if (accent) {
        document.documentElement.style.setProperty('--tenant-accent', accent);
    } else {
        document.documentElement.style.removeProperty('--tenant-accent');
    }
}

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name.startsWith('storefront/'):
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#C6F36A',
    },
});

// The navigate event fires on the initial load and after every visit,
// keeping the tenant accent in sync with the shared branding prop.
router.on('navigate', (event) => {
    const page = event.detail.page;
    applyTenantAccent(page?.props as TenantBrandingProps | undefined);
});

// This will set light / dark mode on load...
initializeTheme();

registerSW({
    immediate: true,
});
