import { useSyncExternalStore } from 'react';

const MOBILE_BREAKPOINT = 768;

function mediaQueryListener(callback: () => void) {
    if (typeof window === 'undefined') {
        return () => {};
    }

    const mediaQuery = window.matchMedia(
        `(max-width: ${MOBILE_BREAKPOINT - 1}px)`,
    );
    const listener = () => callback();

    mediaQuery.addEventListener('change', listener);

    return () => {
        mediaQuery.removeEventListener('change', listener);
    };
}

function isSmallerThanBreakpoint(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`).matches;
}

function getServerSnapshot(): boolean {
    return false;
}

export function useIsMobile(): boolean {
    return useSyncExternalStore(
        mediaQueryListener,
        isSmallerThanBreakpoint,
        getServerSnapshot,
    );
}
