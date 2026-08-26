# Example: Hero split with visual

Two-column hero: text on the left, visual on the right. Stacks vertically on mobile. Demonstrates relative-width columns, breakpoint direction flip, and a hover effect on the visual.

---

## HTML

```html
<section class="hero_split">
  <div class="hero_split_inner"
       data-framer-enter="fade-up"
       data-enter-delay="100ms"
       data-framer-stagger="100ms">
    <div class="hero_split_text" data-framer-enter="fade-up">
      <span class="hero_split_eyebrow" data-framer-enter="fade-up">For builders</span>
      <h1 class="hero_split_heading" data-framer-enter="fade-up">
        Convertex meets Framer with style fidelity
      </h1>
      <p class="hero_split_lead" data-framer-enter="fade-up">
        Generate Framer-ready HTML and CSS that converts to native layers in one paste. No reformatting, no manual cleanup.
      </p>
      <div class="hero_split_buttons" data-framer-enter="fade-up">
        <a href="/start" class="button is-primary">Start free</a>
        <a href="/playground" class="button is-secondary">Try the playground</a>
      </div>
    </div>
    <div class="hero_split_visual" data-framer-enter="fade-up">
      <img class="hero_split_image" src="https://cdn.example.com/hero.jpg" alt="Convertex editor screenshot" width="1200" height="900" loading="eager" />
    </div>
  </div>
</section>
```

---

## CSS

```css
/* Section frame */
.hero_split {
  display: flex;
  width: 100%;
  padding: 120px 32px;
  background: #0a0a0a;
}

/* Inner two-column row */
.hero_split_inner {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 64px;
  width: 100%;
  max-width: 1200px;
}

/* Text column, 55% on desktop */
.hero_split_text {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 24px;
  width: 55%;
}
.hero_split_eyebrow {
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  font-size: 0.875rem;
  line-height: 1.1;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #ff5b3a;
}
.hero_split_heading {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  font-size: 4rem;
  line-height: 1.05;
  letter-spacing: -0.02em;
  color: #ffffff;
  text-wrap: balance;
}
.hero_split_lead {
  font-family: 'Inter', sans-serif;
  font-weight: 400;
  font-size: 1.25rem;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.75);
}
.hero_split_buttons {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12px;
}

/* Visual column, 45% on desktop */
.hero_split_visual {
  width: 45%;
  border-radius: 24px;
  overflow: hidden;
  transition: transform 0.4s spring;
}
.hero_split_visual:hover {
  transform: translateY(-6px) scale(1.01);
}
.hero_split_image {
  width: 100%;
  aspect-ratio: 4 / 3;
  object-fit: cover;
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
.button.is-primary:active { transform: scale(0.97); }
.button.is-secondary {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #ffffff;
}
.button.is-secondary:hover {
  transform: translateY(-2px);
  border-color: rgba(255, 255, 255, 0.4);
}
.button.is-secondary:active { transform: scale(0.97); }

/* Tablet (810) */
@media (max-width: 1199px) {
  .hero_split { padding: 96px 32px; }
  .hero_split_inner { gap: 40px; }
  .hero_split_heading { font-size: 3rem; line-height: 1.1; }
  .hero_split_lead { font-size: 1.125rem; }
}

/* Mobile (390), flip to vertical */
@media (max-width: 810px) {
  .hero_split { padding: 72px 16px; }
  .hero_split_inner { flex-direction: column; gap: 32px; }
  .hero_split_text { width: 100%; gap: 20px; }
  .hero_split_visual { width: 100%; }
  .hero_split_heading { font-size: 2.25rem; line-height: 1.15; letter-spacing: -0.01em; }
  .hero_split_lead { font-size: 1rem; line-height: 1.55; }
  .hero_split_buttons { flex-direction: column; align-items: stretch; gap: 12px; width: 100%; }
  .button { width: 100%; justify-content: center; }
}
```

---

## Notes

- **Two-column layout**: relative widths (55% / 45%) so columns scale proportionally up to the 1200px max. Could also be `flex: 1.2` and `flex: 1` for a similar effect.
- **Direction flip on mobile**: `flex-direction: column` at the 810 breakpoint turns the row into a vertical stack. The converter cascades base + tablet + mobile rules, so it sees `display: flex` from base and applies the column direction correctly.
- **Visual hover**: a translate-up + tiny scale, with spring transition. The image inside doesn't need its own animation; the wrapper carries it.
- **Image sizing**: `width: 100%; aspect-ratio: 4 / 3; object-fit: cover`, the wrapper has `border-radius` and `overflow: hidden` so the image respects the rounded corners.
- **Stagger**: parent `data-framer-stagger="100ms"` cascades through the text column children. The visual column doesn't have its own marker so it inherits the stagger sequence (or you can omit it from the cascade by removing `data-framer-enter`).

## When to use this layout

- Product hero with a hero screenshot or video.
- Service landing where a representative photo earns its space.
- Avoid for text-heavy heroes where the visual would feel decorative, use [hero-centered](hero-centered.md) instead.
