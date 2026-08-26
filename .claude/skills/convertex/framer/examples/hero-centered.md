# Example: Hero centered

A classic centered hero: eyebrow + heading + lead paragraph + button group, stacked vertically on a dark background. Demonstrates explicit typography per element, parent stagger, and responsive font scaling.

---

## HTML

```html
<section class="hero">
  <div class="hero_inner"
       data-framer-stagger="120ms"
       data-framer-enter="fade-up"
       data-enter-delay="200ms">
    <span class="hero_eyebrow" data-framer-enter="fade-up">v2.0, Public launch</span>
    <h1 class="hero_heading" data-framer-enter="fade-up">
      Build websites that ship in days
    </h1>
    <p class="hero_lead" data-framer-enter="fade-up">
      From design to production in a single workflow. No handoff, no rework.
    </p>
    <div class="hero_buttons" data-framer-enter="fade-up">
      <a href="/start" class="button is-primary">Start free</a>
      <a href="/demo" class="button is-secondary">Book a demo</a>
    </div>
  </div>
</section>
```

---

## CSS

```css
/* Section frame */
.hero {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  min-height: 100vh;
  padding: 120px 32px;
  background: #0a0a0a;
  overflow: hidden;
}

/* Inner column, capped width, centered children */
.hero_inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  max-width: 720px;
  gap: 24px;
}

/* Eyebrow, uppercase label */
.hero_eyebrow {
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  font-size: 0.875rem;
  line-height: 1.1;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.6);
}

/* Heading, display font-size with tight line-height */
.hero_heading {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  font-size: 4.5rem;
  line-height: 1.05;
  letter-spacing: -0.02em;
  color: #ffffff;
  text-align: center;
  text-wrap: balance;
}

/* Lead paragraph */
.hero_lead {
  font-family: 'Inter', sans-serif;
  font-weight: 400;
  font-size: 1.25rem;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.75);
  text-align: center;
  max-width: 560px;
}

/* Button group */
.hero_buttons {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12px;
}

/* Buttons */
.button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 24px;
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 1rem;
  line-height: 1;
  text-decoration: none;
  transition: transform 0.3s spring;
}
.button.is-primary {
  background: #ff5b3a;
  color: #ffffff;
}
.button.is-primary:hover {
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 12px 32px rgba(255, 91, 58, 0.35);
}
.button.is-primary:active {
  transform: scale(0.97);
}
.button.is-secondary {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #ffffff;
}
.button.is-secondary:hover {
  transform: translateY(-2px);
  border-color: rgba(255, 255, 255, 0.4);
}
.button.is-secondary:active {
  transform: scale(0.97);
}

/* Tablet (810) */
@media (max-width: 1199px) {
  .hero { padding: 96px 32px; }
  .hero_heading { font-size: 3.25rem; line-height: 1.1; }
  .hero_lead { font-size: 1.125rem; }
}

/* Mobile (390) */
@media (max-width: 810px) {
  .hero { padding: 72px 16px; }
  .hero_inner { gap: 20px; }
  .hero_heading { font-size: 2.25rem; line-height: 1.15; letter-spacing: -0.01em; }
  .hero_lead { font-size: 1rem; line-height: 1.55; }
  .hero_buttons { flex-direction: column; align-items: stretch; gap: 12px; width: 100%; }
  .button { width: 100%; justify-content: center; }
}
```

---

## What's happening

- **Section frame** is a `display: flex` column centered both axes, full viewport min-height, dark background.
- **Inner column** caps width at 720px so the line length stays readable on wide displays. `align-items: center` centers each child horizontally.
- **Eyebrow / heading / lead** each declare full typography (family, weight, size, line-height, letter-spacing, color, text-align). No reliance on parent cascade.
- **Stagger animation**: parent has `data-framer-stagger="120ms"` + `data-enter-delay="200ms"`. Each child with `data-framer-enter="fade-up"` gets a sequential delay.
- **Buttons**: primary uses `inline-flex + padding + bg + radius` → promoted to FrameNode. Hover translates up + adds a glow shadow. Press scales down.
- **Mobile**: heading shrinks, button group stacks and fills width.

---

## What the converter produces

A FrameNode tree:

```
hero (FrameNode, fill width, min-height 100vh, fillColor: black, layout: stack vertical, padding: 120 32, gap: ... centered)
  hero_inner (FrameNode, fill width capped 720, layout: stack vertical, gap: 24, centered)
    hero_eyebrow (RichTextNode, fade-up enter @ 320ms)
    hero_heading (RichTextNode, fade-up enter @ 440ms)
    hero_lead (RichTextNode, fade-up enter @ 560ms)
    hero_buttons (FrameNode, layout: stack horizontal, gap: 12, fade-up enter @ 680ms)
      button.is-primary (FrameNode, inline-flex hug, whileHover + whileTap)
      button.is-secondary (FrameNode, inline-flex hug, whileHover + whileTap)
```

Three breakpoint variants (1200, 810, 390) all share this structure, with overridden font-sizes and padding.
