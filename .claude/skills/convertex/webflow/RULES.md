# Convertex — Webflow rules (HTML & CSS)

> Webflow ruleset. Loaded by the `convertex` skill router ([../SKILL.md](../SKILL.md))
> only when the target is **Webflow**. Do NOT apply these rules to a Framer conversion.

**Language rule:** Always respond in the user's language. Detect it from their first message. These instructions are in English for precision — your replies must match the user's language.

Generate HTML and CSS compatible with Webflow's native element system. Output two separate blocks: one for HTML (body only), one for CSS.

## Pre-Flight — Ask Before Generating

Ask these 3 questions via a SINGLE **AskUserQuestion** tool call (3 questions in one call), then WAIT for the answer. Do NOT ask them as plain prose when AskUserQuestion is available.

1. **Naming** — options: `Client-First` (apply the [Client-First rules](#client-first) below) / `Free naming`.
2. **Units** — options: `rem` (`1rem = 16px`) / `px`.
3. **Language** — options: `Français` / `English` (the user can choose Other). Adapt placeholder text, ARIA labels, and field names accordingly.

Skip ONLY if the user already answered all 3 in their current message. If AskUserQuestion is unavailable, ask the same 3 questions in one plain-text message.

## Output Format

- **HTML block**: Body content only. No `<html>`, `<head>`, `<body>`, or `<style>` tags. Start with the first element.
- **CSS block**: Separate from HTML. Flat selectors, concrete values, desktop-first with responsive overrides.
- **JS block**: Separate from HTML. Never place `<script>` tags or inline JavaScript inside the HTML. All JavaScript must be pushed as a separate JS block via the `js` field of `create_page` / `update_page` / `patch_page`. Do NOT wrap JS in `<div class="w-embed">` or any HTML embed — the Convertex editor has a dedicated JS panel.
- **Push, don't display**: NEVER paste the code in chat — push it to the Convertex project the user picked (never auto-pick: first `list_projects`, ask via AskUserQuestion, or `create_project` — see the router's Delivery rule), via `notify_sync_start()` then `create_page` / `update_page` / `patch_page`, then reply with a short summary. Output code in chat ONLY if the Convertex MCP is not connected (then offer to install it or to generate in the conversation) or if the user explicitly asks for the code.

---

## 11 Golden Rules

### 1. Flat CSS selectors only

`.class` or `.class1.class2` (combo). **NEVER** use:
- Descendant: `.parent .child` — use `.parent_child` instead
- Child: `.parent > .child` — use `.parent_child` instead
- Sibling: `.a + .b` — use separate classes
- Tag-qualified: `div.container` — use `.container`

### 2. Concrete values only — no CSS variables

Write actual values: `color: #2563eb`, never `color: var(--primary)`. Webflow does not support CSS custom properties natively.

### 3. Combo class pattern

First class = base, additional classes = combos. `class="button is-primary"` means `.button` is the base, `.is-primary` is a combo. In CSS: `.button.is-primary { ... }`.

### 4. Semantic HTML tags

Use `<section>`, `<header>`, `<footer>`, `<nav>`, `<main>`, `<article>`, `<aside>` for their semantic purpose. These render as Block elements in Webflow with their HTML tag preserved.

### 5. No hidden states in CSS

**NEVER** set `display: none`, `opacity: 0`, `visibility: hidden`, or `height: 0` in base CSS. Elements must be visible in Webflow Designer for editing. Set initial hidden states via JavaScript on `DOMContentLoaded`. CSS only defines the active/visible state via combo classes: `.modal.is-open { opacity: 1; }`.

### 6. CSS-first animations — escalate to JS only when needed

Always prefer pure CSS (`:hover`, `transition`, `@keyframes` in ghost blocks). Only use JS class toggling when CSS alone cannot detect the trigger (click, scroll threshold). Only use GSAP when you need scroll-triggering, staggered timelines, or physics. Every class toggled by JS **must** have its combo-class CSS in the flat CSS block. Never mix approaches on the same element. **Read the [Animations guide](patterns/js-animations.md) before generating any animation.**

### 7. Inline SVGs must be wrapped

Raw `<svg>` elements **must** be inside `<div class="w-embed">`. Without the wrapper, SVGs render incorrectly in Webflow.

### 8. Never use `<em>`

`<em>` becomes a block-level element in Webflow. Use `<span>` with a class that sets `font-style: italic` instead.

### 9. Never use inline `style` attributes

All styling must go through CSS classes. The `style` attribute is not supported.

### 10. Webflow components need exact `w-` structures

Navbars, forms, sliders, tabs, dropdowns, lightboxes, and background videos require specific HTML structures with `w-` classes and data attributes. **Read the pattern file** before generating any of these. See [Component Patterns](#component-patterns).

### 11. Encoding for srcdoc preview

The Convertex preview renders everything inside an iframe `srcdoc`. These encoding constraints are mandatory:

- **JS strings**: double quotes only (`"..."`, never `'...'`)
- **JS/CSS comments**: ASCII-only characters (no accents, no typographic apostrophes, no em-dashes)
- **HTML**: no `<!-- -->` comments

**The ASCII-only rule applies ONLY to JS and CSS comments — NEVER to visible HTML text.** Preserve the user's copy exactly as written, accents included: a French hero stays "Gérez vos finances en toute simplicité", never "Gerez ... simplicite". Stripping accents from real content is a defect. Accents, apostrophes and punctuation in headings, paragraphs, links, button labels, `alt` and `aria-label` text must be kept verbatim. The ASCII restriction exists only so that code comments don't break the `srcdoc` parser — it has nothing to do with the words shown on the page.

---

## Responsive Breakpoints

| Breakpoint | Media Query | Direction |
|---|---|---|
| XXL | `min-width: 1920px` | Upward override |
| XL | `min-width: 1440px` | Upward override |
| Large | `min-width: 1280px` | Upward override |
| **Desktop** | _(no media query)_ | **Base styles** |
| Tablet | `max-width: 991px` | Downward override |
| Mobile Landscape | `max-width: 767px` | Downward override |
| Mobile Portrait | `max-width: 479px` | Downward override |

**Rules:**
- Desktop-first: base styles have no media query
- Override downward with `max-width`, upward (rare) with `min-width`
- Only include properties that actually change — never repeat base values
- Use **only** these exact breakpoint values

---

## Selector Reference

### Allowed
```css
.hero_heading { }                /* Single class */
.button.is-secondary { }        /* Combo class */
.button:hover { }               /* Pseudo on class */
.nav_link.is-active:hover { }   /* Combo + pseudo */
```

### Forbidden — will break conversion
```css
.hero .heading { }          /* Descendant → use .hero_heading */
.card > .title { }          /* Child → use .card_title */
.input + .error { }         /* Sibling → use .input_error */
div.container { }           /* Tag-qualified → use .container */
a.nav-link { }              /* Tag-qualified → use .nav-link */
.list_item:nth-child(odd) { } /* Complex pseudo → needs ghost block */
```

---

## Pseudo-Classes

**Supported natively** (write in CSS): `:hover`, `:active`, `:focus`, `:visited`, `:focus-visible`, `:focus-within`, `::placeholder`

**FORBIDDEN — never use, even in ghost blocks:**
`::before`, `::after` — poorly supported by Webflow, not manageable in the Designer. Always use a real HTML element instead (e.g. a `<div>` with a class for decorative elements, icons, or separators).

**Not supported natively** (may use a [ghost block](patterns/ghost-blocks.md) if necessary):
`:nth-child()`, `:first-child`, `:last-child`, `:not()`, `@keyframes`

```html
<div class="w-embed"><style>
  .card:first-child { border-top: none; }
</style></div>
```

---

## Contextual Styling

Webflow has no descendant selectors. When a parent state must affect children, **propagate the combo class** to every affected child:

```html
<div class="pricing_card is-featured">
  <h3 class="pricing_title is-featured">Pro</h3>
  <p class="pricing_price is-featured">$99/mo</p>
</div>
```
```css
.pricing_card.is-featured { background-color: #0f172a; }
.pricing_title.is-featured { color: #ffffff; }
.pricing_price.is-featured { color: #60a5fa; }
```

---

## Element Rules

### Spans
`<span>` is valid only inside text elements (`<p>`, `<h1>`–`<h6>`) or alongside text siblings. In all other cases, use `<div>`.

### Links & Buttons
**`<button>` is atomic in Webflow — it CANNOT have children.** It only holds plain text. If you need an icon or nested elements, use `<a>` (Link Block) instead.

Inside `<a>` Link Blocks, **always use `<div>`** for child elements (text wrappers, icon containers). Never use `<span>` — it creates Text Spans in Webflow instead of Block elements, breaking layout control.

**Link with icon — correct structure:**
```html
<a href="/page" class="button is-primary">
  <div class="button_text">Get started</div>
  <div class="w-embed">
    <svg class="button_icon" width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true">
      <path d="M1 5h8M5 1l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>
</a>
```

**Simple button — text only:**
```html
<button class="button">Get started</button>
```

**NEVER do this:**
```html
<!-- WRONG: children inside <button> -->
<button class="button">
  <div class="button_text">Get started</div>
  <div class="w-embed"><svg>...</svg></div>
</button>

<!-- WRONG: text directly alongside w-embed -->
<a href="/page" class="button">
  Get started
  <div class="w-embed"><svg>...</svg></div>
</a>

<!-- WRONG: span inside a link -->
<a href="/page" class="button">
  <span class="button_text">Get started</span>
</a>
```

### Lists
```html
<ul class="features_list">
  <li class="features_list-item">First feature</li>
  <li class="features_list-item">Second feature</li>
</ul>
```
Both `<ul>` and `<ol>` are supported. Style via classes on the list and list items.

### Blockquote
```html
<blockquote class="testimonial_quote">Customer feedback here.</blockquote>
```

### Horizontal Rule
```html
<hr class="divider" />
```

---

## Inline Components

| Component | HTML | Notes |
|---|---|---|
| **Grid** | `<div class="w-layout-grid my-grid">` | Must have `display: grid` in CSS |
| **Container** | `<div class="w-layout-blockcontainer my-container">` | Centered max-width block |
| **Rich Text** | `<div class="w-richtext">` | CMS-style formatted content |
| **HTML Embed** | `<div class="w-embed">` | Raw HTML, SVG, or `<style>` blocks |

**Note:** `w-container` (used inside navbars) is different from `w-layout-blockcontainer` (general layout container). Don't mix them.

---

## Custom Attributes

- **Standard**: `id`, `href`, `src`, `alt`, `loading`, `target` — set directly on the element
- **Custom**: `data-*`, `aria-*`, `role`, `tabindex` — supported as custom attributes
- **`style`** — **never use**

---

## Component Patterns

These components require specific `w-` HTML structures. **Read the pattern file before generating any of them:**

- [Navbar](elements/navbar.md) — **default to a CUSTOM navbar** (plain `<nav>` + your own flat classes). Build Webflow's native `w-nav` component ONLY if the user explicitly asks for it.
- [Forms](elements/forms.md) — `w-form` wrapper with `w-form-done` / `w-form-fail` states
- [Dropdown](elements/dropdown.md) — `w-dropdown` with toggle and list
- [Slider](elements/slider.md) — `w-slider` with mask, slides, arrows, dot nav
- [Tabs](elements/tabs.md) — `w-tabs` with `data-w-tab` matching between links and panes
- [Lightbox](elements/lightbox.md) — `w-lightbox` with `w-json` config
- [Background Video](elements/background-video.md) — `w-background-video` with data attributes
- [Video](elements/video.md) — `w-video` with YouTube/Vimeo `<iframe>` embed
- [Images](elements/images.md) — `<img>`, responsive `srcset`, SVG handling, `object-fit`
- [Links & Buttons](elements/links-buttons.md) — `<button>` vs `<a>` detection, Link Block vs Text Link

---

## Advanced Reference

- [Ghost Blocks & Dynamic States](patterns/ghost-blocks.md) — hidden states with JS, `::before`/`::after`, `@keyframes`, unsupported CSS via embed blocks
- [JS Animations](patterns/js-animations.md) — CSS transitions + combo classes vs GSAP, decision guide, self-check before delivering
- [CSS Property Reference](css/best-practices.md) — detailed writing guide for spacing, borders, gradients, transitions, grid, flexbox, typography, filters, effects

---

## Accessibility Checklist

- Every `<img>` must have `alt` (empty `alt=""` for decorative images)
- Form inputs must have `<label>` with matching `for`/`id`
- Interactive elements must be keyboard-focusable with visible focus styles
- Icon-only buttons/links need `aria-label`
- Color contrast: WCAG AA minimum (4.5:1 normal text, 3:1 large text)
- Don't rely on color alone to convey information
- Use semantic tags (`<nav>`, `<main>`, `<section>`, `<article>`)

---

## Client-First

**Only apply this section if the user chose Client-First.** Otherwise skip entirely.

### 5 Rules

1. **Page skeleton is mandatory** — `page-wrapper > main-wrapper > section_[id] > padding-global padding-section-[size] > container-[size] > content`
2. **Underscore = custom, hyphen = utility** — `hero_heading` (custom), `text-size-large` (utility), `is-primary` (combo)
3. **All units in rem** — Never px. Only exception: `border-width` can use px.
4. **Max 2 utility classes per element** — 3+ utilities = create a custom class instead. Exception: `padding-global padding-section-large`.
5. **Flat CSS selectors only** — Same as Webflow Rule #1.

### 3 Class Types

**Custom** — `[folder]_[element]`: specific to one component.
`hero_heading`, `footer_link`, `pricing_card`

**Utility** — `[category]-[property]-[value]`: reusable site-wide.
`text-size-large`, `margin-bottom margin-medium`, `hide-tablet`

**Combo** — `is-[variant]`: modifier on a base class. Never used alone.
`is-secondary`, `is-featured`, `is-active`

### Page Skeleton

```html
<div class="page-wrapper">
  <main class="main-wrapper">
    <section class="section_hero">
      <div class="padding-global padding-section-large">
        <div class="container-large">
          <!-- content -->
        </div>
      </div>
    </section>
  </main>
</div>
```

Nav and footer go **outside** `main-wrapper`, **inside** `page-wrapper`. `padding-global` and `padding-section-[size]` go on the **SAME div**.

### Detailed Client-First Reference

Read these files for complete patterns and values:

- [Naming Conventions](client-first/naming/conventions.md) — full rules for custom, utility, and combo classes
- [Page Skeleton](client-first/structure/page-skeleton.md) — 5-layer structure, container sizes, padding values, complete page example
- [CSS Rules](client-first/css/rules.md) — rem values, core structure CSS, mandatory responsive overrides, typography base styles
- [Typography Utilities](client-first/utilities/typography.md) — `heading-style-*`, `text-size-*`, `text-weight-*`, `text-color-*`, `text-align-*`
- [Spacing Utilities](client-first/utilities/spacing.md) — margin/padding two-class system, spacer blocks, all size values
- [Layout Utilities](client-first/utilities/layout.md) — `max-width-*`, `hide-*`, `background-color-*`, icons, positioning, aspect ratios
- [Button Utilities](client-first/utilities/buttons.md) — `.button` base, `is-secondary`/`is-text`/`is-small`/`is-large` variants
