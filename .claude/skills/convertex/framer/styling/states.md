# States: `:hover` and `:active` (Press)

Hover and Press are first-class Framer effects. The converter reads `:hover` and `:active` rules from your CSS and emits them as `whileHover*` / `whileTap*` props on the FrameNode.

**No JavaScript needed. No class toggling. Just CSS.**

---

## Hover (`:hover`)

```css
.button {
  display: inline-flex;
  padding: 14px 24px;
  background: #ff5b3a;
  border-radius: 999px;
  color: #fff;
  transition: transform 0.3s spring;   /* sets the hover transition timing */
}

.button:hover {
  transform: translateY(-2px) scale(1.03);
  box-shadow: 0 12px 32px rgba(255, 91, 58, 0.3);
}
```

Maps to Framer:
- `whileHoverEnabled: true`
- `whileHoverY: -2`
- `whileHoverScale: 1.03`
- `whileHoverBoxShadow: { color: "rgba(...)", x: 0, y: 12, blur: 32, spread: 0, type: "box", inset: false }`
- `whileHoverTransition: { type: "spring", duration: 0.3, bounce: 0.2, … }`

### Recognized properties in `:hover`

| CSS | Framer |
|---|---|
| `transform: translate(x, y)` / `translateX` / `translateY` / `translate3d(x, y, z)` | `whileHoverX`, `whileHoverY` |
| `transform: scale(N)` / `scaleX` / `scaleY` / `scale3d(x, y, z)` | `whileHoverScale` |
| `transform: rotate(deg)` / `rotateX` / `rotateY` / `rotate3d` | `whileHoverRotate`, `whileHoverRotateX`, `whileHoverRotateY` |
| `transform: skew(x, y)` / `skewX` / `skewY` | `whileHoverSkewX`, `whileHoverSkewY` |
| `opacity` | `whileHoverOpacity` |
| `box-shadow` (single) | `whileHoverBoxShadow` |

Other properties (color, background, border-color) at `:hover` are NOT mapped to a hover effect. Framer's hover gesture is transform-based by design. For color changes on hover, you'll need to redesign, typically a `:hover` with translate + a border-color change works visually.

### Combined transforms

```css
.card:hover {
  transform: translateY(-4px) scale(1.02) rotate(0.5deg);
}
```

All three components are read and mapped to the corresponding axes.

---

## Press (`:active`)

```css
.button:active {
  transform: scale(0.96);
}
```

Maps to `whileTap*` props (same axes as hover). Use Press for the "click-down" feel: scale slightly smaller, dim slightly, etc.

```css
.icon_button:active {
  transform: scale(0.94);
  opacity: 0.85;
}
```

Hover and Press can coexist on the same element, they're independent gestures.

---

## Transitions: timing and easing

The transition timing for hover/press lives in the **base state**'s `transition` property, NOT inside the `:hover` block:

```css
.button {
  transition: transform 0.3s spring;   /* read for both hover AND press */
  /* or */
  transition: transform 0.3s ease-out;
  /* or */
  transition: transform 0.4s cubic-bezier(0.5, 0, 0.5, 1);
}
```

### Easing keywords

| CSS | Maps to (4-tuple) |
|---|---|
| `linear` | `[0, 0, 1, 1]` |
| `ease` | `[0.25, 0.1, 0.25, 1]` |
| `ease-in` | `[0.42, 0, 1, 1]` |
| `ease-out` | `[0, 0, 0.58, 1]` |
| `ease-in-out` | `[0.42, 0, 0.58, 1]` |
| `cubic-bezier(a, b, c, d)` | `[a, b, c, d]` direct |
| _(no transition declared)_ | spring with defaults |
| `spring` (custom keyword) | spring physics, default params |
| `spring(bounce)` | spring with custom bounce (0 to 1) |
| `spring(bounce, stiffness, damping)` | spring with full custom physics |

### Spring keyword (Framer-specific)

The `spring` keyword is **non-standard CSS** but the Convertex Framer pipeline recognizes it and routes to Framer's spring physics:

```css
transition: transform 0.3s spring;
transition: transform 0.4s spring(0.5);          /* bouncier */
transition: transform 0.5s spring(0.3, 600, 40); /* full physics */
```

Browser preview won't animate a spring (browsers ignore the unknown keyword), but Framer renders it natively. This is the recommended approach for buttons and cards, feels much more polished than `ease-out`.

Default spring params: `bounce: 0.2, stiffness: 400, damping: 30`. Adjust for taste:
- `bounce` 0.0 = no overshoot (settles cleanly), 0.5 = noticeable bounce
- `stiffness` 200 = soft, 400 = standard, 800 = snappy
- `damping` 20 = lively, 30 = standard, 50 = heavy

---

## Single-axis transitions

CSS allows multiple comma-separated transitions, but Framer applies one timing per gesture state. The converter takes the **first** transition declaration:

```css
.button {
  /* converter uses the first one: 0.3s spring */
  transition: transform 0.3s spring, opacity 0.5s ease;
}
```

If you need different timing per axis, you'll get one timing applied to all changes. Pick the most important axis.

---

## Hover + Press together

```css
.cta {
  display: inline-flex;
  padding: 16px 28px;
  background: #6366f1;
  border-radius: 12px;
  color: #fff;
  font-weight: 600;
  transition: transform 0.3s spring;
}
.cta:hover {
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 12px 32px rgba(99, 102, 241, 0.4);
}
.cta:active {
  transform: translateY(0) scale(0.97);
}
```

Framer applies hover when the cursor enters, press when the user clicks. Press takes precedence over hover during the click. Releasing the click returns to hover (if cursor still over) or rest.

---

## Common patterns

### Card lift

```css
.card {
  transition: transform 0.4s spring;
}
.card:hover {
  transform: translateY(-6px);
  box-shadow: 0 20px 48px rgba(0, 0, 0, 0.15);
}
```

### Button press-down

```css
.button {
  transition: transform 0.2s spring(0.4);
}
.button:hover { transform: translateY(-1px); }
.button:active { transform: scale(0.96); }
```

### Icon button rotate

```css
.icon_button {
  transition: transform 0.4s spring;
}
.icon_button:hover { transform: rotate(90deg); }
```

### Image zoom inside a frame

```css
.image_wrap { overflow: hidden; }       /* clip the zoom */
.image_wrap img {
  transition: transform 0.6s ease-out;
}
.image_wrap:hover img {
  transform: scale(1.08);
}
```

(Note: this hovers on `.image_wrap` and animates `.image_wrap img`, descendant selector. The converter applies hover styles to the matched element. If the descendant doesn't match a class the converter recognizes as a node, the hover is dropped, prefer putting the hover on the same element.)

---

## Don'ts

- **Don't put `transition` inside the `:hover` block**, it's read from the base. Inside `:hover`, transition is ignored.
- **Don't animate `width` / `height` / `padding` on hover**, Framer's hover is transform-based. Width / padding changes are dropped (only transform + opacity + shadow are mapped).
- **Don't animate `background-color` on hover**, not mapped. Use a translate + border to convey the state change visually.
- **Don't mix `:hover` and `data-framer-loop` on the same element**, they fight over the layer's transform. Pick one.
- **Don't stack 5+ hover effects on every element**, feels nervy. Reserve the strongest hover for primary CTAs; subtle on cards; none on body text.
