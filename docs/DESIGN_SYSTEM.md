# Design System

## Foundation

TickSales uses **Tailwind CSS v4** with **shadcn/ui (New York style)** built on **Radix UI primitives**. Colors are defined as **OKLCH CSS custom properties**, making the branding system fully dynamic without Tailwind recompilation.

---

## Color Token System

### CSS Custom Properties (`:root`)

The existing `app.css` defines a complete semantic token system:

```css
/* Semantic tokens (mapped from Tailwind @theme) */
--background       /* Page background */
--foreground       /* Primary text */
--card             /* Card surface */
--card-foreground  /* Card text */
--popover          /* Popover surface */
--popover-foreground
--primary          /* Brand primary (buttons, links, active states) */
--primary-foreground
--secondary        /* Secondary actions */
--secondary-foreground
--muted            /* Subtle backgrounds */
--muted-foreground /* Subdued text */
--accent           /* Highlight surface */
--accent-foreground
--destructive      /* Danger/error */
--destructive-foreground
--border           /* Default borders */
--input            /* Input borders */
--ring             /* Focus rings */
--radius           /* Border radius base */

/* Chart colors */
--chart-1 through --chart-5

/* Sidebar-specific tokens */
--sidebar
--sidebar-foreground
--sidebar-primary
--sidebar-primary-foreground
--sidebar-accent
--sidebar-accent-foreground
--sidebar-border
--sidebar-ring
```

### POS-Specific Status Tokens

Additional semantic tokens for POS status indicators:

```css
/* Status colors — defined in addition to shadcn tokens */
--color-success:    oklch(0.6 0.17 145);   /* Green — completed, in stock */
--color-warning:    oklch(0.75 0.16 60);   /* Amber — low stock, pending */
--color-info:       oklch(0.6 0.18 240);   /* Blue — informational */
--color-offline:    oklch(0.55 0 0);       /* Gray — offline indicator */
```

---

## Preset Themes

Each theme replaces the CSS custom property values. No Tailwind recompile needed.

| Theme | Primary | Character |
|-------|---------|-----------|
| `neutral` | Neutral gray-black | Default / clean |
| `indigo` | Indigo 500 | Professional / corporate |
| `violet` | Violet 500 | Creative / modern |
| `emerald` | Emerald 500 | Natural / growth |
| `ocean` | Sky 600 | Fresh / tech |
| `amber` | Amber 500 | Warm / retail |
| `rose` | Rose 500 | Vibrant / food |
| `slate` | Slate 600 | Minimal / understated |

Theme switching is applied via a `data-theme` attribute on `<html>` and CSS `:root[data-theme="indigo"]` overrides.

---

## Tenant Branding

Tenant admins can customize:

```css
/* Injected per-tenant via <style> tag in app.blade.php */
:root {
    --primary:           oklch(/* tenant primary */);
    --primary-foreground: oklch(/* computed for contrast */);
    --sidebar-primary:   oklch(/* tenant primary */);
    /* ... */
}
```

The branding is stored in `tenant_branding` and injected by the Inertia middleware into the shared `branding` prop.

**Accessibility rule:** Minimum contrast ratio 4.5:1 (WCAG AA) is enforced when saving tenant colors. A server-side contrast validator rejects invalid color combinations.

---

## Typography

| Token | Value | Usage |
|-------|-------|-------|
| `--font-sans` | Instrument Sans → system-ui → sans-serif | Body text, UI |
| Font sizes | Tailwind default scale | `text-sm`, `text-base`, `text-lg`, `text-xl`, `text-2xl`, `text-3xl` |
| Font weights | 400, 500, 600 | Regular, Medium, Semibold |
| Line height | Tailwind default | `leading-tight`, `leading-normal` |

---

## Spacing Scale

Standard Tailwind spacing (base-4 system). Key values:

| Token | px | Usage |
|-------|----|-------|
| `p-2` | 8px | Compact padding (badges, table cells) |
| `p-3` | 12px | Button padding (sm) |
| `p-4` | 16px | Standard padding |
| `p-6` | 24px | Card padding |
| `p-8` | 32px | Section padding |
| `gap-4` | 16px | Standard grid/flex gap |
| `gap-6` | 24px | Between cards |

---

## Border Radius

| Token | Value | Usage |
|-------|-------|-------|
| `--radius` | 0.625rem (10px) | Base radius (cards, modals) |
| `rounded-sm` | calc(radius - 4px) | Badges, small tags |
| `rounded-md` | calc(radius - 2px) | Inputs, buttons |
| `rounded-lg` | radius | Cards, dropdowns |
| `rounded-xl` | radius + 4px | Dashboard widgets |
| `rounded-full` | 9999px | Avatars, pill badges |

---

## Shadow Scale

| Class | Usage |
|-------|-------|
| `shadow-sm` | Cards, dropdowns (subtle lift) |
| `shadow-md` | Modals, popovers |
| `shadow-lg` | Floating panels |

Avoid heavy shadows. Prefer border + subtle shadow for depth.

---

## Component Inventory

### Already Available (shadcn/ui)
- `Avatar` — User avatar with initials fallback
- `Badge` — Status / label chips
- `Button` — Primary, secondary, outline, ghost, destructive, link variants
- `Card` — With header, content, footer
- `Checkbox`
- `Dialog` — Modal dialogs
- `Dropdown Menu`
- `Input`
- `Label`
- `Navigation Menu`
- `Select`
- `Separator`
- `Sheet` — Slide-in drawer
- `Sidebar` — Collapsible sidebar (full implementation)
- `Skeleton` — Loading placeholder
- `Sonner` — Toast notifications
- `Spinner` — Loading indicator
- `Toggle` / `Toggle Group`
- `Tooltip`
- `Placeholder Pattern` — Empty state background

### To Be Added (as needed)
- `Table` — Data table with sorting/pagination
- `DataTable` — Advanced table with filters
- `DatePicker` — Calendar date selection
- `NumberInput` — Formatted numeric input
- `Combobox` — Searchable select
- `AlertDialog` — Confirmation dialogs
- `Alert` — Inline alerts (info/success/warning/error)
- `Progress` — Progress bar
- `Tabs`
- `Accordion`
- `Switch`
- `Slider`
- `Textarea`
- `FormField` — Wraps Label + Input + Error message
- `PageHeader` — Page title + actions
- `EmptyState` — Empty list states with icon + message + CTA
- `StatusBadge` — Color-coded status (sale/inventory/session status)
- `CurrencyDisplay` — Formatted currency value
- `KpiCard` — Dashboard metric card
- `Chart` — Sales/inventory charts (to be evaluated: Recharts or similar)

---

## Page Layout Patterns

### App Shell (existing)
```
┌─────────────────────────────────────────┐
│ Sidebar (collapsible, icon mode)        │
│ ├── Logo / Brand                        │
│ ├── Nav Items                           │
│ └── User menu                           │
│                                         │
│ Content Area                            │
│ ├── Breadcrumb header                   │
│ └── Page content                        │
└─────────────────────────────────────────┘
```

### Index Page Pattern
```
PageHeader (title + Create button)
Filters row (search, selects)
DataTable (with pagination)
```

### Form Page Pattern
```
PageHeader (title + Back button)
Card
  └── Form fields
      └── Submit / Cancel buttons
```

### POS Sale Screen Pattern
```
┌──────────────────┬──────────────────────┐
│ Product Grid     │ Sale Summary         │
│ (touch-friendly) │ ├── Items list       │
│                  │ ├── Totals           │
│ Search bar       │ ├── Customer         │
│                  │ └── Payment / Submit │
└──────────────────┴──────────────────────┘
```

---

## UI States — Every Major Page Must Handle

| State | Implementation |
|-------|---------------|
| Loading | Skeleton components (Inertia deferred props) |
| Empty | `EmptyState` component (icon + message + CTA) |
| Error | Error boundary + `Alert` (destructive) |
| Unauthorized | 403 page |
| Offline | `OfflineIndicator` banner |
| Syncing | `SyncStatus` indicator |
| Success | `Sonner` toast + route navigation |

---

## Form Conventions

- Use `<Form>` component from `@inertiajs/react` (renders as HTML form)
- Display validation errors inline below each field via `errors.fieldName`
- Show `processing` loading state on submit button
- Use `Sonner` toast for success confirmation
- Unsaved changes warning for complex forms (e.g., purchase orders)

---

## Responsive Breakpoints

Standard Tailwind breakpoints:

| Breakpoint | Width | Usage |
|-----------|-------|-------|
| (default) | 0px+ | Mobile portrait |
| `sm` | 640px | Mobile landscape |
| `md` | 768px | Tablet portrait |
| `lg` | 1024px | Tablet landscape / small laptop |
| `xl` | 1280px | Desktop |
| `2xl` | 1536px | Large desktop |

### Mobile-First POS Rules
- Sidebar: auto-closes to icon mode on `md` and below
- POS sale screen: stacked layout on `md`, side-by-side on `lg`+
- Button minimum touch target: 44x44px
- Table overflow: horizontal scroll on small screens

---

## Accessibility Targets

- WCAG 2.2 AA contrast minimum (4.5:1 normal text, 3:1 large)
- All interactive elements keyboard-navigable
- Focus ring visible on all focusable elements
- ARIA labels on icon-only buttons
- Screen reader text with `sr-only` class where needed
- Reduced motion: respect `prefers-reduced-motion`
- Color never the sole indicator (use icon or text alongside color)

---

## Dark Mode

Dark mode is toggled via the `dark` class on `<html>`. The `use-appearance.tsx` hook manages this and persists to `localStorage`.

Three modes: `light`, `dark`, `system` (matches OS preference).

All color tokens have dark variants defined in `.dark { }` in `app.css`.

---

## Notification / Toast Conventions

Use `Sonner` toasts for feedback:

```tsx
import { toast } from 'sonner';

// Success
toast.success('Sale created successfully.');

// Error
toast.error('Failed to sync. Please try again.');

// Info
toast.info('New update available. Refresh to apply.');

// Warning
toast.warning('Stock level is critically low.');
```

Toast duration: 4 seconds default, 8 seconds for errors.
