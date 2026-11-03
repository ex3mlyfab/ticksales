import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { SheetClose } from '@/components/ui/sheet';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { AppNavSection } from '@/components/app-navigation';

export function NavMain({ sections }: { sections: AppNavSection[] }) {
    const { isCurrentUrl } = useCurrentUrl();
    const { isMobile } = useSidebar();

    return (
        <>
            {sections.map((section) => (
                <SidebarGroup className="px-2 py-0" key={section.title}>
                    <SidebarGroupLabel>{section.title}</SidebarGroupLabel>
                    <SidebarMenu>
                        {section.items.map((item) => (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isCurrentUrl(item.href)}
                                    tooltip={{ children: item.title }}
                                >
                                    {isMobile ? (
                                        <SheetClose asChild>
                                            <Link href={item.href} prefetch>
                                                {item.icon && <item.icon />}
                                                <span>{item.title}</span>
                                            </Link>
                                        </SheetClose>
                                    ) : (
                                        <Link href={item.href} prefetch>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </Link>
                                    )}
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
