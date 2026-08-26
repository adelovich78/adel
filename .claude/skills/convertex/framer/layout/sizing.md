# Sizing: the 4 Framer types

Every Framer layer has one of 4 sizing types per axis (`widthType`, `heightType`):

| Type | Code | Meaning | CSS that produces it |
|---|---|---|---|
| **Fill** | `3` | Stretches to fill parent | `width: 100%`, `flex: 1`, `flex-grow: 1`, `width: 100vw` (≥100%) |
| **Hug content** | `2` | Wraps tightly around children | `display: inline-flex`, `display: inline-block`, `display: inline-grid`, `width: auto`, `width: fit-content` |
| **Fixed** | `0` | Hard pixel value | `width: 320px` |
| **Relative** | `1` | Fraction of parent (e.g. 50%) | `width: 50%`, `width: 33.33%` |

**Default for a FrameNode is `Fill` (`3`)**. If you don't set a size, the layer stretches.

---

## CSS recipes (copy-paste blocks)

### A button hugging its label

```css
.button {
  display: inline-flex;          /* → widthType: 2 (hug) */
  align-items: center;
  gap: 8px;
  padding: 14px 24px;
  background: #ff5b3a;
  border-radius: 999px;
  color: #fff;
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 1rem;
  line-height: 1;
}
```

The `display: inline-flex` is what makes Framer hug. Without it, the button would stretch to fill the parent column.

### A hero column filling the page width with a max cap

```css
.hero_inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;                   /* → widthType: 3 (fill) */
  max-width: 1200px;             /* → maxWidth: "1200px", caps the fill */
  padding: 120px 32px;
  gap: 32px;
}
```

The converter combines `width: 100%` + `max-width: 1200px` into a fill layer that stops at 1200. Same outcome as `max-width: 1200px` alone (without `width`), but more explicit.

### A two-column hero: 60/40 split

```css
.hero_split {
  display: flex;
  align-items: center;
  gap: 64px;
}
.hero_split_text {
  width: 60%;                    /* → widthType: 1 (relative), value 0.6 */
}
.hero_split_visual {
  width: 40%;                    /* → widthType: 1 (relative), value 0.4 */
  aspect-ratio: 4 / 3;
  background: #f3f3f3;
  border-radius: 16px;
}
```

### A fixed-width sidebar

```css
.sidebar {
  width: 320px;                  /* → widthType: 0 (fixed), value 320 */
  height: 100vh;                 /* → heightType: 3 (fill) */
}
```

### A square card hugging its content

```css
.feature_card {
  display: flex;                 /* default fill if left bare */
  flex-direction: column;
  gap: 16px;
  padding: 32px;
  background: #111;
  border-radius: 16px;
  width: 100%;                   /* explicit fill, clearer than relying on default */
}
```

---

## `max-width` and `min-width`

Both map to native Framer string properties:
- CSS `max-width: 1200px` → Framer `maxWidth: "1200px"` (caps a fill-width element)
- CSS `min-width: 240px` → Framer `minWidth: "240px"`

When `max-width` is declared **without** `width`, the converter sets `widthType: 3` (fill) automatically so the element stays responsive.

```css
.section_inner {
  max-width: 1280px;             /* element fills parent up to 1280px cap */
  padding: 0 32px;
}
```

---

## Heights

Same 4 types, but practical advice for landing pages:

| Use | CSS |
|---|---|
| Section that sizes to its content (most cases) | _omit `height` entirely_ → default `heightType: 2` (hug) |
| Hero filling viewport | `min-height: 100vh` → 100vh maps to fill (`heightType: 3`) |
| Card with strict aspect | `aspect-ratio: 16 / 9` |
| Strict pixel height | `height: 480px` |

**Avoid** setting fixed `height` on text containers, text wraps differently across breakpoints, fixed height clips content.

---

## Aspect ratio

```css
.video_wrap {
  width: 100%;
  aspect-ratio: 16 / 9;          /* → aspectRatio: 1.777… */
  border-radius: 16px;
  overflow: hidden;
}
```

Native Framer property; the converter passes the ratio through directly.

---

## Position absolute / fixed

Used sparingly. Position values map directly:

```css
.badge {
  position: absolute;
  top: 16px;
  right: 16px;
  padding: 4px 10px;
  background: #ff5b3a;
  color: #fff;
  border-radius: 999px;
}
```

The parent of a `position: absolute` child should have `position: relative` for the absolute positioning to anchor correctly. The converter respects all four edge values (`top`, `right`, `bottom`, `left`).

**Do not** use `position: absolute` for layout purposes (e.g. side-by-side columns). Use flex/grid.

### Over-extending overlays (lines, decorations beyond parent edges)

A common Aceternity / Webflow pattern: a decorative line that extends 100px beyond each side of a card, expressed as `left: -100px; width: calc(100% + 200px)`. Framer has no `calc()` for this, and the over-extension is lost.

**Use both edges with negative values instead.** Framer respects two-edge anchoring and computes the width dynamically:

```css
.card { position: relative; }

/* Horizontal line extending 100px beyond on each side */
.card_line_h {
  position: absolute;
  left: -100px;
  right: -100px;             /* both edges → element spans (parent + 200px) */
  height: 1px;
  background: #a3a3a3;
  pointer-events: none;
}
.card_line_top { top: -2px; }
.card_line_bottom { bottom: -2px; }

/* Vertical line extending 100px beyond top/bottom */
.card_line_v {
  position: absolute;
  top: -100px;
  bottom: -100px;
  width: 1px;
  background: #a3a3a3;
  pointer-events: none;
}
.card_line_left { left: -2px; }
.card_line_right { right: -2px; }
```

Drop `width: calc(...)` entirely. Framer will calculate the dimension from the two edge offsets.

The converter does parse simple `calc()` expressions (containing `100%`, `100vw`, etc.) as fill so it won't hard-fail, but the layer will only span the parent's bounds.

---

## Implicit child sizing: what the converter does for you

If you have a flex stack with explicit `align-items: flex-start | center | flex-end`, the converter automatically hugs children on the cross axis (so cards in a vertical stack don't stretch full-width if you didn't intend it).

If you have a horizontal stack, children's main-axis (width) hugs by default, unless you've set an explicit width.

This is mostly invisible, write the CSS naturally, the converter applies CSS flex semantics.

**Exception**: `display: flex; flex-wrap: wrap` (a wrapping row of buttons) keeps children at fill so the wrap behavior works. The converter exempts wrap stacks from the auto-hug rule.

---

## Don'ts

- **Don't mix `width` with `flex: 1`**, write one or the other. Conflict resolution picks one but it's harder to reason about.
- **Don't write `width: auto` to mean fill**, it means hug. Use `width: 100%`.
- **Don't write `height: 100%`**, heights percentages need a parent height to resolve. Use `min-height: 100vh` for full-viewport, or omit and let content size the layer.
- **Don't size a text RichTextNode with fixed `width`**, text wraps. Use `max-width` for line-length caps.
