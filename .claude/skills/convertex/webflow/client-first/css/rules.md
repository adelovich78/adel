# CSS Rules for Client-First

## Units: rem Only

All sizes in rem (`1rem = 16px`). Use these standard values:

| rem | px | | rem | px |
|---|---|---|---|---|
| `0.25` | 4 | | `2.5` | 40 |
| `0.5` | 8 | | `3` | 48 |
| `0.75` | 12 | | `4` | 64 |
| `1` | 16 | | `5` | 80 |
| `1.25` | 20 | | `6` | 96 |
| `1.5` | 24 | | `8` | 128 |
| `2` | 32 | | `12` | 192 |

**Exception:** `border-width` values can use `px` (e.g., `border-width: 1px`).

## Core Structure CSS

Only include classes that appear in the HTML:

```css
/* Horizontal padding */
.padding-global { padding-left: 2.5rem; padding-right: 2.5rem; }

/* Containers */
.container-large { max-width: 80rem; margin-left: auto; margin-right: auto; width: 100%; }
.container-medium { max-width: 64rem; margin-left: auto; margin-right: auto; width: 100%; }
.container-small { max-width: 48rem; margin-left: auto; margin-right: auto; width: 100%; }

/* Section padding */
.padding-section-large { padding-top: 8rem; padding-bottom: 8rem; }
.padding-section-medium { padding-top: 5rem; padding-bottom: 5rem; }
.padding-section-small { padding-top: 3rem; padding-bottom: 3rem; }
```

## Mandatory Responsive Overrides

These must always be included when using the corresponding structure classes:

```css
@media (max-width: 991px) {
  .padding-global { padding-left: 1.5rem; padding-right: 1.5rem; }
  .padding-section-large { padding-top: 6rem; padding-bottom: 6rem; }
  .padding-section-medium { padding-top: 4rem; padding-bottom: 4rem; }
  .padding-section-small { padding-top: 2rem; padding-bottom: 2rem; }
}

@media (max-width: 479px) {
  .padding-global { padding-left: 1.25rem; padding-right: 1.25rem; }
  .padding-section-large { padding-top: 4rem; padding-bottom: 4rem; }
  .padding-section-medium { padding-top: 3rem; padding-bottom: 3rem; }
  .padding-section-small { padding-top: 1.5rem; padding-bottom: 1.5rem; }
}
```

## CSS Organization Order

Structure your CSS in this order for consistency:

1. **Core structure** — `padding-global`, containers, `padding-section-*`
2. **Typography utilities** — `text-size-*`, `text-weight-*`, `heading-style-*`
3. **Button utilities** — `button`, `.button.is-*` variants
4. **Spacing utilities** — `margin-*`, `padding-*`, `spacer-*`
5. **Layout utilities** — `max-width-*`, `hide-*`, `overflow-*`
6. **Custom classes** — grouped by section (`hero_*`, `features_*`, `footer_*`)
7. **Responsive overrides** — tablet (991px) → mobile landscape (767px) → mobile portrait (479px)

## Typography Base Styles

Define tag-level styles for consistent typography across the site:

```css
/* Heading tag styles */
h1 { font-size: 3.5rem; font-weight: 700; line-height: 1.1; letter-spacing: -0.02em; }
h2 { font-size: 2.5rem; font-weight: 700; line-height: 1.2; letter-spacing: -0.02em; }
h3 { font-size: 2rem; font-weight: 600; line-height: 1.3; }
h4 { font-size: 1.5rem; font-weight: 600; line-height: 1.4; }
h5 { font-size: 1.25rem; font-weight: 600; line-height: 1.4; }
h6 { font-size: 1rem; font-weight: 600; line-height: 1.5; }

/* Body text */
p { font-size: 1rem; line-height: 1.6; color: #374151; }
a { color: #2563eb; text-decoration: none; }
a:hover { text-decoration: underline; }

/* Responsive typography */
@media (max-width: 991px) {
  h1 { font-size: 2.75rem; }
  h2 { font-size: 2rem; }
  h3 { font-size: 1.75rem; }
}

@media (max-width: 479px) {
  h1 { font-size: 2.25rem; }
  h2 { font-size: 1.75rem; }
  h3 { font-size: 1.5rem; }
}
```

**Note:** These are starting values. Adapt sizes, weights, and colors to the project's design system.
