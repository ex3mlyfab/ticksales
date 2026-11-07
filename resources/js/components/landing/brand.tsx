import { usePage } from '@inertiajs/react';
import { ReceiptText } from 'lucide-react';

import { cn } from '@/lib/utils';

type BrandProps = {
    className?: string;
    markClassName?: string;
    showName?: boolean;
};

/**
 * The TickSales brand mark: a receipt glyph in a rounded tile.
 */
export default function Brand({
    className,
    markClassName,
    showName = true,
}: BrandProps) {
    const { name, branding } = usePage().props;

    return (
        <span className={cn('flex items-center gap-2', className)}>
            <span
                className={cn(
                    'flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm',
                    markClassName,
                )}
            >
                {branding?.logo_path ? (
                    <img src={branding.logo_path} alt="" className="size-full rounded-xl object-cover" />
                ) : (
                    <ReceiptText className="size-5" strokeWidth={2} />
                )}
            </span>
            {showName && (
                <span className="text-base font-semibold tracking-tight">
                    {branding?.name ?? name ?? 'Sokora'}
                </span>
            )}
        </span>
    );
}
