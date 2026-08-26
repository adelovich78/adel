# Visual styling: colors, borders, radius, shadows, blur, transform

Everything that makes a layer look like itself: backgrounds, edges, depth, motion at rest.

---

## Colors

Always use raw values. **Never** `var(--brand)`.

```css
color: #ffffff;
color: #fff;                          /* short hex */
color: rgb(15, 23, 42);
color: rgba(255, 255, 255, 0.8);      /* with alpha */
color: hsl(220, 90%, 55%);
color: transparent;
```

The converter normalizes all forms to `rgba(r, g, b, a)` for the Framer JSON output.

### Background color

```css
background-color: #0a0a0a;
background-color: rgba(255, 255, 255, 0.05);   /* glass / overlay */
```

Translates to `fillType: "color"` + `fillColor: "rgba(...)"`.

### Linear gradient

```css
background-image: linear-gradient(135deg, #6366f1 0%, #ec4899 100%);
background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.6) 100%);
```

Maps to `fillType: "linear-gradient"` + `fillLinearGradient` object. Framer requires all 3 gradient types in the JSON, so the converter generates a placeholder radial and conic too.

### Radial gradient

```css
background-image: radial-gradient(circle at center, #6366f1 0%, transparent 70%);
```

Maps to `fillType: "radial-gradient"`. Position keywords (`circle at center`, `circle at top right`) are passed through.

### Background image

```css
background-image: url('https://example.com/hero.jpg');
background-size: cover;               /* default, maps to fillImageResize: "fill" */
background-size: contain;             /* → fillImageResize: "fit" */
background-position: center;
background-repeat: no-repeat;
```

Important: the converter passes through external URLs. Framer fetches them on paste. If the URL is unreachable (CORS, hotlink protection), the image won't render.

For Convertex-managed assets, use the asset CDN URL the user provides.

---

## Borders

```css
/* All sides */
border: 1px solid #1f2937;
border-color: #1f2937;
border-style: solid;
border-width: 1px;

/* Per side */
border-top: 1px solid #1f2937;
border-bottom: 2px solid rgba(255,255,255,0.1);
border-left-color: red;               /* per-side longhand */
```

The converter handles per-side and shorthand correctly. Per-side widths/colors map to Framer's `borderTop/Right/Bottom/Left` + `borderPerSide: true`.

`border-style` accepted: `solid`, `dashed`, `dotted`. `double`, `groove`, etc. are rendered as solid.

### When border is enabled

Framer FrameNodes have a `borderEnabled: false` default. The converter sets `borderEnabled: true` when it sees any border declaration. If you don't want a border, just don't write one.

---

## Border radius

```css
border-radius: 16px;                  /* all four corners */
border-radius: 16px 16px 0 0;         /* top-left top-right bottom-right bottom-left */
border-radius: 999px;                 /* pill */

/* Per corner */
border-top-left-radius: 24px;
border-top-right-radius: 24px;
border-bottom-left-radius: 0;
border-bottom-right-radius: 0;
```

All forms map to Framer's `radiusTopLeft/TopRight/BottomLeft/BottomRight`. The all-corners shorthand (`border-radius: 16px`) emits per-corner values.

`em` and `%` units are honored (`border-radius: 50%` for a circle).

---

## Box shadow

```css
/* Single */
box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);

/* Multiple */
box-shadow:
  0 1px 3px rgba(0, 0, 0, 0.1),
  0 12px 40px rgba(0, 0, 0, 0.08);

/* Inset */
box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
```

Each shadow becomes an entry in Framer's `boxShadows` array as a `type: "box"` object (the CSS-equivalent mode in Framer's panel, opposite of `realistic`). `inset: true` for inset shadows. Spread (4th value) supported.

For colored / brand shadows:

```css
box-shadow: 0 12px 32px rgba(99, 102, 241, 0.3);    /* indigo glow */
```

Multiple comma-separated shadows are parsed correctly (depth-aware comma splitting).

---

## Blur (filter and backdrop-filter)

```css
.glass {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(20px);        /* glass effect, frosts content behind */
}
.placeholder {
  filter: blur(8px);                  /* blurs the element itself */
}
```

Both map to Framer's `blur*` props:
- `filter: blur(N)` → `blurType: "layer"`, `blurRadius: N`
- `backdrop-filter: blur(N)` → `blurType: "background"`, `blurRadius: N`

`-webkit-backdrop-filter` is also recognized (Safari prefix). You can include both for browser preview compatibility, but in Framer the `-webkit-` form alone works.

Other filter functions (`brightness`, `saturate`, `contrast`) are **not** mapped, Framer doesn't expose those at the layer level.

---

## Opacity

```css
.disabled { opacity: 0.4; }
```

Maps to Framer's `opacity` (0 to 1). Affects the entire layer including children.

For text-only de-emphasis, prefer `color: rgba(...)` with alpha, keeps icons / borders inside the layer at full opacity.

---

## Transform: rotate at rest

Static rotation on a layer:

```css
.badge {
  transform: rotate(-3deg);            /* → rotation: -3 */
}
.notice {
  transform: rotate(0.05turn);         /* turns and rad supported */
}
```

Only `rotate()` is honored at rest. `translate()`, `scale()`, `skew()` at rest are dropped, use them in `:hover` / `:active` / animations instead.

---

## Mix blend mode

```css
.overlay { mix-blend-mode: overlay; }
.heading-blend { mix-blend-mode: difference; }
```

Maps to Framer's `blendingMode`. Supported values: `normal` (default, omitted), `overlay`, `difference`, `multiply`, `screen`, `lighten`, `darken`, `color-dodge`, `color-burn`, `soft-light`, `hard-light`, `exclusion`, `hue`, `saturation`, `color`, `luminosity`.

---

## Masks: prefer `mask-image` over overlay divs for fades

Fade-out edges (ticker masks, gallery vignettes, "into the dark" gradients) belong on the container as `mask-image`, **not** as extra absolutely-positioned `<div>` overlays. The mask makes the underlying content fully transparent at its edges, so it works on any background and adds zero Framer nodes.

### Horizontal ticker with fading edges

```css
.ticker {
  display: flex;
  overflow: hidden;
  mask-image: linear-gradient(90deg, transparent 0%, #000 15%, #000 85%, transparent 100%);
  -webkit-mask-image: linear-gradient(90deg, transparent 0%, #000 15%, #000 85%, transparent 100%);
}
```

### Vertical scroll fade (top + bottom)

```css
.scroll_zone {
  overflow-y: auto;
  mask-image: linear-gradient(180deg, transparent 0%, #000 10%, #000 90%, transparent 100%);
  -webkit-mask-image: linear-gradient(180deg, transparent 0%, #000 10%, #000 90%, transparent 100%);
}
```

Supported gradient types: `linear-gradient(...)`, `radial-gradient(...)`, `conic-gradient(...)`. Multiple comma-separated gradients also work (`mask-image: linear-gradient(...), radial-gradient(...);`) — each becomes its own entry in Framer's `masks` array.

Always write **both** `mask-image` and `-webkit-mask-image` (Safari still needs the prefixed form), the converter prefers the unprefixed but reads either.

**Don't** use a fake `<div>` gradient overlay (`position: absolute; background: linear-gradient(...)`) when a real mask works — that pattern hardcodes the background color, breaks on theme changes, and bloats the Framer tree with decoration nodes.

`mask-image: url(...)` (bitmap masks) is **not** supported yet — needs asset upload. Stick to gradients.

---

## Overflow

```css
.card { overflow: hidden; }            /* → overflow: "hidden", clips children */
.scroll_zone { overflow: auto; }       /* → overflow: "scroll", Framer scroll layer */
.section { overflow: visible; }        /* default, children can escape */
```

Notes:
- `overflow: clip` is mapped to `hidden` (Framer doesn't have a separate `clip`).
- For images / cards with rounded corners, set `overflow: hidden` so children respect the radius.
- Don't use `overflow: hidden` on a section just to "fix" some layout overflow, fix the root cause.

---

## z-index

```css
.modal { z-index: 100; }
.tooltip { z-index: 200; }
```

Integer values pass through to Framer's `zIndex`. `auto` is dropped.

---

## Pointer events / user select

```css
.decorative { pointer-events: none; }   /* not clickable, doesn't catch hover */
.code_block { user-select: text; }
.no_select { user-select: none; }
```

Both map to Framer string props.

---

## Visibility

```css
.hidden_layer { visibility: hidden; }   /* → visible: false (still takes space) */
.nav { display: none; }                 /* → visible: false (Framer's only hide knob) */
```

Both map to Framer's `visible: false`. Framer has **no `display: none` equivalent** — there is only the boolean `visible`, which is what `display: none` is rewritten to. Use this naturally inside `@media` blocks to hide layers per-breakpoint; the converter emits `breakpointOverrides[bp][nodeId].visible = false`:

```css
.nav { display: flex; }
@media (max-width: 810px) {
  .nav { display: none; }   /* → mobile breakpoint override: visible: false */
}
```

Note: in Framer, an invisible layer still occupies layout space inside its parent stack (just like CSS `visibility: hidden`). If you need true layout removal at a breakpoint, restructure rather than relying on visibility alone.

---

## Don'ts

- **Don't use `var(--anything)`**, inline the actual value.
- **Don't use `text-shadow`**, not reliably supported. Use a layered FrameNode behind the text for glow effects.
- **Don't use `clip-path`**, not mapped. (`mask-image` IS supported, see the Masks section above.)
- **Don't use CSS custom properties** for colors / tokens. The converter has no token system (Session 5 dropped color tokens after they confused users with cryptic auto-generated names).
- **Don't write `outline`**, Framer has no outline; use `border` if you need an extra edge.
