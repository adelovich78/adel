# Example: Footer

A multi-column footer with brand block, link columns, and a bottom bar. Demonstrates a horizontal-then-vertical responsive layout, list items as columns, and inline social icons.

---

## HTML

```html
<footer class="footer">
  <div class="footer_inner">
    <div class="footer_top">
      <div class="footer_brand">
        <a href="/" class="footer_logo" aria-label="Convertex home">
          <svg width="120" height="32" viewBox="0 0 120 32" aria-hidden="true">
            <text x="0" y="22" font-family="Plus Jakarta Sans" font-weight="700" font-size="22" fill="currentColor">Convertex</text>
          </svg>
        </a>
        <p class="footer_tagline">Generate Framer-ready HTML & CSS that pastes as native layers.</p>
        <div class="footer_socials">
          <a href="https://twitter.com/convertex" class="footer_social" aria-label="Convertex on X">
            <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true">
              <path d="M3 3l14 14M17 3L3 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            </svg>
          </a>
          <a href="https://github.com/convertex" class="footer_social" aria-label="Convertex on GitHub">
            <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true">
              <circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5" fill="none" />
            </svg>
          </a>
        </div>
      </div>

      <nav class="footer_columns" aria-label="Footer">
        <div class="footer_column">
          <h4 class="footer_column_title">Product</h4>
          <ul class="footer_links">
            <li><a class="footer_link" href="/features">Features</a></li>
            <li><a class="footer_link" href="/pricing">Pricing</a></li>
            <li><a class="footer_link" href="/changelog">Changelog</a></li>
            <li><a class="footer_link" href="/playground">Playground</a></li>
          </ul>
        </div>
        <div class="footer_column">
          <h4 class="footer_column_title">Resources</h4>
          <ul class="footer_links">
            <li><a class="footer_link" href="/docs">Docs</a></li>
            <li><a class="footer_link" href="/guides">Guides</a></li>
            <li><a class="footer_link" href="/examples">Examples</a></li>
            <li><a class="footer_link" href="/community">Community</a></li>
          </ul>
        </div>
        <div class="footer_column">
          <h4 class="footer_column_title">Company</h4>
          <ul class="footer_links">
            <li><a class="footer_link" href="/about">About</a></li>
            <li><a class="footer_link" href="/blog">Blog</a></li>
            <li><a class="footer_link" href="/careers">Careers</a></li>
            <li><a class="footer_link" href="/contact">Contact</a></li>
          </ul>
        </div>
      </nav>
    </div>

    <div class="footer_bottom">
      <p class="footer_copy">© 2026 Convertex. All rights reserved.</p>
      <div class="footer_bottom_links">
        <a href="/privacy" class="footer_bottom_link">Privacy</a>
        <a href="/terms" class="footer_bottom_link">Terms</a>
      </div>
    </div>
  </div>
</footer>
```

---

## CSS

```css
/* Footer frame */
.footer {
  display: flex;
  justify-content: center;
  width: 100%;
  padding: 96px 32px 48px;
  background: #050505;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.footer_inner {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 1200px;
  gap: 64px;
}

/* Top section, brand + columns side by side */
.footer_top {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  gap: 64px;
  width: 100%;
}

/* Brand block */
.footer_brand {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 30%;
}
.footer_logo {
  display: inline-flex;
  color: #ffffff;
}
.footer_tagline {
  font-family: 'Inter', sans-serif;
  font-weight: 400;
  font-size: 0.9375rem;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.55);
}
.footer_socials {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 8px;
}
.footer_social {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.05);
  color: rgba(255, 255, 255, 0.65);
  text-decoration: none;
  transition: transform 0.3s spring;
}
.footer_social:hover {
  transform: translateY(-2px);
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

/* Link columns */
.footer_columns {
  display: flex;
  flex-direction: row;
  gap: 64px;
}
.footer_column {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 140px;
}
.footer_column_title {
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 0.875rem;
  line-height: 1.1;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #ffffff;
}
.footer_links {
  display: flex;
  flex-direction: column;
  gap: 8px;
  list-style: none;
  padding: 0;
}
.footer_link {
  font-family: 'Inter', sans-serif;
  font-weight: 400;
  font-size: 0.9375rem;
  line-height: 1.4;
  color: rgba(255, 255, 255, 0.55);
  text-decoration: none;
  transition: color 0.2s ease;
}
.footer_link:hover {
  color: #ffffff;
}

/* Bottom bar */
.footer_bottom {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  padding-top: 32px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}
.footer_copy {
  font-family: 'Inter', sans-serif;
  font-weight: 400;
  font-size: 0.875rem;
  line-height: 1.4;
  color: rgba(255, 255, 255, 0.4);
}
.footer_bottom_links {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 24px;
}
.footer_bottom_link {
  font-family: 'Inter', sans-serif;
  font-weight: 400;
  font-size: 0.875rem;
  line-height: 1.4;
  color: rgba(255, 255, 255, 0.4);
  text-decoration: none;
  transition: color 0.2s ease;
}
.footer_bottom_link:hover {
  color: rgba(255, 255, 255, 0.8);
}

/* Tablet (810) */
@media (max-width: 1199px) {
  .footer { padding: 80px 32px 40px; }
  .footer_inner { gap: 48px; }
  .footer_top { gap: 48px; }
  .footer_brand { width: 35%; }
  .footer_columns { gap: 40px; }
}

/* Mobile (390) */
@media (max-width: 810px) {
  .footer { padding: 64px 16px 32px; }
  .footer_inner { gap: 40px; }
  .footer_top { flex-direction: column; gap: 40px; }
  .footer_brand { width: 100%; }
  .footer_columns { flex-direction: column; gap: 32px; }
  .footer_bottom { flex-direction: column; align-items: flex-start; gap: 16px; padding-top: 24px; }
}
```

---

## Notes

- **Top section** is a horizontal flex with brand on the left (30% width) and columns on the right (auto). On mobile they stack.
- **Each link column** is a vertical flex with `gap: 8px` between links, no margin between `<li>` items.
- **Lists**: `<ul>` with `list-style: none; padding: 0` so we control the spacing entirely. The converter renders the list as a FrameNode containing each `<li>` as a FrameNode containing the link.
- **Bottom bar**: horizontal on desktop, stacks vertically on mobile.
- **Social buttons**: `inline-flex` 36×36 with rounded background. Hover lifts up and intensifies the background. Native Framer hover gesture.
- **Anchor jumps**: the brand logo links to `/`, but for in-page anchors (e.g. `<a href="#features">`), the converter wires them up if a section has `id="features"`.

## What this exercise

- Multi-column responsive layout (3 columns of links + brand block).
- Direction flip mobile (horizontal → vertical).
- Lists with explicit `list-style: none`.
- Inline icons via `<svg>`.
- Subtle hover on links (color change, works visually as a state, doesn't animate via Framer's hover gesture which is transform-only).
- Hover on social buttons with translate and bg change.
