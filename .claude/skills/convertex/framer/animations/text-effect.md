# Text Effect: per-token text animation

Framer can tokenize a text string (per word, per character, per line) and animate each token independently. Different from `data-framer-enter`, which animates the whole element as one unit.

Use for **one big hero headline**, occasionally a section title. Never on body copy.

---

## Marker

```html
<h1 class="hero_heading"
    data-framer-text-effect="appear"
    data-text-tokenization="word"
    data-text-stagger="75ms"
    data-text-delay="200ms">
  Build websites that ship fast
</h1>
```

Each word fades in sequentially, 75ms apart, after a 200ms initial delay.

---

## Presets

| Name | Behavior |
|---|---|
| `appear` (default) | Opacity 0 → 1 (no movement) |
| `fade` | Same as appear |
| `fade-up` | Opacity 0 + Y +10 (small distance, tokens are tiny) |
| `fade-down` | Opacity 0 + Y -10 |
| `fade-left` | Opacity 0 + X +10 |
| `fade-right` | Opacity 0 + X -10 |
| `slide-up` | Y +10, no fade |
| `slide-down` | Y -10 |
| `blur` | Opacity 0 + Blur 8px |
| `fade-blur` | Opacity 0 + Y +10 + Blur 8px |
| `scale` / `zoom` | Scale 0.5 + Opacity 0 |
| `rotate` | Rotate -10° + Opacity 0 |
| `none` | Manual via `data-text-*` |

The default Y distance is **10px** (not 50 like enter) because each token is small and 50px would feel excessive.

---

## Tokenization

```html
data-text-tokenization="word"          <!-- default -->
data-text-tokenization="character"     <!-- per letter -->
data-text-tokenization="line"          <!-- per visual line -->
```

The converter accepts `letter`, `char`, `characters` as aliases for `character`. **Always emits `"character"` as the canonical value**, Framer's properties panel crashes on other values like `"letter"`.

---

## Stagger and delay (inverted semantics)

Text effect has different semantics than other effects:

- `data-text-stagger` → **inter-token delay** (how long Framer waits between successive tokens). Lives in `textEffectTransition.delay`.
- `data-text-delay` → **absolute delay** before the entire sequence starts. Lives in `textEffectDelay` at top level.

```html
<h1 data-framer-text-effect="fade-up"
    data-text-tokenization="word"
    data-text-delay="300ms"            <!-- wait 300ms, then start -->
    data-text-stagger="60ms">          <!-- 60ms between each word -->
  This whole thing
</h1>
```

For a 5-word phrase: word 0 starts at 300ms, word 1 at 360ms, word 2 at 420ms, etc.

---

## Trigger

```html
data-text-trigger="onMount"            <!-- default, fires at page load -->
data-text-trigger="onInView"           <!-- fires when element scrolls into view -->
data-text-trigger="onScrollDirection"  <!-- ⚠ same caveat as scroll appear -->
```

For a hero where the headline is above the fold, `onMount` is fine (the user sees the animation on load). For a section title further down the page, use `onInView` so the animation plays when the user reaches it.

```html
<h2 class="section_title"
    data-framer-text-effect="fade-up"
    data-text-trigger="onInView"
    data-text-threshold="0.3"
    data-text-stagger="60ms">
  Why teams pick us
</h2>
```

---

## Replay

```html
data-text-replay="true"                <!-- replays each time element enters viewport -->
data-text-replay="false"               <!-- default, animates once -->
```

---

## Per-axis overrides

```html
<h1 data-framer-text-effect="none"
    data-text-x="20"
    data-text-y="-5"
    data-text-rotate="2"
    data-text-blur="6"
    data-text-opacity="0">
  Each word starts offset and blurred
</h1>
```

Available: `data-text-x`, `data-text-y`, `data-text-scale`, `data-text-opacity`, `data-text-rotate`, `data-text-blur`. Per-axis values win over preset.

---

## Timing

```html
<h1 data-framer-text-effect="fade-up"
    data-text-duration="500ms"
    data-text-easing="spring(0)"        <!-- damping 40, no bounce, clean settle -->
    data-text-stagger="50ms">
  Each token settles cleanly
</h1>
```

The default text-effect transition uses spring with `damping: 40, bounce: 0` (cleaner settle than other effects' `damping: 30, bounce: 0.2`). Override via `data-text-easing` if needed.

| Attribute | Default |
|---|---|
| `data-text-duration` | `400ms` |
| `data-text-easing` | spring (clean settle) |
| `data-text-type` | `spring` (or `tween`) |

---

## Recommended cadences

### Hero headline (above the fold)

```html
<h1 class="hero_heading"
    data-framer-text-effect="fade-up"
    data-text-tokenization="word"
    data-text-delay="200ms"
    data-text-stagger="60ms"
    data-text-duration="500ms">
  Build websites that ship in days
</h1>
```

Effect: each word fades up sequentially right after page load. Punchy without being slow.

### Section title (mid-page)

```html
<h2 class="section_title"
    data-framer-text-effect="fade-up"
    data-text-trigger="onInView"
    data-text-threshold="0.3"
    data-text-tokenization="word"
    data-text-stagger="50ms">
  Why teams choose us
</h2>
```

Effect: triggers when the section is visibly in view, gives weight to the title.

### Display headline with letter-by-letter

```html
<h1 class="display"
    data-framer-text-effect="fade-blur"
    data-text-tokenization="character"
    data-text-stagger="20ms"
    data-text-blur="6">
  Hello world
</h1>
```

Effect: each letter unblurs. Use very sparingly, it's slow and distinctive. Reserve for landing-page heroes that genuinely need a "moment".

---

## Don'ts

- **Don't put text-effect on body paragraphs**. Slows the read. Hero / section titles only.
- **Don't use `tokenization: "letter"`**, Framer panel crashes. Use `"character"` (which is what `letter` aliases to anyway in our mapper).
- **Don't combine text-effect with stagger via `data-framer-stagger`**, different system; the parent stagger doesn't propagate to text effect children.
- **Don't set `data-text-stagger` higher than `200ms`**, feels like watching paint dry.
- **Don't combine `data-framer-text-effect` with `data-framer-enter` on the same element**. Both apply, but you get two animations fighting on the same text. Pick one.
