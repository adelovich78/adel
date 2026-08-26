# Spacing Utilities

## Two-Class System

Direction + size on the **SAME element**:

```html
<div class="margin-bottom margin-medium">   <!-- margin-bottom: 2rem -->
<div class="padding-vertical padding-large"> <!-- padding-top + bottom: 3rem -->
```

## Margin

### Directions

`margin-top`, `margin-bottom`, `margin-left`, `margin-right`, `margin-horizontal`, `margin-vertical`

### Sizes

| Class | Value | | Class | Value |
|---|---|---|---|---|
| `margin-0` | 0 | | `margin-large` | 3rem |
| `margin-tiny` | 0.125rem | | `margin-xlarge` | 4rem |
| `margin-xxsmall` | 0.25rem | | `margin-xxlarge` | 5rem |
| `margin-xsmall` | 0.5rem | | `margin-huge` | 6rem |
| `margin-small` | 1rem | | `margin-xhuge` | 8rem |
| `margin-medium` | 2rem | | `margin-xxhuge` | 12rem |
| `margin-custom1` | 1.5rem | | `margin-custom2` | 2.5rem |

### Examples

```html
<h2 class="margin-bottom margin-small">Section Title</h2>
<p class="margin-bottom margin-medium">Paragraph with spacing below</p>
<div class="margin-horizontal margin-large">Horizontal margins</div>
```

## Padding

### Directions

`padding-top`, `padding-bottom`, `padding-left`, `padding-right`, `padding-horizontal`, `padding-vertical`

### Sizes

Same scale as margin: `padding-0` through `padding-xxhuge`, plus `padding-custom1`, `padding-custom2`, `padding-custom3`.

### Examples

```html
<div class="padding-vertical padding-large">Vertical padding</div>
<div class="padding-horizontal padding-medium">Horizontal padding</div>
```

## Spacer Blocks

Empty divs for vertical spacing between elements:

```html
<div class="spacer-medium"></div>
```

| Class | Value |
|---|---|
| `spacer-small` | 1rem |
| `spacer-medium` | 2rem |
| `spacer-large` | 3rem |

Use spacers when you need spacing between siblings without adding margin to either element.

## Reset

`spacing-clean` — sets all margin and padding to 0. Useful for overriding tag defaults.

```html
<h3 class="spacing-clean">Heading with no default margins</h3>
```

## Important

`padding-global` and `padding-section-*` are **structural classes** (part of the page skeleton), NOT spacing utilities. Don't use them for general spacing.
