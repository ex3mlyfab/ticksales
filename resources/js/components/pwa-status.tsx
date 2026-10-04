import { useEffect, useState } from 'react';

export function PwaStatus() {
    const [isOnline, setIsOnline] = useState<boolean>(
        typeof navigator === 'undefined' ? true : navigator.onLine,
    );
    const [canInstall, setCanInstall] = useState(false);
    const [promptEvent, setPromptEvent] = useState<BeforeInstallPromptEvent | null>(null);

    useEffect(() => {
        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        const handleBeforeInstallPrompt = (event: Event) => {
            event.preventDefault();
            setPromptEvent(event as BeforeInstallPromptEvent);
            setCanInstall(true);
        };

        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
            window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
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
                <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-500/50 dark:bg-amber-950/40 dark:text-amber-100">
                    You are offline. Sales and data entry continue locally and sync once connectivity returns.
                </div>
            )}

            {canInstall && (
                <div className="flex items-center justify-between rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:border-emerald-500/50 dark:bg-emerald-950/40 dark:text-emerald-100">
                    <span>Install TickSales for a faster POS experience.</span>
                    <button
                        type="button"
                        onClick={handleInstall}
                        className="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-500"
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
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed'; platform: string }>;
};
