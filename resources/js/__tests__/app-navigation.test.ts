import { describe, expect, it } from 'vitest';
import { getAppNavigation } from '@/components/app-navigation';

describe('app navigation permissions', () => {
    it('shows only sections granted by the shared permissions', () => {
        const sections = getAppNavigation(['products.view', 'expenses.view']);

        expect(
            sections.map((section) => [
                section.title,
                ...section.items.map((item) => item.title),
            ]),
        ).toEqual([
            ['Dashboard', 'Dashboard'],
            ['Catalog', 'Products'],
            ['Expenses', 'Expenses'],
        ]);
    });

    it('keeps the dashboard available when no permissions are granted', () => {
        const sections = getAppNavigation([]);

        expect(sections).toHaveLength(1);
        expect(sections[0].items[0].title).toBe('Dashboard');
    });
});
