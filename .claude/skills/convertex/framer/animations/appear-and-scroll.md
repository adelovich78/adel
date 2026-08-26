# Appear & Scroll animations

Two related effect families:

- **`data-framer-enter`**, mount appear (fires once when the element first renders, regardless of scroll).
- **`data-framer-scroll-appear`**, scroll appear (fires when the element scrolls into the viewport, with optional replay).

Both use the same preset table and stagger system. They can stack on the same element, that replicates the FitCoach native pattern (every animated descendant has both).

---

## Mount appear: `data-framer-enter`

```html
<div data-framer-enter="fade-up">
  Hero headline
</div>
```

That's the minimum. The element fades in while moving up 50px on mount.

### Presets

| Name | Behavior |
|---|---|
| `fade` | Opacity 0 → 1, no movement |
| `fade-up` | Opacity 0 + Y +50 → final (starts below) |
| `fade-down` | Opacity 0 + Y -50 (starts above) |
| `fade-left` | Opacity 0 + X +50 (starts right of final) |
| `fade-right` | Opacity 0 + X -50 (starts left of final) |
| `slide-up` | Y +50 → final, no fade |
| `slide-down` | Y -50 |
| `slide-left` | X +50 |
| `slide-right` | X -50 |
| `scale` / `scale-up` / `zoom` | Scale 0.5 + Opacity 0 |
| `rotate` | Rotate -10° + Opacity 0 |
| `none` | No preset, wire all axes manually via `data-enter-*` |

Direction follows AOS: `fade-up` means "the element appears moving upward" → starts BELOW its final position.

### Distance override

```html
<div data-framer-enter="fade-up" data-enter-distance="80">…</div>
```

Rescales the preset's directional axis (only Y for fade-up, X for fade-left, etc.).

### Per-axis overrides

```html
<div data-framer-enter="none"
     data-enter-x="-30" data-enter-y="20" data-enter-rotate="5" data-enter-opacity="0">
  Custom appear
</div>
```

Available: `data-enter-x`, `data-enter-y`, `data-enter-scale`, `data-enter-opacity`, `data-enter-rotate`. Per-axis values win over both preset and distance.

### Timing

```html
<div data-framer-enter="fade-up"
     data-enter-duration="600ms"
     data-enter-delay="200ms"
     data-enter-easing="cubic-bezier(0.5, 0, 0.5, 1)"
     data-enter-type="tween">
  …
</div>
```

| Attribute | Default | Notes |
|---|---|---|
| `data-enter-duration` | `400ms` | accepts ms, s, or bare number (>10 = ms) |
| `data-enter-delay` | `0` | same units |
| `data-enter-easing` | `[0.44, 0, 0.56, 1]` (Framer default ease) | keyword, `cubic-bezier(...)`, or `spring`/`spring(args)` |
| `data-enter-type` | `tween` | `tween` or `spring` (also auto-detected from spring easing) |

### Spring easing

```html
<div data-framer-enter="fade-up"
     data-enter-easing="spring(0.3, 600, 40)"   <!-- bounce, stiffness, damping -->
     data-enter-duration="700ms">…</div>
```

Sets `type: spring` automatically. Args are optional: `spring`, `spring(bounce)`, `spring(bounce, stiffness)`, or full triple.

---

## Stagger: parent-driven

Apply `data-framer-stagger` on a parent to cascade enter delays across animated children:

```html
<div class="hero_inner"
     data-framer-stagger="120ms"
     data-framer-enter="fade-up"
     data-enter-delay="200ms">
  <span class="hero_eyebrow" data-framer-enter="fade-up">v2.0</span>
  <h1 class="hero_heading" data-framer-enter="fade-up">Build websites that ship</h1>
  <p class="hero_lead" data-framer-enter="fade-up">From design to production…</p>
  <div class="hero_buttons">
    <a class="button is-primary" data-framer-enter="fade-up" href="/">Start</a>
    <a class="button" data-framer-enter="fade-up" href="/docs">Docs</a>
  </div>
</div>
```

Formula: `child.delay = parent.delay + (animatedIndex + 1) * stagger`.

With `parent.delay = 200ms`, `stagger = 120ms`:
- eyebrow → 320ms
- heading → 440ms
- lead → 560ms
- button group div → 680ms (counts even though it's a wrapper if it has the marker; if it doesn't, it's skipped)

Children **without** an animation marker are skipped from the index counter, they don't increment the cascade.

**Stagger is enter-only.** Scroll appear stagger emerges naturally from elements scrolling into view at different times.

---

## Scroll appear: `data-framer-scroll-appear`

```html
<div class="feature_card" data-framer-scroll-appear="fade-up">
  …
</div>
```

Same preset table as `data-framer-enter`. Defaults differ:

| Attribute | Default |
|---|---|
| `data-scroll-trigger` | `onInView` |
| `data-scroll-threshold` | `0` (any pixel visible) |
| `data-scroll-direction` | `down` |
| `data-scroll-once` | `true` (animates once, doesn't replay) |
| `data-scroll-easing` | spring (different from enter, which defaults to tween) |

### Threshold

```html
<div data-framer-scroll-appear="fade-up" data-scroll-threshold="0.3">…</div>
```

Fires when 30% of the element is visible. Range 0 to 1.

### Replay on every entry

```html
<div data-framer-scroll-appear="fade-up" data-scroll-once="false">
  Replays the animation each time the element re-enters the viewport.
</div>
```

### Triggers

| Trigger | When it fires |
|---|---|
| `onInView` (default) | Element enters the viewport |
| `onMount` | Element first renders (same as enter, but with scroll-effect's animateOnce/threshold/direction options) |
| `onScrollDirection` | Oscillates between final and initial as the user scrolls down vs up. **⚠ implemented but Framer's preview throws "Unexpected value", avoid for now until validated.** |
| `onScrollTarget` | Fires when a **different** section enters the viewport, see [Scroll Target](#scroll-target) below |

### Scroll Target: cross-element trigger

Fire an animation on element A when element B enters the viewport.

```html
<!-- Target side: declare the section that, when in view, triggers others -->
<section id="features-section" data-framer-scroll-target="true" class="features">
  …
</section>

<!-- Source side: this card animates when #features-section enters viewport -->
<div class="hero_card"
     data-framer-scroll-appear="fade-up"
     data-scroll-trigger="onScrollTarget"
     data-scroll-target="#features-section">
  Card animates when the features section comes in
</div>
```

Mechanism:
- Target section needs `id="..."` AND `data-framer-scroll-target="true"`.
- Source element needs `data-scroll-trigger="onScrollTarget"` AND `data-scroll-target="#section-id"`.
- The converter resolves the target id to the Framer node id in a post-pass after the full tree is built.
- If the target is missing, the effect silently disables on paste and a console warning is emitted.

Use this for choreographed sections (scroll past one section to ignite the next, etc.).

---

## Per-axis and timing: same as enter

All `data-enter-*` attributes have a `data-scroll-*` counterpart:
- `data-scroll-distance`, `data-scroll-x`, `data-scroll-y`, `data-scroll-scale`, `data-scroll-opacity`, `data-scroll-rotate`
- `data-scroll-duration`, `data-scroll-delay`, `data-scroll-easing`, `data-scroll-type`

Same parsing rules.

---

## Stacking enter + scroll-appear on the same element

```html
<div class="hero_card"
     data-framer-enter="fade-up"
     data-enter-duration="600ms"
     data-framer-scroll-appear="scale"
     data-scroll-once="false">
  Animates on mount AND every time it scrolls back into view.
</div>
```

Both effects apply independently. Enter runs at mount, scroll runs on viewport entry.

---

## Anchor links via `id`

If your HTML has `<section id="pricing">…</section>`, the converter emits `elementId: "pricing"` on the FrameNode automatically. Anchor links like `<a href="#pricing">` then jump correctly in Framer.

You don't need `data-framer-scroll-target` for anchor jumps, that's only for cross-element animation triggers.

---

## Don'ts

- **Don't put enter / scroll-appear on a static text node**. Animation effects work on FrameNodes (and their RichText descendants when the wrapper has the marker). For a bare paragraph that becomes a RichTextNode directly, the marker still applies (the converter wires it up), but only on FrameNode wrappers does the timing feel right at scale.
- **Don't combine `data-framer-enter="..."` with `data-framer-text-effect="..."` on the same element**, they run together, but it's usually visually noisy. Pick one.
- **Don't use `onScrollDirection` for now**, known runtime bug. Use `onInView` with `data-scroll-once="false"` for a similar replay-on-scroll feel.
- **Don't stagger every level of nesting**. One stagger at the hero level is enough. Three nested staggers feel mechanical.
- **Don't set very long durations** (>1s) on appear effects. Keeps the page feeling responsive.
