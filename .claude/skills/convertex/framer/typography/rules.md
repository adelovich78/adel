# Typography rules: explicit-everything

Framer treats each text element as an independent layer. Cascade is unreliable, and inherited properties show as empty fields in Framer's panel, which the user reads as "broken style".

**Declare typography on every text element.** Yes, every one.

---

## Required properties on every text element

For each `<h1>` to `<h6>`, `<p>`, `<a>`, `<blockquote>`, `<li>`, and any `<span>` that holds text:

```css
.hero_heading {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  font-size: 4.5rem;
  line-height: 1.05;
  letter-spacing: -0.02em;
  color: #ffffff;
}
```

Six properties on every text element. Always.

If you have a class hierarchy like `.h1 .text-large .text-white`, **don't split** these properties across them, flatten them onto the leaf class. Cascade across multiple classes works in browsers but creates surprising results in Framer's text-style preset assignment.

---

## Optional properties (when relevant)

Only declare these when the design calls for them:

```css
text-align: left | center | right | justify;
text-transform: uppercase | lowercase | capitalize | none;
text-decoration: underline | line-through | none;
font-style: italic | normal;
word-spacing: 0.05em;
text-wrap: balance;                  /* nice for headings, Framer maps it natively */
```

---

## Font-size scale (suggested)

A consistent scale keeps the layer tree readable. Suggested in rem (1rem = 16px):

| Role | Size | Line height |
|---|---|---|
| Display / hero h1 | 4 to 4.5rem | 1.05 |
| H2 / section title | 2.5 to 3rem | 1.1 |
| H3 / card title | 1.5 to 1.75rem | 1.2 |
| H4 / inline title | 1.125 to 1.25rem | 1.3 |
| Lead paragraph | 1.25rem | 1.5 |
| Body | 1rem | 1.55 |
| Small / caption | 0.875rem | 1.45 |
| Eyebrow / label | 0.75 to 0.875rem | 1.1 (often `text-transform: uppercase` + `letter-spacing: 0.08em`) |

These are starting points; brand directives override.

---

## Mobile downscaling

Re-declare `font-size` and `line-height` at the breakpoint when they change. Family / weight / color usually stay.

```css
@media (max-width: 1199px) {
  .hero_heading { font-size: 3.25rem; line-height: 1.1; }
}
@media (max-width: 810px) {
  .hero_heading { font-size: 2.25rem; line-height: 1.15; letter-spacing: -0.01em; }
}
```

Rough scaling guide: tablet ≈ 0.75× desktop, mobile ≈ 0.55 to 0.6× desktop. Adjust per design.

---

## Letter spacing

Use `em` for typographic letter-spacing (scales with font-size):

```css
letter-spacing: -0.02em;             /* tight on big headings */
letter-spacing: 0;                    /* default body */
letter-spacing: 0.08em;              /* uppercase eyebrows */
```

`px` works but doesn't scale across breakpoints. Prefer `em`.

---

## Line height

Use unitless or `em` values, both supported. Avoid `px` for line-height (doesn't scale).

```css
line-height: 1.05;                   /* tight headings */
line-height: 1.5;                    /* body */
line-height: 1.1;                    /* dense h2 */
```

---

## Color

Always declare `color` on text elements. Use raw rgba/hex, never `var()`.

```css
color: #ffffff;                      /* hex */
color: rgba(255, 255, 255, 0.8);     /* with alpha */
color: rgb(15, 23, 42);              /* rgb form */
```

The converter normalizes all forms to `rgba(r, g, b, a)` for Framer.

For an opacity'd text (e.g. de-emphasized lead paragraph), prefer `rgba(255,255,255,0.8)` over `opacity: 0.8` on the element, `opacity` cascades into all children and changes the entire layer's alpha including borders/shadows.

---

## Inline span styling inside a paragraph

Inline emphasis inside a paragraph requires explicit class + typography on the span:

```html
<p class="lead">
  Build websites that <span class="lead_highlight">feel handcrafted</span>, ship fast.
</p>
```

```css
.lead {
  font-family: 'Inter', sans-serif;
  font-weight: 400;
  font-size: 1.25rem;
  line-height: 1.5;
  color: #cbd5e1;
}
.lead_highlight {
  font-family: 'Inter', sans-serif;   /* re-declare, even if same as parent */
  font-weight: 600;
  font-size: 1.25rem;
  line-height: 1.5;
  color: #ffffff;                     /* the actual emphasis */
}
```

The converter inlines this as a `--framer-font-weight: 600; --framer-text-color: #fff` style on the span inside the html string of the RichTextNode (Session 5 fix for inline typography preservation).

---

## Paragraph spacing

If you need extra space between paragraphs in a multi-paragraph block, use parent `gap`:

```html
<div class="content">
  <p class="lead">First paragraph.</p>
  <p class="lead">Second paragraph.</p>
</div>
```

```css
.content {
  display: flex;
  flex-direction: column;
  gap: 16px;                          /* paragraph-to-paragraph rhythm */
}
```

**Avoid** `margin-bottom` on `<p>`, Framer doesn't honor it (other than as a small intra-RichText paragraph spacing, which won't help cross-paragraph rhythm).

---

## Text alignment

```css
text-align: left | center | right | justify;
```

Maps directly to Framer's `--framer-text-alignment`. `justify` is honored but rarely looks good in landing pages.

---

## Text wrap (heading polish)

```css
.hero_heading {
  text-wrap: balance;                 /* avoids orphan words on a heading */
}
.lead {
  text-wrap: pretty;                  /* slightly less aggressive, for body */
}
```

Both map to `textWrapBalance: true` on the Framer RichTextNode.

---

## What NOT to do

- **Don't rely on cascade** for font-family or color. Re-declare on every text element. The converter walks `INHERITABLE_PROPS` defensively, but explicit is more reliable.
- **Don't use CSS variables** (`color: var(--brand-text)`). Inline the hex.
- **Don't use `em`/`rem` mixed with px in the same scale**, pick one (rem preferred for typography, px for borders/shadows).
- **Don't write `font: 1rem/1.5 'Inter', sans-serif`** (shorthand). Use longhand. The shorthand is parsed but produces inconsistent results when only some properties are present.
- **Don't use `<em>` for italic**, it works but creates a separate inline element. Use `<span class="x">` with `font-style: italic` for cleaner output.
- **Don't use `text-shadow`**, Framer doesn't render it consistently. Use a layered FrameNode behind the text if you need a glow.
