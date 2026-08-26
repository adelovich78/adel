# Example: Animation showcase

A page that exercises every native Framer effect family: enter, scroll-appear, hover, press, loop, drag, ticker, text effect, and exit. Use as reference for the right cadence of each.

This is **not** a recommended layout for a real product page, it's a showcase. In production, use 2 or 3 effect families per page, not all 9.

---

## HTML

```html
<main class="lab">
  <!-- Hero with text effect + parent stagger -->
  <section class="lab_section">
    <div class="lab_inner"
         data-framer-stagger="120ms"
         data-framer-enter="fade-up"
         data-enter-delay="200ms">
      <span class="lab_eyebrow" data-framer-enter="fade-up">Animation lab</span>
      <h1 class="lab_heading"
          data-framer-text-effect="fade-up"
          data-text-tokenization="word"
          data-text-stagger="60ms"
          data-text-delay="400ms">
        Every native effect at a glance
      </h1>
      <p class="lab_lead" data-framer-enter="fade-up">
        Hover. Press. Mount. Scroll. Loop. Drag. Ticker.
      </p>
    </div>
  </section>

  <!-- Scroll-appear cards -->
  <section class="lab_section">
    <h2 class="lab_section_title" data-framer-scroll-appear="fade-up">Scroll appear</h2>
    <div class="lab_grid">
      <div class="lab_card" data-framer-scroll-appear="fade-up" data-scroll-threshold="0.2">Threshold 0.2</div>
      <div class="lab_card" data-framer-scroll-appear="fade-up" data-scroll-once="false">Replays on every entry</div>
      <div class="lab_card" data-framer-scroll-appear="scale" data-scroll-delay="100ms">Scale + delay</div>
    </div>
  </section>

  <!-- Hover + Press -->
  <section class="lab_section">
    <h2 class="lab_section_title" data-framer-scroll-appear="fade-up">Hover & Press</h2>
    <div class="lab_buttons">
      <button class="lab_button is-snappy">Snappy</button>
      <button class="lab_button is-bouncy">Bouncy</button>
      <button class="lab_button is-smooth">Smooth</button>
    </div>
  </section>

  <!-- Loop -->
  <section class="lab_section">
    <h2 class="lab_section_title" data-framer-scroll-appear="fade-up">Loop</h2>
    <div class="lab_loops">
      <div class="lab_orb" data-framer-loop="rotate" data-loop-duration="20s"></div>
      <div class="lab_orb is-pulse" data-framer-loop="pulse" data-loop-duration="2s" data-loop-easing="ease-in-out" data-loop-repeat-type="mirror"></div>
      <div class="lab_orb is-bounce" data-framer-loop="bounce" data-loop-duration="1.5s" data-loop-easing="ease-in-out" data-loop-repeat-type="mirror"></div>
    </div>
  </section>

  <!-- Ticker -->
  <section class="lab_section">
    <h2 class="lab_section_title" data-framer-scroll-appear="fade-up">Ticker</h2>
    <div class="lab_ticker"
         data-framer-ticker="true"
         data-ticker-velocity="80"
         data-ticker-hover-modifier="0">
      <span class="lab_chip">Convertex</span>
      <span class="lab_chip">Framer</span>
      <span class="lab_chip">Native paste</span>
      <span class="lab_chip">Animations</span>
      <span class="lab_chip">Breakpoints</span>
      <span class="lab_chip">Type effects</span>
    </div>
  </section>

  <!-- Drag (constrained) -->
  <section class="lab_section">
    <h2 class="lab_section_title" data-framer-scroll-appear="fade-up">Drag</h2>
    <div id="drag-zone" class="lab_drag_zone">
      <div class="lab_drag_card"
           data-framer-drag="true"
           data-drag-freeform="true"
           data-drag-constraint="#drag-zone"
           data-drag-snap-back="false">
        Drag me
      </div>
    </div>
  </section>
</main>
```

---

## CSS

```css
/* Page frame */
.lab {
  display: flex;
  flex-direction: column;
  width: 100%;
  background: #0a0a0a;
}

.lab_section {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  padding: 96px 32px;
  gap: 32px;
}

.lab_inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  max-width: 720px;
  text-align: center;
}

.lab_eyebrow {
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  font-size: 0.875rem;
  line-height: 1.1;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #ff5b3a;
}
.lab_heading {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  font-size: 4rem;
  line-height: 1.05;
  letter-spacing: -0.02em;
  color: #ffffff;
  text-align: center;
  text-wrap: balance;
}
.lab_lead {
  font-family: 'Inter', sans-serif;
  font-weight: 400;
  font-size: 1.25rem;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.7);
  text-align: center;
}
.lab_section_title {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 600;
  font-size: 1.75rem;
  line-height: 1.2;
  color: #ffffff;
}

/* Scroll-appear cards */
.lab_grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  width: 100%;
  max-width: 1000px;
}
.lab_card {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  font-size: 1rem;
  line-height: 1.4;
  color: #ffffff;
  text-align: center;
}

/* Hover & press buttons */
.lab_buttons {
  display: flex;
  flex-direction: row;
  gap: 16px;
  flex-wrap: wrap;
  justify-content: center;
}
.lab_button {
  display: inline-flex;
  padding: 14px 28px;
  border: 0;
  border-radius: 999px;
  background: #ff5b3a;
  color: #ffffff;
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 1rem;
  line-height: 1;
  cursor: pointer;
}
.lab_button.is-snappy { transition: transform 0.2s spring(0.4); }
.lab_button.is-snappy:hover { transform: translateY(-2px); }
.lab_button.is-snappy:active { transform: scale(0.96); }

.lab_button.is-bouncy { transition: transform 0.5s spring(0.5); }
.lab_button.is-bouncy:hover { transform: translateY(-4px) scale(1.05); }
.lab_button.is-bouncy:active { transform: scale(0.94); }

.lab_button.is-smooth { transition: transform 0.6s ease-out; }
.lab_button.is-smooth:hover { transform: translateY(-2px) scale(1.02); }
.lab_button.is-smooth:active { transform: scale(0.97); }

/* Loop orbs */
.lab_loops {
  display: flex;
  flex-direction: row;
  gap: 64px;
  align-items: center;
  justify-content: center;
}
.lab_orb {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, #ff5b3a, #ec4899);
  border-radius: 24px;
}
.lab_orb.is-pulse {
  background: linear-gradient(135deg, #6366f1, #ec4899);
  border-radius: 999px;
}
.lab_orb.is-bounce {
  background: linear-gradient(135deg, #10b981, #6366f1);
  border-radius: 16px;
}

/* Ticker */
.lab_ticker {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 24px;
  width: 100%;
  max-width: 1200px;
  overflow: hidden;
  padding: 16px 0;
}
.lab_chip {
  display: inline-flex;
  align-items: center;
  padding: 10px 20px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  font-size: 0.9375rem;
  line-height: 1;
  color: #ffffff;
  white-space: nowrap;
}

/* Drag zone */
.lab_drag_zone {
  position: relative;
  width: 100%;
  max-width: 720px;
  height: 320px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px dashed rgba(255, 255, 255, 0.15);
  border-radius: 24px;
  overflow: hidden;
}
.lab_drag_card {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 160px;
  height: 100px;
  padding: 0 24px;
  background: linear-gradient(135deg, #ff5b3a, #ec4899);
  border-radius: 16px;
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 1rem;
  color: #ffffff;
  cursor: grab;
}

/* Tablet */
@media (max-width: 1199px) {
  .lab_section { padding: 80px 32px; }
  .lab_heading { font-size: 3rem; }
  .lab_grid { grid-template-columns: repeat(2, 1fr); }
}

/* Mobile */
@media (max-width: 810px) {
  .lab_section { padding: 64px 16px; gap: 24px; }
  .lab_heading { font-size: 2.25rem; line-height: 1.15; }
  .lab_lead { font-size: 1rem; }
  .lab_grid { grid-template-columns: 1fr; }
  .lab_buttons { flex-direction: column; align-items: stretch; }
  .lab_button { width: 100%; justify-content: center; }
  .lab_loops { gap: 32px; }
  .lab_orb { width: 48px; height: 48px; }
  .lab_drag_zone { height: 240px; }
}
```

---

## Notes

- **Hero**: text-effect on the headline (per-word), enter on eyebrow / lead with parent stagger. The text-effect's own delay (`400ms`) is calibrated to start after the eyebrow has settled.
- **Scroll cards**: each with a different scroll-appear configuration (threshold, replay, scale preset).
- **Buttons**: 3 timing curves to feel the difference between snappy spring (high bounce, fast), bouncy spring (overshoot), and smooth tween.
- **Loops**: rotate (linear forever), pulse (mirror = scale up/down/up/down), bounce (mirror = up/down).
- **Ticker**: 6 chips scroll at 80px/sec, pause when cursor enters.
- **Drag**: card draggable inside the dashed zone; constraint resolves via `#drag-zone` id.

## Production usage

Pick **at most**:
- One text effect (hero headline only)
- Enter + stagger on the hero
- Scroll-appear on subsequent sections
- Hover + press on interactive elements
- Optionally: one loop (logo strip) or one ticker (trust strip)

Skip drag for static landing pages. Skip exit unless you have multi-page transitions in Framer.
