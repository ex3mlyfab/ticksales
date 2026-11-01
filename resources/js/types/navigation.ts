import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};

export type Tenant = {
    id: string;
    name: string;
    slug?: string | null;
    email?: string | null;
    currency?: string;
    timezone?: string | null;
    locale?: string | null;
    is_current?: boolean;
};

export type Branch = {
    id: string;
    tenant_id: string;
    name: string;
    code?: string | null;
    email?: string | null;
    phone?: string | null;
    address?: string | null;
    is_main?: boolean;
    is_active?: boolean;
};

export type Branding = {
    name: string;
    logo_path?: string | null;
    favicon_path?: string | null;
    primary_color?: string;
    secondary_color?: string;
    accent_color?: string;
    sidebar_style?: string;
    border_radius?: string;
    theme_preset?: string;
};
