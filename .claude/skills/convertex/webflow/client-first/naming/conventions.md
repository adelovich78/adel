# Client-First Naming Conventions

## 3 Class Types

### 1. Custom Classes — `[folder]_[element-name]`

Underscore separates the component (folder) from the element: `hero_heading`, `footer_link`, `team-list_headshot-image`.

Rules:
- **One underscore only** — folder + element
- **Hyphens for multi-word names** — `team-list_headshot-wrapper`
- **Descriptive, not abbreviated** — `hero_description` not `hero_desc`
- **Lowercase always** — never camelCase

```html
<section class="section_hero">
  <div class="hero_content">
    <h1 class="hero_heading">Welcome</h1>
    <p class="hero_description">Build something great</p>
    <div class="hero_button-wrapper">
      <a href="/start" class="button">Get started</a>
    </div>
  </div>
  <div class="hero_image-wrapper">
    <img src="hero.jpg" alt="Hero" class="hero_image" />
  </div>
</section>
```

### 2. Utility Classes — `[category]-[property]-[value]`

No underscore, hyphens only. Reusable across the entire site.

```html
<p class="text-size-large">Large text paragraph</p>
<div class="margin-bottom margin-medium">Spaced element</div>
<div class="hide-mobile-portrait">Hidden on small screens</div>
```

General → specific reading order: `text-size-large` reads as "text → size → large".

### 3. Combo Classes — `is-[variant]`

Applied ON TOP of a base class to create variants. **Never used alone.**

```html
<a class="button is-secondary">Learn more</a>
<div class="card is-featured">Featured card</div>
<a class="nav_link is-active">Current page</a>
```

```css
.button.is-secondary { background-color: transparent; border-width: 1px; border-style: solid; }
.card.is-featured { border-color: #2563eb; }
.nav_link.is-active { color: #2563eb; font-weight: 700; }
```

## Decision Guide

| Situation | Use | Example |
|---|---|---|
| Specific to one component | Custom | `hero_heading` |
| Reused across multiple components | Utility | `text-size-large` |
| Variant of a base style | Combo | `is-highlighted` |
| 3+ utilities would stack | Custom class instead | `hero_stats` (not `text-size-large text-weight-bold text-color-primary`) |

## No Deep Stacking

Max 2 utility classes per element.

**Exception:** `padding-global padding-section-large` (Layer 4 structure) — this specific combo is always allowed.

**Wrong:**
```html
<p class="text-size-large text-weight-bold text-color-primary">Too many utilities</p>
```

**Right:**
```html
<p class="hero_stat-value">Clean custom class</p>
```

## Common Naming Patterns

| Component | Custom classes |
|---|---|
| Hero section | `hero_content`, `hero_heading`, `hero_description`, `hero_button-wrapper`, `hero_image` |
| Feature card | `feature_card`, `feature_icon`, `feature_title`, `feature_description` |
| Testimonial | `testimonial_card`, `testimonial_quote`, `testimonial_author`, `testimonial_avatar` |
| Pricing | `pricing_card`, `pricing_title`, `pricing_price`, `pricing_features`, `pricing_cta` |
| Footer | `footer_component`, `footer_top`, `footer_bottom`, `footer_link`, `footer_social` |
| CTA section | `cta_content`, `cta_heading`, `cta_description`, `cta_button-wrapper` |
