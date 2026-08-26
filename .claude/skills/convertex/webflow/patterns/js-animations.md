# Animations — Webflow Compatibility

## CSS-First Rule

**Always prefer pure CSS animations.** Only escalate to JavaScript when CSS alone cannot achieve the effect. This produces lighter code, fewer moving parts, and maximum compatibility with Webflow.

**Escalation ladder — stop at the first level that works:**

1. **CSS only** — `:hover`, `transition`, `@keyframes` → no JS at all
2. **CSS transitions + JS class toggle** — JS is just a switch, all visual logic stays in CSS
3. **GSAP** — only when you need scroll-triggering, staggered timelines, or physics

---

## The Compatibility Problem

JavaScript that adds CSS classes at runtime only produces visible effects if the corresponding CSS rules exist **in Webflow**. When pasting converted code into Webflow:

- **Flat CSS** (the CSS block) → converted to native Webflow styles ✓
- **Ghost block CSS** (`w-embed > style`) → injected at runtime as raw HTML ✓
- **CSS that exists nowhere** → animation breaks silently ✗

You MUST ensure every class toggled by JS has its CSS defined in the flat CSS block or in a ghost block.

---

## Three Allowed Patterns

Choose **one pattern per animation**. Never mix them on the same properties. Always start from Pattern A and only move to B or C if needed.

### Pattern A — Pure CSS (No JS)

Best for: hover effects, focus states, continuous loops, simple transitions between states that CSS can detect natively.

**Use these tools — they all work natively in Webflow, no JS needed:**

```css
/* Hover transition */
.card {
  transform: translateY(0);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}
```

```html
<!-- Continuous animation via ghost block @keyframes -->
<div class="w-embed"><style>
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
.status_dot { animation: pulse 2s ease-in-out infinite; }
</style></div>
```

**Checklist for Pattern A:**
1. No `<script>`, no JS block needed ✓
2. `:hover`, `:focus`, `:focus-visible` go in flat CSS ✓
3. `@keyframes` go in a ghost block (not flat CSS — Webflow cannot convert them) ✓

### Pattern B — CSS Transitions + JS Class Toggle

Best for: show/hide, toggles, state changes — anything triggered by a user action that CSS alone cannot detect (click, scroll threshold, timer).

**How it works:** CSS `transition` on the base class handles the animation. JS only toggles a combo class. All visual properties live in CSS — JS is just a switch.

**CSS block (flat CSS — gets converted to Webflow native styles):**
```css
/* Base class: VISIBLE state — must be editable in Webflow Designer */
.modal_overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  opacity: 1;
  visibility: visible;
  transition: opacity 0.3s ease, visibility 0.3s ease;
}

/* Combo class: HIDDEN state — toggled by JS */
.modal_overlay.is-hidden {
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
}
```

**JS block:**
```javascript
document.addEventListener('DOMContentLoaded', function () {
  // Set initial hidden state via JS (not CSS — Rule #5)
  document.querySelectorAll('.modal_overlay').forEach(function (el) {
    el.classList.add('is-hidden');
  });

  document.querySelector('.modal_trigger').addEventListener('click', function () {
    document.querySelector('.modal_overlay').classList.remove('is-hidden');
  });

  document.querySelector('.modal_close').addEventListener('click', function () {
    document.querySelector('.modal_overlay').classList.add('is-hidden');
  });
});
```

**Checklist for Pattern B:**
1. Base class in flat CSS with `transition` property ✓
2. Every combo class toggled by JS (`.is-hidden`, `.is-active`, `.is-open`, etc.) defined in flat CSS ✓
3. Initial hidden state set by JS on `DOMContentLoaded`, not in CSS ✓
4. Combo class selectors are flat: `.base.combo` — no descendants ✓

---

### Pattern C — GSAP (Direct Style Manipulation)

Best for: scroll-triggered animations, staggered reveals, complex timelines, physics-based motion, parallax.

**How it works:** GSAP animates properties by setting inline styles directly. No CSS classes are needed for the animated properties. CSS only defines the static layout.

**CSS block (flat CSS):**
```css
/* Only static layout — no animation properties, no hidden states */
.hero_heading {
  font-size: 3.5rem;
  font-weight: 700;
  line-height: 1.1;
}

.hero_text {
  font-size: 1.125rem;
  color: #555;
  max-width: 540px;
}
```

**JS block:**
```javascript
document.addEventListener('DOMContentLoaded', function () {
  // GSAP sets initial state AND animates — no CSS classes involved
  gsap.set('.hero_heading', { opacity: 0, y: 40 });
  gsap.set('.hero_text', { opacity: 0, y: 30 });

  var tl = gsap.timeline({ defaults: { ease: 'power2.out' } });
  tl.to('.hero_heading', { opacity: 1, y: 0, duration: 0.8 });
  tl.to('.hero_text', { opacity: 1, y: 0, duration: 0.6 }, '-=0.4');
});
```

**GSAP requires the external script.** Add it to the page's external scripts:
```
https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js
```

For scroll-triggered animations, also add ScrollTrigger:
```
https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js
```

**Checklist for Pattern C:**
1. GSAP CDN added to external scripts ✓
2. `gsap.set()` handles initial hidden state (not CSS) ✓
3. No `classList.add/toggle` for animated properties — GSAP handles everything ✓
4. CSS only defines layout, typography, colors — not animation states ✓

---

## Rendering Constraints

These rules apply to **all** animations regardless of pattern. They ensure reliable rendering inside the Convertex iframe preview and in Webflow.

### Event listeners for position

Listeners that track position (`mousemove`, `scroll`, `resize`) must always bind to `window`, never `document`:

```javascript
// CORRECT
window.addEventListener("mousemove", function (e) { ... });
window.addEventListener("scroll", function () { ... });

// WRONG — unreliable in iframe context
document.addEventListener("mousemove", function (e) { ... });
```

### Animate positions with GSAP transforms

Always use GSAP transform properties (`x`, `y`, `scale`, `rotation`) instead of CSS positional properties (`left`, `top`, `width`, `height`). Transforms are GPU-accelerated, more performant, and more reliable across rendering contexts:

```javascript
// CORRECT — GPU-accelerated, reliable
gsap.to(el, { x: 100, y: 50, scale: 1.2 });

// WRONG — triggers layout reflow, unreliable in iframe
gsap.to(el, { left: 100, top: 50, width: "120%" });
```

### Always `gsap.set()` before fade-in

Before any fade-in animation on a dynamically positioned element, always call `gsap.set()` to initialize the position first. This prevents a visible flash at coordinates `0,0`:

```javascript
// CORRECT — position set before fade-in, no flash
gsap.set(el, { x: targetX, y: targetY, opacity: 0 });
gsap.to(el, { opacity: 1, duration: 0.5 });

// WRONG — element briefly appears at 0,0 before moving
gsap.to(el, { x: targetX, y: targetY, opacity: 1 });
```

### No `gsap.quickTo` — use `gsap.to` in listeners

`gsap.quickTo` can behave unpredictably inside iframes. Use a standard `gsap.to` call inside event listeners instead — it is simpler and more reliable:

```javascript
// CORRECT
window.addEventListener("mousemove", function (e) {
  gsap.to(".cursor", { x: e.clientX, y: e.clientY, duration: 0.3 });
});

// AVOID — unreliable in iframe
var moveCursor = gsap.quickTo(".cursor", "x", { duration: 0.3 });
```

### No manual `requestAnimationFrame`

Never use `requestAnimationFrame` manually. GSAP manages the render loop internally. Adding manual RAF calls creates conflicts and redundant frame processing.

---

## Forbidden Patterns

### Never: class toggle without CSS

```javascript
// ✗ BROKEN — where is the CSS for .is-visible?
el.classList.add('is-visible');
```

If you write `classList.add('is-visible')`, you MUST have `.element.is-visible { ... }` in the flat CSS block.

### Never: mix GSAP and class-based animation on the same element

```javascript
// ✗ BROKEN — GSAP sets inline opacity, but .is-hidden also sets opacity via CSS
// They will conflict unpredictably
gsap.to(el, { opacity: 1 });
el.classList.remove('is-hidden'); // .is-hidden { opacity: 0 }
```

Pick one approach per element. If GSAP controls `opacity`, don't also toggle a class that sets `opacity`.

### Never: direct `el.style` manipulation for animations

```javascript
// ✗ FRAGILE — no easing, no timeline control, hard to maintain
el.style.opacity = '1';
el.style.transform = 'translateY(0)';
```

Use GSAP instead — it handles easing, timelines, and cleanup properly.

### Never: `@keyframes` in flat CSS

```css
/* ✗ Webflow cannot convert @keyframes to native styles */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
```

`@keyframes` MUST go in a ghost block:
```html
<div class="w-embed"><style>
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
.hero_heading { animation: fadeIn 0.8s ease forwards; }
</style></div>
```

---

## Decision Guide

Always start from the top. Move down **only** if the level above cannot achieve the effect.

| Animation type | Pattern | Why this level |
|---|---|---|
| Hover effects | **A — Pure CSS** `:hover` + `transition` | No JS needed, natively supported |
| Focus / active states | **A — Pure CSS** `:focus-visible` + `transition` | No JS needed |
| Continuous loop (pulse, spin, float) | **A — Pure CSS** `@keyframes` in ghost block | No JS needed |
| Show / hide (modal, menu, accordion) | **B — CSS transition + JS toggle** | Needs click trigger (CSS can't detect) |
| Tab / carousel active state | **B — CSS transition + JS toggle** | Needs click trigger |
| Scroll reveal (fade-in on scroll) | **C — GSAP + ScrollTrigger** | Needs scroll position detection |
| Staggered reveal (items appear one by one) | **C — GSAP timeline** | Needs timeline sequencing |
| Parallax | **C — GSAP + ScrollTrigger** | Needs scroll-linked values |
| Complex multi-step animation | **C — GSAP timeline** | Needs sequencing + easing control |

---

## Self-Check Before Delivering

Before delivering code with JS animations, verify:

1. **Every `classList.add` / `classList.toggle` call** → does the combo class CSS exist in the flat CSS block?
2. **Every GSAP animation** → is the GSAP CDN included in external scripts? Is ScrollTrigger included if using scroll triggers?
3. **No `display: none`, `opacity: 0`, `visibility: hidden` in base CSS** → initial hidden states are set by JS on `DOMContentLoaded`?
4. **No `@keyframes` in flat CSS** → they are in a ghost block?
5. **No mixed approaches** → each element uses either Pattern A or Pattern B, not both?
6. **JS strings in double quotes?** → `"..."` only, never `'...'`
7. **Comments ASCII-only?** → no accents, no typographic apostrophes, no em-dashes
8. **Position listeners on `window`?** → `mousemove`, `scroll`, `resize` bound to `window`, not `document`
9. **Positions animated via transforms?** → `x`/`y`/`scale`/`rotation`, never `left`/`top`/`width`/`height`
10. **`gsap.set()` before every fade-in of a positioned element?** → prevents flash at 0,0
