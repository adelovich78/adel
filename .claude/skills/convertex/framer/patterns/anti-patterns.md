# Anti-patterns: what breaks Framer

A consolidated list of mistakes that produce broken output. Most come from copy-pasting Webflow / Tailwind / browser muscle memory into Framer-targeted code.

---

## 🚫 `margin` for spacing

Framer has no `margin` property. Anywhere.

```css
/* ❌ */
.hero_heading { margin-bottom: 24px; }
.hero_inner { margin: 0 auto; }
.section { margin-top: 80px; }

/* ✅ */
.hero_inner { display: flex; flex-direction: column; gap: 24px; }
.section_outer { display: flex; justify-content: center; }       /* center via parent */
.sections { display: flex; flex-direction: column; gap: 80px; }   /* spacing via parent gap */
```

The only place `margin-bottom` is partially honored: on `<p>` text elements, where it maps to Framer's `--framer-paragraph-spacing` (intra-paragraph rhythm). For inter-element rhythm, always use parent `gap`.

---

## 🚫 CSS variables for tokens

```css
/* ❌ */
:root { --brand: #ff5b3a; --text: #ffffff; }
.button { background: var(--brand); color: var(--text); }

/* ✅ */
.button { background: #ff5b3a; color: #ffffff; }
```

The converter dropped color tokens in Session 5 because Framer can't dedupe across pastes. Inline the actual values.

---

## 🚫 `inherit` / `initial` / `unset` / `revert`

```css
/* ❌ */
.card_heading { color: inherit; font-size: inherit; }

/* ✅ */
.card_heading {
  color: #ffffff;        /* explicit */
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 1.5rem;
  line-height: 1.2;
}
```

The converter has a small `INHERITABLE_PROPS` walk that handles cascade for typography during conversion. But explicit values produce cleaner output and a better edit experience in Framer's panel.

---

## 🚫 Webflow's breakpoint widths

```css
/* ❌ */
@media (max-width: 991px) { … }       /* Webflow tablet */
@media (max-width: 767px) { … }       /* Webflow mobile landscape */
@media (max-width: 479px) { … }       /* Webflow mobile portrait */

/* ✅ */
@media (max-width: 1199px) { … }      /* Framer tablet, 810 */
@media (max-width: 810px) { … }       /* Framer mobile, 390 */
```

Non-standard widths make Framer silently drop the breakpoint variant.

---

## 🚫 Webflow `w-*` classes

```html
<!-- ❌ -->
<div class="w-layout-grid w-layout-blockcontainer">
  <a class="w-button is-primary">Click</a>
  <div class="w-embed">…</div>
</div>

<!-- ✅ -->
<div class="grid container">
  <a class="button is-primary">Click</a>
  <svg>…</svg>           <!-- inline SVG, no embed wrapper needed -->
</div>
```

Framer doesn't recognize Webflow's `w-*` system. Drop the prefixes; use semantic classes.

---

## 🚫 `<style>` tags / `@font-face` / `@keyframes`

```html
<!-- ❌ -->
<style>
  @keyframes spin { 0% { transform: rotate(0); } 100% { transform: rotate(360deg); } }
  .logo { animation: spin 20s linear infinite; }
  @font-face { font-family: 'CustomFont'; src: url(...); }
</style>
```

```html
<!-- ✅ -->
<div class="logo" data-framer-loop="rotate" data-loop-duration="20s">…</div>
```

Animations: use `data-framer-*` markers. Fonts: use families Framer knows (Inter / Framer Sans / Google Fonts).

---

## 🚫 Inline `style` attributes

```html
<!-- ❌ -->
<div style="background: red; padding: 24px;">Hi</div>

<!-- ✅ -->
<div class="card_alert">Hi</div>
<style>.card_alert { background: red; padding: 24px; }</style>
```

The converter doesn't read inline styles. Always use classes.

(Internal exception: the converter itself injects inline `style="--framer-font-size:...; --framer-text-color:..."` on inline `<span>`s when typography needs to be preserved through merging. You should never write these manually.)

---

## 🚫 `::before` / `::after` pseudo-elements

```css
/* ❌ */
.card::before { content: ""; position: absolute; … }
.card::after { content: "→"; }
```

Framer has no concept of pseudo-elements. They're silently dropped.

```html
<!-- ✅ Use a real HTML element -->
<div class="card">
  <div class="card_decoration"></div>          <!-- replaces ::before -->
  <p class="card_text">Card text</p>
  <span class="card_arrow">→</span>            <!-- replaces ::after -->
</div>
```

---

## 🚫 `<em>` for italic

```html
<!-- ❌, Framer renders <em> as a separate inline element awkwardly -->
<p>Build websites <em>fast</em>.</p>

<!-- ✅ -->
<p>Build websites <span class="italic">fast</span>.</p>
<style>
  .italic {
    font-family: 'Inter', sans-serif;
    font-weight: 400;
    font-size: 1rem;
    font-style: italic;
    color: inherit;     /* OK here, inline span, but typography rules suggest declaring */
  }
</style>
```

(Or just put the words you want italic in a span with `font-style: italic`.)

---

## 🚫 `text-shadow`

Not reliably rendered in Framer. For a glow effect, layer a blurred background frame behind the text.

---

## 🚫 `position: absolute` for layout columns

```css
/* ❌ */
.hero_text { position: absolute; left: 0; width: 50%; }
.hero_visual { position: absolute; right: 0; width: 50%; }

/* ✅ */
.hero_split { display: flex; gap: 64px; }
.hero_text { width: 60%; }
.hero_visual { width: 40%; }
```

Reserve `position: absolute` for genuine overlays (badges, decorations, decorative shapes), not for two-column layouts.

---

## 🚫 `display: none` to hide elements

```css
/* ❌ */
.hidden_on_mobile { display: none; }

/* ✅ */
.hidden_on_mobile { visibility: hidden; }
```

`display: none` doesn't map cleanly to Framer's layer system, the element stays in the layer tree at full size. Use `visibility: hidden` (maps to `visible: false` in Framer) or remove the element entirely with breakpoint-conditional rendering at the design level.

Practically: if a section shouldn't appear on mobile, design it differently for mobile rather than hiding the desktop version.

---

## 🚫 `<script>` tags / inline JS / animation libraries

```html
<!-- ❌ -->
<script src="https://unpkg.com/aos"></script>
<script>AOS.init();</script>
<div data-aos="fade-up">…</div>

<script src="https://cdn.gsap.com/gsap.min.js"></script>
<script>gsap.from(".hero h1", { y: 80, opacity: 0 });</script>
```

The converter ignores scripts. Translate to Framer-native:

```html
<!-- ✅ -->
<div data-framer-enter="fade-up" data-enter-y="80">…</div>
```

---

## 🚫 Background SVGs as `background-image: url(data:image/svg+xml,…)`

These get inlined in CSS, but Framer's image handling doesn't always render data URLs correctly. Prefer:

```html
<!-- ✅ inline SVG, handled by Embed CC -->
<svg class="bg_decoration" viewBox="0 0 200 200">…</svg>
```

```css
.bg_decoration {
  position: absolute;
  width: 100%;
  height: 100%;
  pointer-events: none;
}
```

---

## 🚫 Tiled gradients for dotted / dashed lines

Aceternity, Vercel, and similar designs often use a CSS hack to create dashed lines via a 50/50 gradient tiled with `background-size`:

```css
/* ❌ — Framer doesn't tile gradients, the pattern stretches to fill the layer */
.dotted_line {
  background: linear-gradient(to right, #a3a3a3 50%, transparent 50%);
  background-size: 5px 1px;
  height: 1px;
}
```

In a browser, `background-size: 5px 1px` repeats the gradient every 5px → dotted line. In Framer, the gradient is applied to the layer's fill which has no tiling, so you get a single half-gray, half-transparent stretched stripe. Looks broken.

**Alternatives:**

```css
/* ✅ For a border on an element — use border-style */
.featured_card {
  border: 1px dashed #a3a3a3;       /* maps natively to Framer dashed border */
}

/* ✅ For a decorative line that doesn't need to be dotted — solid color */
.divider_line {
  width: 100%;
  height: 1px;
  background: #a3a3a3;
}

/* ✅ For a dotted line that absolutely needs the dotted look — inline SVG */
<svg class="dotted_line" width="100%" height="1" preserveAspectRatio="none" aria-hidden="true">
  <line x1="0" y1="0" x2="100%" y2="0" stroke="#a3a3a3" stroke-width="1" stroke-dasharray="2 2" />
</svg>
```

The SVG approach goes through Framer's Embed CC and renders the dashes correctly via `stroke-dasharray`. Use it when fidelity matters.

---

## 🚫 `calc()` for sizing beyond simple fills

Framer has no `calc()` engine. The converter parses `calc()` best-effort:
- `calc(100% + ...)` / `calc(100vw + ...)` → fill (offset is dropped, layer spans parent only)
- `calc(N% ± ...)` → relative N%
- `calc(<pure pixel arithmetic>)` → evaluated sum
- Anything else → falls back to hug content

For "extend beyond parent" patterns, use two-edge anchoring instead (`left: -100; right: -100`). See [Sizing → Over-extending overlays](../layout/sizing.md).

---

## 🚫 `outline` for focus / borders

```css
/* ❌ */
.button:focus { outline: 2px solid #ff5b3a; }

/* ✅ */
.button:focus-visible { box-shadow: 0 0 0 2px #ff5b3a; }
```

Framer doesn't have outline as a layer property. Use `box-shadow` to fake it.

---

## 🚫 `clip-path` / `mask-image`

Not mapped. Use a parent `overflow: hidden` for rectangular clipping. For other shapes, use Framer's native shape layers (after paste, manually).

---

## 🚫 Font shorthand

```css
/* ❌ */
.heading { font: 700 2rem/1.2 'Inter', sans-serif; }

/* ✅ */
.heading {
  font-family: 'Inter', sans-serif;
  font-weight: 700;
  font-size: 2rem;
  line-height: 1.2;
}
```

The shorthand is parsed inconsistently. Always use longhand.

---

## 🚫 `-webkit-text-fill-color` / `background-clip: text` (gradient text)

Not supported. For brand gradient text, render it as an `<img>` of the headline (uncommon, usually accept a solid color).

---

## 🚫 `filter: brightness/saturate/contrast`

Only `filter: blur(...)` is mapped. Other filter functions are dropped.

---

## 🚫 `grid-template-areas`

Not parsed. Use `grid-template-columns` / `grid-template-rows` with `grid-column` / `grid-row` placement on children.

```css
/* ❌ */
.layout {
  display: grid;
  grid-template-areas:
    "header header"
    "nav main";
}
.header { grid-area: header; }

/* ✅ */
.layout {
  display: grid;
  grid-template-columns: 240px 1fr;
}
.header { grid-column: 1 / 3; }
.nav { grid-column: 1; }
.main { grid-column: 2; }
```

---

## 🚫 `:not()`: `:nth-child()`, `:first-child`, `:last-child`

Not parsed. Use explicit classes (`.card.is-first`, `.card.is-last`) and apply differently.

---

## 🚫 `prefers-reduced-motion` media query

Not currently mapped. Accept that animations will play for everyone. Keep them subtle. (May change in a future converter update.)

---

## Quick checklist before generating

Before generating Framer-targeted HTML/CSS:

- [ ] Zero `margin` declarations
- [ ] Zero `var(--…)` references
- [ ] Breakpoints are 1199 and 810
- [ ] Every text element has font-family, weight, size, line-height, color declared
- [ ] Buttons have `display: inline-flex` (or similar) + bg/padding/radius for promotion
- [ ] Animations are `data-framer-*`, not `<script>` or `@keyframes`
- [ ] No Webflow `w-*` classes
- [ ] No `::before` / `::after`
