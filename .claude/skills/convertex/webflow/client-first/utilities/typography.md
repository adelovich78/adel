# Typography Utilities

HTML tags define default styles (`<h1>`–`<h6>` styled via tag styles). Use utility classes only to **override** the default.

## Heading Style Switch — `heading-style-h[n]`

Restyle a heading to LOOK like another level while preserving the semantic tag for SEO:

```html
<h3 class="heading-style-h2">Visually H2, semantically H3</h3>
<h1 class="heading-style-h4">Small heading, top-level SEO weight</h1>
```

If the tag matches the desired visual style, use no class: `<h2>Title</h2>`.

## Text Size — `text-size-[size]`

| Class | Value |
|---|---|
| `text-size-large` | 1.5rem (24px) |
| `text-size-medium` | 1.125rem (18px) |
| `text-size-regular` | 1rem (16px) |
| `text-size-small` | 0.875rem (14px) |
| `text-size-tiny` | 0.75rem (12px) |

```html
<p class="text-size-large">Larger paragraph for introductions</p>
<p class="text-size-small">Fine print or captions</p>
```

## Text Weight — `text-weight-[name]`

| Class | Value |
|---|---|
| `text-weight-xbold` | 800 |
| `text-weight-bold` | 700 |
| `text-weight-semibold` | 600 |
| `text-weight-normal` | 400 |
| `text-weight-light` | 300 |

```html
<p class="text-weight-semibold">Semi-bold text</p>
```

## Text Color — `text-color-[name]`

| Class | Typical use |
|---|---|
| `text-color-primary` | Main text color (dark) |
| `text-color-secondary` | Muted/secondary text |
| `text-color-alternate` | Inverted text (for dark backgrounds) |

Adapt actual color values to the project's design system.

```html
<p class="text-color-secondary">Muted helper text</p>
```

## Text Alignment — `text-align-[dir]`

`text-align-left`, `text-align-center`, `text-align-right`

```html
<div class="text-align-center">
  <h2>Centered heading</h2>
  <p>Centered paragraph</p>
</div>
```

## Text Style — `text-style-[name]`

| Class | CSS | Use |
|---|---|---|
| `text-style-allcaps` | `text-transform: uppercase; letter-spacing: 0.05em` | Labels, badges |
| `text-style-italic` | `font-style: italic` | Emphasis |
| `text-style-link` | `text-decoration: underline` | Inline links |
| `text-style-muted` | `opacity: 0.6` | De-emphasized text |
| `text-style-nowrap` | `white-space: nowrap` | Prevent line breaks |
| `text-style-strikethrough` | `text-decoration: line-through` | Deleted/old prices |
| `text-style-2lines` | Line clamp to 2 lines | Card descriptions |
| `text-style-3lines` | Line clamp to 3 lines | Preview text |

```html
<span class="text-style-allcaps text-size-small">Featured</span>
<p class="text-style-2lines">This text will be truncated to 2 lines with an ellipsis if it overflows...</p>
```
