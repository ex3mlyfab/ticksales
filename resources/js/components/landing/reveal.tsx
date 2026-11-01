import { useEffect, useRef, useState, type ReactNode } from 'react';

import { cn } from '@/lib/utils';

type RevealProps = {
    children: ReactNode;
    className?: string;
    delay?: number;
};

/**
 * Fades content in the first time it scrolls into view.
 *
 * Uses a single IntersectionObserver, so it stays cheap on long pages.
 */
export default function Reveal({
    children,
    className,
    delay = 0,
}: RevealProps) {
    const elementRef = useRef<HTMLDivElement>(null);
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        const element = elementRef.current;

        if (!element) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        setIsVisible(true);
                        observer.disconnect();
                    }
                }
            },
            { threshold: 0.15, rootMargin: '0px 0px -10% 0px' },
        );

        observer.observe(element);

        return () => observer.disconnect();
    }, []);

    return (
        <div
            ref={elementRef}
            style={delay ? { transitionDelay: `${delay}ms` } : undefined}
            className={cn(
                'transition-[opacity,transform] duration-700 ease-out motion-reduce:transition-none',
                isVisible
                    ? 'translate-y-0 opacity-100'
                    : 'translate-y-4 opacity-0 motion-reduce:translate-y-0 motion-reduce:opacity-100',
                className,
            )}
        >
            {children}
        </div>
    );
}
