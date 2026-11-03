import { Breadcrumbs } from '@/components/breadcrumbs';
import { TenantSwitcher } from '@/components/tenant-switcher';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="flex h-16 shrink-0 items-center justify-center border-b border-sidebar-border/50 px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 sm:px-6 lg:px-8">
            <div className="mx-auto flex w-full max-w-screen-2xl items-center gap-2">
                <div className="flex min-w-0 items-center gap-2">
                    <SidebarTrigger className="-ml-1" />
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
                <div className="ml-auto w-56 max-w-[45vw] sm:max-w-[13rem]">
                    <TenantSwitcher />
                </div>
            </div>
        </header>
    );
}
