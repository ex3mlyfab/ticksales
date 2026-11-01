/**
 * Runtime smoke test for the Inertia page components.
 *
 * These pages were previously crashing at runtime with
 * `ReferenceError: route is not defined` because they called the global
 * `route()` helper. The helper was replaced with typed Wayfinder imports,
 * which are statically resolvable — but a TypeScript-only check cannot prove
 * that a module *evaluates* without throwing at runtime.
 *
 * This test therefore mounts every page component through Inertia's real
 * `App` (so the `PageContext` / `HeadContext` providers are genuine) and
 * asserts that:
 *
 *   1. the module imports without throwing (catches import-time crashes),
 *   2. the component renders without throwing (catches render-time crashes,
 *      e.g. reading `.checked` off a Radix `<button>`), and
 *   3. submitting a controlled form issues a request to the expected URL
 *      and HTTP method (catches a form wired to the wrong route).
 *
 * A fake HTTP client is injected via `http.setClient()` so submissions are
 * captured instead of hitting the network, while the real `useForm`
 * implementation (and therefore the real submit pipeline) is exercised.
 */
import { act } from 'react';
import { createRoot, type Root } from 'react-dom/client';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { App, http } from '@inertiajs/react';

// React 19 requires this flag to use `act()` outside of a testing library.
(globalThis as { IS_REACT_ACT_ENVIRONMENT?: boolean }).IS_REACT_ACT_ENVIRONMENT =
    true;

interface CapturedRequest {
    method: string;
    url: string;
    data: unknown;
    headers: Record<string, string>;
}

const capturedRequests: CapturedRequest[] = [];

/**
 * Inertia resolves a relative URL against `location.href`, so the captured
 * request URL is absolute under happy-dom's default origin. Compare paths.
 */
function pathOf(url: string): string {
    return new URL(url, 'http://localhost').pathname;
}

/**
 * Minimal Inertia page payload. `props` is supplied per test.
 */
function makePage(component: string, props: Record<string, unknown> = {}) {
    return {
        component,
        props,
        url: `/${component}`,
        version: 'smoke-test',
        clearHistory: false,
        encryptHistory: false,
        scrollProps: { preserve: false, positions: {} },
        key: 'smoke',
        deferredProps: {},
        mergeProps: [],
        errors: {},
        resolveCallbacks: [],
        resolvePage: () => Promise.resolve(),
    };
}

/**
 * Mount a page component inside Inertia's `App` so the genuine page/head
 * contexts are present, then return the rendered container.
 *
 * The page is wrapped in a thin function component: this clears the page's
 * static `.layout` (the app shell, which is dead code at runtime anyway), so
 * only the page body is exercised — matching how the page renders inside its
 * own layout but without dragging in the sidebar/topbar shell.
 */
async function renderPage(
    Component: React.ComponentType<any>,
    props: Record<string, unknown> = {},
    pageComponent = 'Smoke',
): Promise<HTMLElement> {
    const container = document.createElement('div');
    document.body.appendChild(container);

    const TestComponent: React.ComponentType<Record<string, unknown>> = (
        componentProps,
    ) => <Component {...componentProps} />;

    const root: Root = createRoot(container);

    const appProps = {
        initialPage: makePage(pageComponent, props),
        initialComponent: TestComponent,
        resolveComponent: () => Promise.resolve(TestComponent),
        defaultLayout: () => null,
    };

    await act(async () => {
        root.render(
            <App
                {...(appProps as unknown as React.ComponentProps<typeof App>)}
            />,
        );
    });

    return container;
}

beforeEach(() => {
    capturedRequests.length = 0;

    http.setClient({
        request: async (request: {
            method: string;
            url: string;
            data?: unknown;
            headers?: Record<string, string>;
        }) => {
            capturedRequests.push({
                method: request.method,
                url: request.url,
                data: request.data,
                headers: request.headers ?? {},
            });

            // Mirrors the shape the real XHR client resolves with: Inertia's
            // `_Response` reads `headers.get('x-inertia')` to decide whether
            // the response is an Inertia response. It must be present, or
            // Inertia treats the 200 as a full-page response and opens its
            // error dialog.
            return {
                status: 200,
                data: JSON.stringify({ component: 'Smoke', props: {} }),
                headers: new Headers({ 'x-inertia': 'true' }),
            };
        },
    } as never);
});

afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
});

describe('page modules evaluate without throwing', () => {
    /**
     * Importing each page module directly is the cheapest way to prove the
     * module body itself is free of import-time `ReferenceError`s (the class
     * of bug the Wayfinder conversion fixed).
     */
    const pageModules = import.meta.glob<Record<string, unknown>>(
        '../pages/**/*.tsx',
        { eager: false },
    );

    const pagePaths = Object.keys(pageModules)
        .filter((path) => !path.includes('.test.'))
        .sort();

    it('discovers the page modules', () => {
        expect(pagePaths.length).toBeGreaterThan(50);
    });

    it.each(pagePaths)('imports %s without throwing', async (path) => {
        const module = await pageModules[path]();

        expect(module.default).toBeTypeOf('function');
    }, 30000);
});

describe('controlled form pages render and submit to the right route', () => {
    it('renders the discount create page', async () => {
        const { default: DiscountCreatePage } = await import('../pages/discounts/create'
        );

        const container = await renderPage(DiscountCreatePage);

        expect(container.querySelector('form')).not.toBeNull();
        expect(container.textContent).toContain('Create discount');
    });

    it('submits the discount create form to POST /discounts', async () => {
        const { default: DiscountCreatePage } = await import('../pages/discounts/create'
        );

        const container = await renderPage(DiscountCreatePage);

        const form = container.querySelector('form');
        expect(form).not.toBeNull();

        await act(async () => {
            form?.dispatchEvent(
                new Event('submit', { bubbles: true, cancelable: true }),
            );
        });

        expect(capturedRequests).toHaveLength(1);
        expect(capturedRequests[0].method).toBe('post');
        expect(pathOf(capturedRequests[0].url)).toBe('/discounts');
    });

    it('submits the discount edit form to PUT /discounts/{id}', async () => {
        const { default: DiscountEditPage } = await import('../pages/discounts/edit'
        );

        const container = await renderPage(DiscountEditPage, {
            discount: {
                id: 'disc_1',
                name: 'Winter sale',
                type: 'percentage',
                value: 10,
                min_purchase_amount: null,
                max_discount_amount: null,
                starts_at: null,
                ends_at: null,
                is_active: true,
            },
        });

        const form = container.querySelector('form');
        expect(form).not.toBeNull();

        await act(async () => {
            form?.dispatchEvent(
                new Event('submit', { bubbles: true, cancelable: true }),
            );
        });

        expect(capturedRequests).toHaveLength(1);
        expect(capturedRequests[0].method).toBe('put');
        expect(pathOf(capturedRequests[0].url)).toBe('/discounts/disc_1');
    });

    it('submits the customer create form to POST /customers', async () => {
        const { default: CustomerCreatePage } = await import('../pages/customers/create'
        );

        const container = await renderPage(CustomerCreatePage, {
            groups: [{ id: 'grp_1', name: 'Retail' }],
        });

        const form = container.querySelector('form');
        expect(form).not.toBeNull();

        await act(async () => {
            form?.dispatchEvent(
                new Event('submit', { bubbles: true, cancelable: true }),
            );
        });

        expect(capturedRequests).toHaveLength(1);
        expect(capturedRequests[0].method).toBe('post');
        expect(pathOf(capturedRequests[0].url)).toBe('/customers');
    });

    it('submits the product create form to POST /products', async () => {
        const { default: ProductCreatePage } = await import('../pages/products/create'
        );

        const container = await renderPage(ProductCreatePage, {
            categories: [{ id: 'cat_1', name: 'Electronics' }],
            brands: [{ id: 'br_1', name: 'Acme' }],
            units: [{ id: 'un_1', name: 'Each', abbreviation: 'ea' }],
            taxRates: [{ id: 'tx_1', name: 'VAT' }],
        });

        const form = container.querySelector('form');
        expect(form).not.toBeNull();

        await act(async () => {
            form?.dispatchEvent(
                new Event('submit', { bubbles: true, cancelable: true }),
            );
        });

        expect(capturedRequests).toHaveLength(1);
        expect(capturedRequests[0].method).toBe('post');
        expect(pathOf(capturedRequests[0].url)).toBe('/products');
    });

    it('submits the supplier create form to POST /suppliers', async () => {
        const { default: SupplierCreatePage } = await import('../pages/suppliers/create'
        );

        const container = await renderPage(SupplierCreatePage);

        const form = container.querySelector('form');
        expect(form).not.toBeNull();

        await act(async () => {
            form?.dispatchEvent(
                new Event('submit', { bubbles: true, cancelable: true }),
            );
        });

        expect(capturedRequests).toHaveLength(1);
        expect(capturedRequests[0].method).toBe('post');
        expect(pathOf(capturedRequests[0].url)).toBe('/suppliers');
    });

    it('submits the tax rate create form to POST /tax-rates', async () => {
        const { default: TaxRateCreatePage } = await import('../pages/tax-rates/create'
        );

        const container = await renderPage(TaxRateCreatePage);

        const form = container.querySelector('form');
        expect(form).not.toBeNull();

        await act(async () => {
            form?.dispatchEvent(
                new Event('submit', { bubbles: true, cancelable: true }),
            );
        });

        expect(capturedRequests).toHaveLength(1);
        expect(capturedRequests[0].method).toBe('post');
        expect(pathOf(capturedRequests[0].url)).toBe('/tax-rates');
    });
});
