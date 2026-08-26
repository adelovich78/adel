# Example: Feature cards grid

A 3-column grid of feature cards, each with an icon, title, and description. Demonstrates grid layout, scroll-triggered appear, hover lift, and proper inner-card stack composition.

---

## HTML

```html
<section class="features">
  <div class="features_inner">
    <div class="features_intro" data-framer-scroll-appear="fade-up" data-scroll-threshold="0.2">
      <span class="features_eyebrow">What you get</span>
      <h2 class="features_heading">Everything you need to ship a Framer site</h2>
      <p class="features_lead">From idea to publish, Convertex generates the markup, the styles, the animations.</p>
    </div>

    <div class="features_grid">
      <article class="feature_card" data-framer-scroll-appear="fade-up" data-scroll-delay="0">
        <div class="feature_card_icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>
        <h3 class="feature_card_title">Native paste</h3>
        <p class="feature_card_text">Output drops straight into Framer as native layers. No reformatting.</p>
      </article>

      <article class="feature_card" data-framer-scroll-appear="fade-up" data-scroll-delay="100ms">
        <div class="feature_card_icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 4v16m-8-8h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </div>
        <h3 class="feature_card_title">Animations included</h3>
        <p class="feature_card_text">Mount, scroll, hover, press, ticker, drag. All native Framer effects, set via attributes.</p>
      </article>

      <article class="feature_card" data-framer-scroll-appear="fade-up" data-scroll-delay="200ms">
        <div class="feature_card_icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M3 12l9 9 9-9-9-9-9 9z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
          </svg>
        </div>
        <h3 class="feature_card_title">Responsive by default</h3>
        <p class="feature_card_text">Breakpoints at 1200, 810, 390 with full cascade. No mobile guesswork.</p>
      </article>
    </div>
  </div>
</section>
```

---

## CSS

```css
/* Section frame */
.features {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  padding: 120px 32px;
  background: #0a0a0a;
}

/* Inner column, capped width */
.features_inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 64px;
  width: 100%;
  max-width: 1200px;
}

/* Intro stack */
.features_intro {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  max-width: 720px;
  text-align: center;
}
.features_eyebrow {
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  font-size: 0.875rem;
  line-height: 1.1;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #ff5b3a;
  text-align: center;
}
.features_heading {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  font-size: 2.75rem;
  line-height: 1.1;
  letter-spacing: -0.02em;
  color: #ffffff;
  text-align: center;
  text-wrap: balance;
}
.features_lead {
  font-family: 'Inter', sans-serif;
  font-weight: 400;
  font-size: 1.125rem;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.75);
  text-align: center;
}

/* Grid of cards */
.features_grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  width: 100%;
}

/* Card */
.feature_card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 16px;
  padding: 32px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  transition: transform 0.4s spring;
}
.feature_card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 48px rgba(0, 0, 0, 0.3);
}

.feature_card_icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(255, 91, 58, 0.12);
  color: #ff5b3a;
}

.feature_card_title {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 600;
  font-size: 1.375rem;
  line-height: 1.2;
  color: #ffffff;
}
.feature_card_text {
  font-family: 'Inter', sans-serif;
  font-weight: 400;
  font-size: 1rem;
  line-height: 1.55;
  color: rgba(255, 255, 255, 0.7);
}

/* Tablet, 2 columns */
@media (max-width: 1199px) {
  .features { padding: 96px 32px; }
  .features_inner { gap: 48px; }
  .features_grid { grid-template-columns: repeat(2, 1fr); gap: 20px; }
  .features_heading { font-size: 2.25rem; }
  .features_lead { font-size: 1rem; }
}

/* Mobile, 1 column */
@media (max-width: 810px) {
  .features { padding: 72px 16px; }
  .features_inner { gap: 40px; }
  .features_grid { grid-template-columns: 1fr; gap: 16px; }
  .features_intro { gap: 12px; }
  .features_heading { font-size: 1.75rem; line-height: 1.15; letter-spacing: -0.01em; }
  .feature_card { padding: 24px; gap: 12px; }
  .feature_card_title { font-size: 1.25rem; }
}
```

---

## Notes

- **Grid responsive**: 3 columns desktop, 2 columns tablet, 1 column mobile. The converter maps each `grid-template-columns: repeat(N, 1fr)` to `gridColumnCount: N` with `gridType: "columns"`.
- **Per-card scroll appear with cascading delays**: each card has its own `data-framer-scroll-appear="fade-up"` + an explicit `data-scroll-delay` (`0`, `100ms`, `200ms`). The cards reveal in sequence as the section scrolls into view.
- **Card hover**: subtle lift + shadow, spring transition. Avoids scale (cards in a grid look strange when one is bigger than its neighbors).
- **Icon tile**: `inline-flex` so it hugs `48 × 48`. Background is a tinted version of the brand color (12% alpha).
- **Intro is independently scroll-triggered** at `threshold: 0.2` so it animates as soon as it's 20% visible, earlier than the cards.

## Variants

- **More cards**: 6 cards in a 3-column grid → 2 rows. Same pattern, just add more `<article>` elements.
- **With image card cover**: replace the icon block with a full-width image container at the top of the card. Use `aspect-ratio: 16 / 9; overflow: hidden;` and `<img>` inside.
- **Asymmetric grid**: add `.feature_card.is-wide { grid-column: span 2; }` for a featured card. The converter maps `grid-column: span 2` to `gridItemColumnSpan: 2`.

## What to avoid

- Don't put `:hover` on the cards' children (icon, title, text). Hover the whole card, descendants come along.
- Don't add stagger via `data-framer-stagger` on the grid parent, works for flex stacks, not as cleanly for grid (cards may have different scroll positions).
