# Breakpoints: 1200, 810, 390

Framer recognizes specific viewport widths as valid breakpoints. **Use only these:**

| Width | Media query | Direction |
|---|---|---|
| **1200** | _(base, no media query)_ | Desktop, author here first |
| **810** | `@media (max-width: 1199px)` | Tablet override |
| **390** | `@media (max-width: 810px)` | Mobile override |

Using non-standard widths (`991`, `767`, `768`, `1024`…) does not get dropped by the converter, but it does land in the wrong Framer variant. The converter buckets by Framer's tablet floor (810):

- `max-width: X` where **X < 810** → MOBILE only
- `max-width: X` where **810 ≤ X ≤ 1199** → TABLET (mobile inherits via cascade)
- `max-width: X` where **X ≥ 1200** → dropped (covers desktop, which is the base)

So `@media (max-width: 768px)` written by reflex (Tailwind `md`, Bootstrap) silently becomes a **mobile** override, not tablet — which is usually NOT what you meant. Always write `810` or `1199` explicitly.

The numbers `1200 / 810 / 390` are baked into the converter and into Framer's internal breakpoint registry.

---

## Cascade rules: what the converter does

For each breakpoint, the converter merges:
1. Desktop (base) CSS
2. All `@media` rules whose width is **larger or equal** to the current breakpoint

Then runs the layout/sizing/effects mappers on the merged result.

In practice:

| Target | Merged CSS |
|---|---|
| Desktop (1200) | base only |
| Tablet (810) | base + `@media (max-width: 1199px)` |
| Mobile (390) | base + `@media (max-width: 1199px)` + `@media (max-width: 810px)` |

This means **mobile inherits both the desktop base AND the tablet rules**. So if tablet says `flex-direction: column`, mobile gets it for free without you re-declaring.

---

## What to re-declare at a breakpoint

**Re-declare** properties that change. Don't restate properties that stayed the same, they cascade automatically.

```css
.hero {
  padding: 120px 64px;
  display: flex;
  flex-direction: row;
  gap: 64px;
}
.hero_heading {
  font-size: 4.5rem;
  line-height: 1.05;
}

@media (max-width: 1199px) {
  .hero { padding: 80px 32px; gap: 48px; }
  .hero_heading { font-size: 3rem; }
}

@media (max-width: 810px) {
  .hero { flex-direction: column; gap: 32px; padding: 56px 16px; }
  .hero_heading { font-size: 2.25rem; line-height: 1.1; }
}
```

The mobile entry doesn't need to re-state `display: flex` (cascaded from base).

---

## Layout changes that need explicit triggers

### Stack direction flip

If you want a horizontal hero to stack vertically on mobile, **don't just write `flex-direction: column`**, that alone may produce empty layout overrides if the converter can't see `display: flex` in the @media block. Instead, declare it once at base and override the direction:

```css
.hero_split {
  display: flex;                   /* base: enables flex */
  flex-direction: row;
  gap: 64px;
}

@media (max-width: 810px) {
  .hero_split {
    flex-direction: column;        /* converter cascades base + override → mapper sees both */
    gap: 32px;
  }
}
```

This works because the cascade in Session 4 specifically merges desktop CSS into each @media before mapping (otherwise `mapLayout` would see no `display: flex` in the @media-only block and emit nothing).

### Children width re-flowing

When you change `flex-direction` from row to column at mobile, the converter automatically flips children sizing rules:
- Vertical stack: children fill width by default (text wraps responsively)
- Horizontal stack: children hug width unless explicitly set to fill

You don't usually need per-child width overrides at the breakpoint.

---

## Typography responsive

Re-declare `font-size`, `line-height`, and `letter-spacing` whenever they change. Other typography properties (family, weight, color) usually stay the same, leave them out of the @media block.

```css
.hero_heading {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  font-size: 4.5rem;
  line-height: 1.05;
  letter-spacing: -0.02em;
  color: #fff;
}

@media (max-width: 1199px) {
  .hero_heading {
    font-size: 3.25rem;
    line-height: 1.1;
  }
}

@media (max-width: 810px) {
  .hero_heading {
    font-size: 2.25rem;
    letter-spacing: -0.01em;
  }
}
```

The converter re-emits the full `--font-selector` (font-face base64) in every breakpoint variant of the html, so font-rendering stays correct at every viewport.

---

## Padding partial overrides

Per-side longhand at @media works correctly, the converter only emits the sides you explicitly changed:

```css
.section { padding: 96px 32px; }

@media (max-width: 810px) {
  .section { padding-top: 56px; padding-bottom: 56px; }   /* horizontal stays 32 */
}
```

Same applies to `border-radius` per corner, `border-width` per side, etc.

---

## Don'ts

- **Don't use Webflow's breakpoint widths** (`991px`, `767px`, `479px`). Framer ignores them.
- **Don't use mobile-first** (`min-width` queries cascading upward). Framer's converter cascades downward only.
- **Don't change `font-family` per breakpoint.** It works but creates inconsistency in the layer tree. Pick one family and scale via `font-size`.
- **Don't combine `min-width` and `max-width` in a single query**, only `max-width` is parsed reliably.
