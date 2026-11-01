import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown } from 'lucide-react';
import dashboardRoutes from '@/routes/dashboard';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Tenant } from '@/types';

export function TenantSwitcher() {
    const { tenant, tenants } = usePage().props as {
        tenant?: Tenant | null;
        tenants?: Tenant[];
    };

    const current = tenant ?? tenants?.[0] ?? null;
    const list = tenants ?? [];

    const switchTenant = (next: Tenant) => {
        if (next.id === current?.id) {
            return;
        }
        // Landing on the tenant-scoped dashboard makes it the active tenant
        // for the rest of the session.
        router.visit(dashboardRoutes.tenant({ tenant: next.id }));
    };

    if (!current) {
        return null;
    }

    // Single-tenant users just see their tenant as a static label.
    if (list.length < 2) {
        return (
            <div className="flex h-9 w-full items-center gap-2 px-3 text-sm">
                <Building2 className="size-4 shrink-0 text-muted-foreground" />
                <span className="truncate font-medium">{current.name}</span>
            </div>
        );
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    className="flex h-9 w-full items-center justify-between gap-2 px-3"
                >
                    <span className="flex min-w-0 items-center gap-2">
                        <Building2 className="size-4 shrink-0 text-muted-foreground" />
                        <span className="truncate font-medium">
                            {current.name}
                        </span>
                    </span>
                    <ChevronsUpDown className="size-4 shrink-0 text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent className="w-64" align="start" side="bottom">
                <DropdownMenuLabel className="text-xs text-muted-foreground">
                    Switch tenant
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {list.map((item) => (
                    <DropdownMenuItem
                        key={item.id}
                        className="cursor-pointer"
                        onClick={() => switchTenant(item)}
                    >
                        <Building2 className="size-4 shrink-0" />
                        <span className="min-w-0 flex-1 truncate">
                            {item.name}
                        </span>
                        {item.is_current && (
                            <Check className="size-4 shrink-0 text-primary" />
                        )}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
