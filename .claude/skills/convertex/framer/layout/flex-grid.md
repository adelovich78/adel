# Flex & Grid: gap-only spacing

Framer has no `margin`. **All inter-element spacing is `gap` on the parent + `padding` for inset.**

If you find yourself reaching for `margin: 0 auto` or `margin-bottom: 24px`, stop and refactor.

---

## Flex stacks (the workhorse)

Vertical column with consistent vertical rhythm:

```css
.section_inner {
  display: flex;
  flex-direction: column;
  align-items: center;            /* horizontal alignment of children */
  gap: 24px;                       /* vertical rhythm between siblings */
  padding: 96px 32px;              /* outer inset */
  width: 100%;
  max-width: 1200px;
}
```

Horizontal row:

```css
.button_group {
  display: flex;
  flex-direction: row;             /* default, usually omitted */
  align-items: center;
  gap: 16px;
}
```

### `align-items` values that work

| CSS | Framer `stackAlignment` |
|---|---|
| `flex-start` / `start` | `start` |
| `center` | `center` |
| `flex-end` / `end` | `end` |
| `stretch` | _(handled as default; children fill cross-axis)_ |
| `baseline` | `baseline` (rare in landing-page work) |

### `justify-content` values

| CSS | Framer `stackDistribution` |
|---|---|
| `flex-start` / `start` | `start` |
| `center` | `center` |
| `flex-end` / `end` | `end` |
| `space-between` | `space-between` |
| `space-around` | `space-around` |
| `space-evenly` | `space-evenly` |

### Wrapping rows

```css
.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 12px;                   /* row-gap column-gap */
}
```

When `flex-wrap: wrap`, the converter keeps children at `fill` so the wrap mechanic works. Don't worry about hugging children manually.

---

## `gap` shorthand and split

```css
.cards {
  display: flex;
  gap: 24px;                       /* same row+col */
  /* or: */
  gap: 12px 32px;                  /* row-gap=12, column-gap=32 */
  /* or: */
  row-gap: 12px;
  column-gap: 32px;
}
```

For flex containers, the converter picks the relevant axis based on `flex-direction`:
- `column` → uses `row-gap`
- `row` → uses `column-gap`

For grid, both axes are kept (`"12px 32px"` string in Framer JSON).

---

## Centering a single child

There's no `margin: 0 auto` in Framer. Center via the parent:

```css
.banner {
  display: flex;
  justify-content: center;         /* horizontal centering */
  align-items: center;             /* vertical centering */
  padding: 80px 32px;
}
.banner_card {
  width: 100%;
  max-width: 720px;                /* the cap is on the child */
}
```

---

## Grid layouts

```css
.feature_grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 32px;
  padding: 80px 32px;
}
```

The converter:
- Counts the columns (here 3) → `gridColumnCount: 3`
- Takes the gap → `gap: "32px 32px"`
- Sets sensible defaults for `gridColumnMinWidth`, `gridColumnWidth`, `gridRowHeight`, `gridRowHeightType`

### Per-item placement

```css
.feature_grid_card.is-wide { grid-column: span 2; }
.feature_grid_card.is-tall { grid-row: span 2; }
.feature_grid_card.is-promo {
  grid-column: 1 / 3;              /* explicit start/end */
  grid-row: 1 / 3;
}
```

| CSS | Framer prop |
|---|---|
| `grid-column: span N` | `gridItemColumnSpan: N` |
| `grid-row: span N` | `gridItemRowSpan: N` |
| `grid-column: A / B` | spans computed from start/end |
| `justify-self: start \| center \| end` | `gridItemHorizontalAlignment` |
| `align-self: start \| center \| end` | `gridItemVerticalAlignment` |

### Masonry (CSS Grid Level 3)

```css
.gallery {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  grid-template-rows: masonry;     /* → gridType: "columnMasonry" */
  gap: 16px;
}
```

Browser support is Firefox-only, but the converter routes this to Framer's native masonry rendering. `grid-template-columns: masonry` produces `rowMasonry`.

---

## Padding patterns

### Section vertical rhythm

Standard pattern for landing-page sections:

```css
.section {
  padding: 96px 32px;              /* desktop */
}
@media (max-width: 1199px) {
  .section { padding: 72px 24px; } /* tablet */
}
@media (max-width: 810px) {
  .section { padding: 48px 16px; } /* mobile */
}
```

### Padding shorthand

```css
padding: 24px;                     /* all sides */
padding: 24px 32px;                /* vertical horizontal */
padding: 16px 24px 32px;           /* top horizontal bottom */
padding: 8px 16px 24px 32px;       /* top right bottom left */

/* Per-side longhand */
padding-top: 24px;
padding-bottom: 24px;
padding-left: 32px;
padding-right: 32px;
```

All forms work. The converter aggregates them into Framer's per-side `paddingTop/Right/Bottom/Left`.

### Partial padding overrides at breakpoints

If you only want to change `padding-top` at tablet, declare ONLY that side:

```css
.hero { padding: 120px 32px 80px; }

@media (max-width: 1199px) {
  .hero { padding-top: 80px; }     /* only top changes; right/bottom/left stay */
}
```

The converter is smart enough to emit only the changed side at the breakpoint (no longer clobbers untouched sides, Session 4 fix).

---

## Ordering children: `flex-direction: column-reverse` etc.

```css
.hero_split {
  display: flex;
  flex-direction: row;
}
@media (max-width: 810px) {
  .hero_split {
    flex-direction: column;        /* stack vertically on mobile */
  }
}
```

Direction reversal at breakpoints: works (`column-reverse`, `row-reverse` map to Framer's reverse stack).

---

## Don'ts

- **Don't use `margin` for spacing**. Anywhere. Replace with `gap` on the parent or `padding`.
- **Don't use `position: absolute` for column layouts.** Use flex/grid.
- **Don't nest stacks unnecessarily.** Three nested wrappers each doing nothing but adding a `<div>` clutters the Framer layer tree. Group only when you need a different layout direction or padding.
- **Don't mix `flex` and `grid` on the same element.** Pick one.
- **Don't write `align-items: stretch` and then put fixed-width children in a horizontal stack.** Pick consistent rules.
