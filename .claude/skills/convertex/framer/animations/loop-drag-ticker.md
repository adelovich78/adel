# Loop, Drag, Ticker, Exit

Continuous and interaction-based effects.

---

## Loop: `data-framer-loop`

Continuous animation that repeats forever. Common uses: spinning logo, pulsing badge, oscillating arrow.

```html
<div class="logo" data-framer-loop="rotate" data-loop-duration="20s">
  <img src="/logo.svg" alt="Logo" />
</div>
```

### Presets

| Name | Behavior |
|---|---|
| `rotate` / `spin` | Rotates 360° per cycle |
| `pulse` | Scale up to 1.05 |
| `bounce` | Y -10 |
| `shake` | X +5 |
| `none` | Manual via `data-loop-*` |

### Customization

```html
<div class="badge"
     data-framer-loop="pulse"
     data-loop-scale="1.1"
     data-loop-duration="2s"
     data-loop-repeat-type="mirror"
     data-loop-easing="ease-in-out">
  Live
</div>
```

| Attribute | Default | Notes |
|---|---|---|
| `data-loop-duration` | `1s` | one cycle |
| `data-loop-easing` | `linear` | rotate / shake → linear; pulse / bounce → `ease-in-out` looks better |
| `data-loop-type` | `tween` | or `spring` |
| `data-loop-repeat-type` | `loop` | `loop` (restart from start) / `reverse` (play backwards) / `mirror` (alternate forward/reverse) |
| `data-loop-repeat-delay` | `0` | pause between cycles |
| `data-loop-pause-offscreen` | `true` | pauses when not visible (saves CPU) |

### Per-axis overrides

```html
<div data-framer-loop="none"
     data-loop-rotate="180"
     data-loop-y="-12"
     data-loop-duration="3s">
  Custom orbit
</div>
```

Available: `data-loop-x`, `data-loop-y`, `data-loop-scale`, `data-loop-opacity`, `data-loop-rotate`, `data-loop-skew-x`, `data-loop-skew-y`.

### When to use loop

- Spinning logos in a "trusted by" strip (subtle, slow).
- A pulsing "Live" or "New" badge.
- A bouncing scroll-down arrow at the hero.

**Don't** loop everything. A page where 5 things move forever feels broken.

---

## Drag: `data-framer-drag`

Make a layer draggable by the user.

```html
<div class="card"
     data-framer-drag="true"
     data-drag-freeform="true"
     data-drag-momentum="true"
     data-drag-snap-back="false">
  Drag me anywhere
</div>
```

| Attribute | Default | Behavior |
|---|---|---|
| `data-framer-drag` | required `"true"` | Enables drag |
| `data-drag-freeform` | `false` | `false` = constrained to bounds; `true` = free movement |
| `data-drag-momentum` | `true` | Carry inertia after release |
| `data-drag-snap-back` | `true` | Return to origin on release |
| `data-drag-constraint` | none | `#bounds-id`, restricts drag to the bounds of that element |

### Constrained drag with bounds

```html
<section id="drag-zone" class="drag_zone">
  <div class="card"
       data-framer-drag="true"
       data-drag-constraint="#drag-zone"
       data-drag-freeform="true">
    Stays inside the zone
  </div>
</section>
```

The constraint reference resolves cross-element via the same id-mapping mechanism as `onScrollTarget`. If `#drag-zone` doesn't exist, drag is unconstrained on paste (warning emitted).

### Drag transitions

Drag uses a third transition type beyond `spring` and `tween`: **`inertia`** (Framer's physics engine for drag-release motion). This is set automatically; you don't need to declare it.

### When to use drag

- Image gallery cards the user can shuffle.
- Before-after slider handles.
- A draggable card stack reveal.

**Don't** add drag to layout elements (sections, headings), confusing UX. Reserve for clearly affordant cards / handles.

---

## Ticker: `data-framer-ticker`

Marquee / infinite-scroll effect. Apply to a flex stack container; Framer clones the children at render time so the scroll loops seamlessly. **The axis is inferred from the parent's `flex-direction`**: `row` → horizontal ticker, `column` → vertical ticker. No explicit axis attribute exists.

### Horizontal (most common)

```html
<div class="logos_strip"
     data-framer-ticker="true"
     data-ticker-velocity="60">
  <img class="logo" src="/google.svg" alt="Google" />
  <img class="logo" src="/apple.svg" alt="Apple" />
  <img class="logo" src="/openai.svg" alt="OpenAI" />
  <img class="logo" src="/figma.svg" alt="Figma" />
</div>
```

```css
.logos_strip {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 64px;
  overflow: hidden;
  padding: 32px 0;
}
.logo {
  height: 32px;
  width: auto;
}
```

### Vertical

Same marker, parent is `flex-direction: column` with a fixed/constrained height and `overflow: hidden`. Children should hug their content (e.g. `height: auto`).

```html
<div class="testimonials_column"
     data-framer-ticker="true"
     data-ticker-velocity="40">
  <div class="testimonial">"Game changer." — Alice</div>
  <div class="testimonial">"Saved me hours." — Bob</div>
  <div class="testimonial">"Slick UX." — Carla</div>
  <div class="testimonial">"My new daily driver." — Dan</div>
</div>
```

```css
.testimonials_column {
  display: flex;
  flex-direction: column;
  gap: 24px;
  height: 480px;        /* container needs a bounded main axis */
  overflow: hidden;
  padding: 16px 0;
}
.testimonial {
  padding: 20px 24px;
  background: #fff;
  border-radius: 12px;
  color: #111;
  font-size: 15px;
}
```

| Attribute | Default | Behavior |
|---|---|---|
| `data-framer-ticker` | required `"true"` | Enables ticker |
| `data-ticker-velocity` | `100` | px/sec (60 to 120 looks natural; vertical text usually 30 to 60) |
| `data-ticker-direction` | `default` | `default` = forward along the stack axis, `reverse` = backward |
| `data-ticker-hover-modifier` | `100` | speed % on hover (100 = unchanged, 0 = pause, 50 = half-speed) |
| `data-ticker-draggable` | `false` | lets user drag-scroll the ticker |

### Recommended pattern

- Pick the axis via the parent's `flex-direction` (`row` or `column`). Always set `overflow: hidden` on the container and a comfortable `gap` between children.
- Constrain the main axis: horizontal tickers get a `width` from their parent (often full-width section); vertical tickers need an explicit `height` (otherwise they hug their content and there's nothing to scroll).
- Children should hug along the main axis (use `<img>` natural width / `width: auto` for horizontal, `height: auto` for vertical).
- For text tickers, wrap each phrase in a `<span>` or `<div>` with `display: inline-flex` so they hug.

### Pause on hover

```html
<div data-framer-ticker="true"
     data-ticker-velocity="80"
     data-ticker-hover-modifier="0">
  Pauses when the cursor enters
</div>
```

Useful for trust strips where the user might want to read the names.

---

## Exit: `data-framer-exit`

Plays the entry animation in reverse when the element unmounts (e.g. switching pages, or a parent's appear effect retreating).

```html
<div data-framer-enter="fade-up" data-framer-exit="true">
  Fades up on enter, fades down on exit.
</div>
```

Native pastes only emit `exitEffectEnabled: true`, no per-axis params. Framer reuses the enterEffect parameters for the reverse playback.

Use for in-page state transitions in Framer (modals, accordions). On a static landing page, exit rarely fires.

---

## Combining effects on the same element

Generally each layer carries one effect family at a time. But some combinations are valid:

- **Hover + Press**, independent gesture states. Both apply.
- **Enter + Scroll-Appear**, both apply, enter at mount, scroll on viewport entry.
- **Loop + Hover**, fights for transform; pick one or accept the visual conflict.
- **Drag + Hover**, independent. Both work.
- **Ticker + child Hover**, ticker on the parent, hover on each child. Works.

**Don't** stack:
- Loop + scroll-appear (both fight transform).
- Text-effect + enter (visual noise on text).
- Ticker on a layer with internal flex/grid layout that needs to stay still.

---

## Don'ts

- **Don't loop more than 1 or 2 elements per page**. The eye locks onto motion.
- **Don't drag essential content** (text, sections). Reserve for clearly cards/handles.
- **Don't ticker dense content**, logos and short pill labels work, paragraphs don't.
- **Don't use `data-framer-loop="rotate"` with non-linear easing**, produces a noticeable pulse at each repeat boundary. Stick with `linear`.
