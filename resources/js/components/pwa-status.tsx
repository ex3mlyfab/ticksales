import { useEffect, useState } from 'react';

export function PwaStatus() {
    // Start "online" so the SSR HTML and the first client render agree;
    // navigator.onLine is browser-only and would otherwise hydrate
    // differently from the server (React hydration mismatch).
    const [isOnline, setIsOnline] = useState<boolean>(true);
    const [canInstall, setCanInstall] = useState(false);
    const [promptEvent, setPromptEvent] =
        useState<BeforeInstallPromptEvent | null>(null);

    useEffect(() => {
        setIsOnline(navigator.onLine);

        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        const handleBeforeInstallPrompt = (event: Event) => {
            event.preventDefault();
            setPromptEvent(event as BeforeInstallPromptEvent);
            setCanInstall(true);
        };

        window.addEventListener(
            'beforeinstallprompt',
            handleBeforeInstallPrompt,
        );

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
            window.removeEventListener(
                'beforeinstallprompt',
                handleBeforeInstallPrompt,
            );
        };
    }, []);

    const handleInstall = async () => {
        if (!promptEvent) {
            return;
        }

        await promptEvent.prompt();
        await promptEvent.userChoice;
        setCanInstall(false);
        setPromptEvent(null);
    };

    return (
        <div className="space-y-3">
            {!isOnline && (
                <div className="rounded-md border border-warning/30 bg-warning/10 px-3 py-2 text-sm text-forest dark:border-warning/40 dark:bg-warning/10 dark:text-warning">
                    You are offline. Sales and data entry continue locally and
                    sync once connectivity returns.
                </div>
            )}

            {canInstall && (
                <div className="flex items-center justify-between rounded-md border border-success/25 bg-success/10 px-3 py-2 text-sm text-forest dark:border-success/25 dark:bg-success/15 dark:text-success">
                    <span>Install Sokora for a faster POS experience.</span>
                    <button
                        type="button"
                        onClick={handleInstall}
                        className="rounded-md bg-forest px-2.5 py-1 text-xs font-medium text-lime hover:bg-forest/90 dark:bg-lime dark:text-forest"
                    >
                        Install app
                    </button>
                </div>
            )}
        </div>
    );
}

type BeforeInstallPromptEvent = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{
        outcome: 'accepted' | 'dismissed';
        platform: string;
    }>;
};
