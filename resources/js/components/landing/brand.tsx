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
    return (
        <span className={cn('flex items-center gap-2', className)}>
            <span
                className={cn(
                    'flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm',
                    markClassName,
                )}
            >
                <ReceiptText className="size-5" strokeWidth={2} />
            </span>
            {showName && (
                <span className="text-base font-semibold tracking-tight">
                    TickSales
                </span>
            )}
        </span>
    );
}
