# Convertex — Framer rules (HTML & CSS)

> Framer ruleset. Loaded by the `convertex` skill router ([../SKILL.md](../SKILL.md))
> only when the target is **Framer**. Do NOT apply these rules to a Webflow conversion.

**Language rule:** Always respond in the user's language. Detect it from their first message. These instructions are in English for precision; your replies match the user's language.

Generate HTML and CSS that the Convertex Framer converter will turn into native Framer clipboard JSON (`application/x-framer-layers`). Output two separate blocks: one for HTML (body only), one for CSS.

This skill is **not** a Webflow skill. Most Webflow idioms break in Framer. Read the [Anti-Patterns](patterns/anti-patterns.md) before generating anything.

---

## Before Generating

Do NOT ask clarifying questions — the user states what they want, so generate directly from their request. Apply sensible defaults for anything unspecified:

- **Fonts**: default to Inter (we pick the right Framer encoding) unless the user names a font.
- **Colors**: use the user's brand colors if given, otherwise neutral, tasteful defaults.
- **Language**: match the user's language for copy, ARIA labels, and alt text.
- **Scope**: build exactly what was asked (a section, a page, etc.) — nothing more.

---

## Output Format

- **HTML block**: body content only. No `<html>`, `<head>`, `<body>`, or `<style>` tags. Start with the first element.
- **CSS block**: separate from HTML. **Desktop-first** with `@media (max-width: 1199px)` (tablet) and `@media (max-width: 810px)` (mobile) overrides.
- **No `<script>`, no JS file**: Framer animations are configured via `data-framer-*` attributes that the converter reads and emits as native Framer effects. There is no JS layer in Framer pastes.
- **Optional React Component block**: when a section needs stateful UI that native Framer effects cannot express (multi-step forms, custom carousels, live counters, etc.), declare a slot in the HTML (`<div data-framer-component-slot="..." data-framer-component-label="...">`) AND push a TSX component via the `js` field of `create_page` / `update_page` / `patch_page`. The Convertex IDE repurposes the JS panel as a "Composant" panel when the project is Framer. See [Code Components](elements/components.md).
- **Push, don't display**: NEVER paste the code in chat — push it to the Convertex project the user picked (never auto-pick: first `list_projects`, ask via AskUserQuestion, or `create_project` — see the router's Delivery rule), via `notify_sync_start()` then `create_page` / `update_page` / `patch_page`, then reply with a short summary. Output code in chat ONLY if the Convertex MCP is not connected (then offer to install it or to generate in the conversation) or if the user explicitly asks for the code.

---

## The Framer Mental Model

Framer is **not a CSS engine**. Each element pasted becomes a discrete layer with its own properties. Three implications you must internalize before writing a single line:

### 1. Children do NOT inherit reliably from parents

The converter walks a small set of `INHERITABLE_PROPS` (`color`, `font-family`, `font-size`, `font-weight`, `line-height`, `letter-spacing`, `text-align`) from ancestors when an element doesn't declare them. Anything outside that set does not flow down. And once in Framer, an inherited value shows as an **empty field** in the layer's panel, which makes it look broken to the editor.

**Rule:** declare typography, color, and visual properties **explicitly on every element** that needs them. Don't rely on CSS cascade. Even a heading inside a hero with `color: white` should have `color: white` on the heading itself, not just on the parent.

### 2. Framer has no `margin`

Margin between siblings, `margin: 0 auto` centering, `margin-top` to offset from a previous element, all silently dropped. Spacing is expressed as `gap` on a flex/grid parent, plus `padding` on the parent for inner inset.

If you catch yourself typing `margin`, you're targeting the wrong tool.

### 3. Framer sizing has 4 types: your CSS must map cleanly to one

Every layer is `fill` (stretch), `fit-content` (hug), `fixed` (px), or `relative` (fraction). The converter maps your CSS to one of these via [precise rules](layout/sizing.md). Ambiguous CSS (e.g. fixed `width` and `flex: 1` on the same element) will pick a winner, but you should write unambiguously.

---

## 13 Golden Rules

### 1. Explicit-everything on text elements

On every `<h1>` to `<h6>`, `<p>`, `<a>`, `<span>` with text content, **explicitly declare**:

```css
font-family: 'Plus Jakarta Sans', sans-serif;
font-weight: 600;
font-size: 1.25rem;
line-height: 1.4;
color: #ffffff;
```

No relying on cascade. See [Typography rules](typography/rules.md).

### 2. No `margin`. Ever.

Spacing comes from `gap` on flex/grid containers + `padding` on the parent. See [Flex & Grid](layout/flex-grid.md).

### 3. No CSS variables in computed style: only on `--framer-*`

Don't write `var(--primary)`. Inline the actual color. The only CSS variables Framer respects are its own (`--framer-text-color`, etc.) which the converter generates internally. See [Colors](styling/visual.md#colors).

### 4. Framer breakpoints are `1199` and `810`, not `991` or `767`

Framer recognizes specific viewport widths. Use exactly:
- Desktop (base, no media query) up to 1199
- `@media (max-width: 1199px)` for tablet (810)
- `@media (max-width: 810px)` for mobile (390)

**Never write other widths.** Common reflex mistakes and what they actually do in Framer:

| Reflex (don't write)              | Framer bucket | Use instead              |
|-----------------------------------|---------------|--------------------------|
| `@media (max-width: 768px)` (Tailwind `md`, Bootstrap) | **mobile only** (silently) | `@media (max-width: 810px)` |
| `@media (max-width: 991px)` (Bootstrap `lg`, Webflow tablet) | tablet | `@media (max-width: 1199px)` |
| `@media (max-width: 1024px)` (Tailwind `lg`) | tablet      | `@media (max-width: 1199px)` |
| `@media (max-width: 479px)` / `767px` (Webflow) | mobile        | `@media (max-width: 810px)` |
| Tailwind classes `md:`, `lg:`, `sm:` (mobile-first inline) | n/a — gets ignored | rewrite as desktop-first @media |

A `max-width` value strictly **below 810** ends up in the **mobile** variant, even if you wanted tablet. So `(max-width: 768px)` overrides won't show in Framer's tablet view. Always use `810` or `1199`.

See [Breakpoints](layout/breakpoints.md).

### 5. Re-declare changing properties at each breakpoint, fully

Don't write delta-style overrides like `padding-top: 20px` alone. Re-declare full shorthands the converter expects (e.g. full `padding: 40px 24px`). Partial overrides are honored but can silently drop other sides.

### 6. `<a>` and `<button>` with frame styling become Frame layers

When `<a class="btn">` has `display: flex|inline-flex|inline-block|grid` AND any of (background-color, border, padding > 0, border-radius), the converter promotes it to a FrameNode containing a RichTextNode child. Use this pattern for buttons. See [Buttons & links](elements/buttons-and-links.md).

### 7. Use `inline-flex` / `inline-block` to hug content

Default Framer width is `fill`. To make a button hug its label, write `display: inline-flex`. This sets `widthType: 2` (auto/hug). See [Sizing](layout/sizing.md).

### 8. Animations live in `data-framer-*` attributes

Mount-appear, scroll-appear, hover, press, loop, drag, ticker, text effect, all driven by `data-*` attributes the converter reads. Never write `@keyframes`, never include AOS/GSAP scripts. See [Animations index](animations/).

### 9. Hover and Press states use real CSS pseudo-classes

`:hover` and `:active` are read directly from the CSS by the converter and turned into `whileHover*` / `whileTap*` props. Use them naturally:

```css
.button:hover { transform: translateY(-2px); transition: transform 0.3s spring; }
.button:active { transform: scale(0.96); }
```

The custom `spring` keyword is allowed and routes to Framer's spring physics. See [States](styling/states.md).

### 10. Media tags map to Framer Code Components automatically

`<video>`, `<iframe src="youtube.com/...">`, `<iframe src="vimeo.com/...">`, generic `<iframe>`, and inline `<svg>` are mapped to Framer's built-in Video / YouTube / Vimeo / Embed Code Components. Just write standard HTML; the converter handles the rest. See [Media](elements/media.md).

### 11. Use semantic HTML

`<header>`, `<main>`, `<section>`, `<nav>`, `<footer>`, `<article>`, `<aside>`, they all become FrameNodes with their `htmlTag` preserved. This helps SEO and accessibility once the page is published from Framer. Don't replace them with `<div>`.

### 12. Split native vs. Code Component aggressively

When a design has a part that Framer can't express (custom animation, stateful UI, third-party widget), the Code Component slot must contain **the bare minimum that cannot be native in Framer**. Everything else, layout, text, buttons, hover, scroll-appear, ticker, drag, loop, must stay native in the page's HTML/CSS. Think like a Framer designer would: they reach for the native toolbox first and isolate only the irreducible custom piece.

Example: a hero with 36 animated SVG paths in background + animated title + CTA → native `<section>` + `<h1 data-framer-text-effect>` + `<a class="cta">` with `:hover`, and a Code Component slot **only for the SVG paths layer**. The slot is one absolute-positioned background frame, not the whole hero.

See [Code Components: the Split Principle](elements/components.md#the-split-principle-read-this-first-every-time) for the full decision matrix.

### 13. Responsive coverage is non-negotiable, in one of two modes

The mode is decided by the input. Pick one, never both.

**Authoring mode** — the user gave a brief, a screenshot, or a visual reference, no source code:

- You MUST emit BOTH `@media (max-width: 1199px)` AND `@media (max-width: 810px)` blocks in the CSS.
- Minimum coverage at mobile (810): every flex row stacks to `column`, every `gap`/`padding` ≥ 32px is reduced, hero `font-size` is reduced at least one step.
- **Self-check before delivering**: count your `@media` blocks. If either of the two is missing, the output is incomplete, redo it before pushing.

**Preservation mode** — the user provided source code (UI library snippet, Tailwind component, existing CSS, Shadcn/MUI/Tailwind UI block, etc.):

- You MUST preserve every responsive decision from the source. Never drop a media query, never delete a Tailwind responsive prefix.
- Translate breakpoints to Framer's: Tailwind `sm` (640) → keep as base if relevant, `md` (768) → `@media (max-width: 810px)`, `lg` (1024) → `@media (max-width: 1199px)`, `xl` (1280) → base desktop. Custom widths map to the nearest Framer breakpoint, never get dropped silently.
- Do not "improve" or simplify the source's responsive choices. Preserve intent over personal taste. If you genuinely think a breakpoint is wrong, flag it to the user instead of editing it out.

Detection: if the input message contains an HTML/CSS/JSX block, or Tailwind utility classes, or references a UI library by name, you are in Preservation mode. Otherwise Authoring.

---

## Selectors: what the converter understands

**Allowed and recommended:**
```css
.hero_heading { }                     /* single class, flat */
.hero .heading { }                    /* descendant, Framer accepts these */
.card > .title { }                    /* child combinator */
.button:hover { }                     /* pseudo-class */
.button:active { }                    /* pseudo-class for Press */
[data-framer-component="..."] { }     /* attribute (rare) */
```

**Avoid (not parsed):**
```css
.list_item:nth-child(odd) { }    /* complex pseudo, not mapped */
.parent::before { }              /* pseudo-element, not supported */
.parent::after { }               /* pseudo-element, not supported */
@keyframes spin { }              /* not used; use data-framer-loop instead */
@supports (...) { }              /* feature queries ignored */
```

Unlike Webflow, descendant selectors are fine in Framer-targeted CSS. But the **explicit-everything rule** for text elements (Golden Rule #1) means most properties end up on the leaf class anyway.

---

## Responsive Breakpoints

| Width | Media query | Direction |
|---|---|---|
| **Desktop** | _(base, no media query)_ | Base styles, target 1200+ |
| Tablet | `@media (max-width: 1199px)` | Override downward |
| Mobile | `@media (max-width: 810px)` | Override downward |

**Critical:** the converter cascades desktop CSS + applicable @media rules in size-descending order before mapping each breakpoint. So mobile (`max-width: 810px`) gets desktop AND tablet rules merged into it. You don't need to re-declare what stayed the same.

For the full mechanics (override hygiene, when to re-declare, edge cases), see [Breakpoints](layout/breakpoints.md).

---

## Pseudo-Classes

- **`:hover`** → mapped to `whileHover*` (Framer's hover gesture). All transform fns + opacity + box-shadow + transition.
- **`:active`** → mapped to `whileTap*` (Framer's press gesture). Same axes as hover.
- **`:focus`, `:focus-visible`**, passed through, but Framer doesn't have a dedicated focus state. Use sparingly.
- **`:visited`, `::placeholder`**, not converted, leave them out.

See [States](styling/states.md).

---

## Element Rules

### Spans

`<span>` is valid only inside text elements (`<p>`, `<h1>`,`<h6>`, `<a>`, `<button>`) or alongside text. In all other cases, use `<div>`.

### Links and buttons

`<a>` and `<button>` with frame-style CSS (display + bg/border/padding/radius) are promoted to FrameNodes that contain their label text as a child RichTextNode. See [Buttons & links](elements/buttons-and-links.md).

### Lists

```html
<ul class="features_list">
  <li class="features_list_item">Feature one</li>
  <li class="features_list_item">Feature two</li>
</ul>
```

`<ul>` and `<ol>` are passed through as FrameNodes. Style via classes on the list and the items. **Set list-style explicitly** since browser defaults vary and Framer doesn't render bullets the same way.

### Blockquote

```html
<blockquote class="testimonial_quote">Customer feedback here.</blockquote>
```

Treated as a block text element. The wrapper FrameNode is suppressed (becomes a bare RichTextNode under the parent) unless the blockquote has frame-style CSS.

### Horizontal Rule

```html
<hr class="divider" />
```

Becomes a small FrameNode. Style via the class (`height: 1px; background: #333; width: 100%`).

### Images

```html
<img src="..." alt="..." width="800" height="600" loading="lazy" />
```

`width` and `height` HTML attributes set Framer's `intrinsicWidth` / `intrinsicHeight`. `alt` becomes `altAttribute` on the node. See [Media](elements/media.md).

### Videos and embeds

`<video>`, `<iframe src="youtube...">`, `<iframe src="vimeo...">`, generic `<iframe>`, and inline `<svg>` are routed to Framer's built-in Code Components (Video / YouTube / Vimeo / Embed). See [Media](elements/media.md).

---

## Custom Attributes

Standard: `id`, `href`, `src`, `alt`, `loading`, `target`, `rel`, `type`, set directly.

`id` does double duty:
- **Anchor link target**, emits Framer `elementId` so `<a href="#section-id">` jumps work.
- **Cross-element animation reference**, used by `data-scroll-target` and `data-drag-constraint` to wire up onScrollTarget triggers. See [Scroll appear](animations/appear-and-scroll.md#scroll-target).

`data-framer-*` and `data-{enter,scroll,text,loop,drag,ticker,scroll-target}-*`, the animation marker family. See [Animations](animations/).

`aria-*`, `role`, `tabindex`, accessibility, passed through.

**`style=""` inline attribute**, never use. Style only via CSS classes.

---

## Index: read these for every section

**Layout & sizing**
- [Sizing](layout/sizing.md), the 4 Framer types and their CSS recipes
- [Flex & Grid](layout/flex-grid.md), gap-only spacing, flex/grid patterns
- [Breakpoints](layout/breakpoints.md), 1200/810/390 rules

**Typography**
- [Typography rules](typography/rules.md), explicit-everything, paragraph spacing
- [Fonts catalog](typography/fonts.md), Inter / Framer Sans / Google Fonts

**Styling**
- [Visual](styling/visual.md), colors, borders, radius, shadows, blur, transform, blend modes
- [States](styling/states.md), `:hover`, `:active`, transitions

**Animations** (data-* attributes, read before adding any motion)
- [Appear & Scroll](animations/appear-and-scroll.md), mount appear, scroll appear, stagger, scroll-target
- [Text Effect](animations/text-effect.md), per-token text animations
- [Loop, Drag, Ticker, Exit](animations/loop-drag-ticker.md), continuous + interaction effects

**Elements**
- [Media](elements/media.md), img, video, iframe (yt/vimeo/embed), svg
- [Buttons & Links](elements/buttons-and-links.md), frame-intent promotion
- [Code Components](elements/components.md), TSX slots for stateful UI

**Patterns**
- [Anti-patterns](patterns/anti-patterns.md), what breaks Framer (margin, var(), Webflow idioms…)

**Examples** (canonical templates)
- [Hero centered](examples/hero-centered.md)
- [Hero split with visual](examples/hero-split.md)
- [Feature cards grid](examples/feature-cards.md)
- [Footer](examples/footer.md)
- [Animation showcase](examples/animation-showcase.md)

---

## Accessibility Checklist

- Every `<img>` has `alt` (use `alt=""` for purely decorative).
- Form inputs have a paired `<label for="...">`.
- Interactive elements are keyboard-focusable; visible focus styles via `:focus-visible`.
- Icon-only buttons / links carry `aria-label`.
- Color contrast: WCAG AA, 4.5:1 normal text, 3:1 large text.
- Don't rely on color alone to convey information.
- Use semantic landmarks (`<nav>`, `<main>`, `<header>`, `<footer>`, `<section>`).
- Animation respects user motion preferences: keep animations subtle. (`prefers-reduced-motion` is not currently mapped to Framer; design with that in mind.)

---

## When Animations Should Be Used

Framer animations are powerful but easy to overdo. A landing page with everything fading and bouncing feels like a 2014 Bootstrap demo. Use animations to **reinforce hierarchy** and **draw attention to value props**, not to decorate every element.

Recommended cadence:
- **Hero**: parent stagger on the eyebrow + headline + lead + CTA cluster (`data-framer-stagger="120ms"`), `fade-up` preset, short distance (40 to 60px).
- **Feature cards / pricing**: `data-framer-scroll-appear="fade-up"` on each card, threshold 0.2, animate-once true.
- **Hover** on interactive elements (buttons, cards, links): a translate + shadow change is enough, no scale > 1.05.
- **Loop / Ticker**: only for genuine logo strips, marquees, and trust badges. Never on hero or copy.
- **Text Effect**: reserve for one big hero headline, not body copy.
- **Drag**: only when the interaction makes sense (image gallery cards, before-after sliders).

A safe default: `fade-up` with stagger on hero, `fade-up scroll-appear` on subsequent sections, `:hover` translate on CTAs. Skip the rest unless the brief calls for it.
