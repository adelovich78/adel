# Page Skeleton — Mandatory Structure

## The 5 Layers

```html
<div class="page-wrapper">
  <header class="nav_component">...</header>         <!-- Outside main-wrapper -->
  <main class="main-wrapper">
    <section class="section_hero">
      <div class="padding-global padding-section-large">
        <div class="container-large">
          <!-- section content goes here -->
        </div>
      </div>
    </section>
    <section class="section_features">
      <div class="padding-global padding-section-medium">
        <div class="container-large">
          <!-- section content goes here -->
        </div>
      </div>
    </section>
  </main>
  <footer class="footer_component">...</footer>      <!-- Outside main-wrapper -->
</div>
```

## Layer Rules

| Layer | Class | Tag | Purpose |
|---|---|---|---|
| 1 | `page-wrapper` | `<div>` | Outermost page wrapper |
| 2 | `main-wrapper` | `<main>` | Wraps all sections (excludes nav/footer) |
| 3 | `section_[id]` | `<section>` | Named section |
| 4 | `padding-global padding-section-[size]` | `<div>` | Horizontal + vertical spacing |
| 5 | `container-[size]` | `<div>` | Centered max-width content area |

**CRITICAL:** `padding-global` and `padding-section-[size]` go on the **SAME div**. Never separate them into two nested divs.

Nav and footer sit **OUTSIDE** `main-wrapper`, **INSIDE** `page-wrapper`.

## Container Sizes

| Class | Max-width | Typical use |
|---|---|---|
| `container-large` | `80rem` (1280px) | Standard page content |
| `container-medium` | `64rem` (1024px) | Blog posts, focused content |
| `container-small` | `48rem` (768px) | Forms, narrow content |

## Padding Section Sizes

| Class | Desktop | Tablet (991px) | Mobile (479px) |
|---|---|---|---|
| `padding-section-large` | `8rem` | `6rem` | `4rem` |
| `padding-section-medium` | `5rem` | `4rem` | `3rem` |
| `padding-section-small` | `3rem` | `2rem` | `1.5rem` |

## padding-global Values

| Breakpoint | Value |
|---|---|
| Desktop | `2.5rem` (40px) |
| Tablet (991px) | `1.5rem` (24px) |
| Mobile (479px) | `1.25rem` (20px) |

## Section Theming

Use combo classes on the `<section>` for visual themes:

```html
<section class="section_cta section-style-dark">
  <div class="padding-global padding-section-large">
    <div class="container-large">
      <h2 class="cta_heading">Ready to start?</h2>
    </div>
  </div>
</section>
```

```css
.section-style-dark { background-color: #0f172a; color: #f8fafc; }
```

## Complete Page Example

```html
<div class="page-wrapper">
  <!-- Nav (outside main-wrapper) -->
  <div class="w-nav navbar" data-collapse="medium" data-animation="default" data-duration="400">
    <div class="w-container nav_container">
      <a href="/" class="w-nav-brand">
        <img src="logo.svg" alt="Logo" class="nav_logo" />
      </a>
      <nav class="w-nav-menu nav_menu">
        <a href="/features" class="w-nav-link nav_link">Features</a>
        <a href="/pricing" class="w-nav-link nav_link">Pricing</a>
        <a href="/contact" class="w-nav-link nav_link">Contact</a>
      </nav>
      <div class="w-nav-button nav_menu-button">
        <div class="w-icon-nav-menu"></div>
      </div>
    </div>
  </div>

  <!-- Main content -->
  <main class="main-wrapper">
    <!-- Hero -->
    <section class="section_hero">
      <div class="padding-global padding-section-large">
        <div class="container-large">
          <div class="hero_content">
            <h1 class="hero_heading">Build faster with Webflow</h1>
            <p class="hero_description text-size-large">The modern way to create websites.</p>
            <div class="hero_button-wrapper">
              <a href="/start" class="button">Get started</a>
              <a href="/demo" class="button is-secondary">Watch demo</a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section class="section_features">
      <div class="padding-global padding-section-medium">
        <div class="container-large">
          <h2 class="features_heading">Why choose us</h2>
          <div class="features_grid">
            <div class="feature_card">
              <h3 class="feature_title">Fast</h3>
              <p class="feature_description">Lightning-fast performance.</p>
            </div>
            <div class="feature_card">
              <h3 class="feature_title">Reliable</h3>
              <p class="feature_description">99.9% uptime guarantee.</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer (outside main-wrapper) -->
  <footer class="footer_component">
    <div class="padding-global padding-section-small">
      <div class="container-large">
        <div class="footer_content">
          <p class="footer_copyright text-size-small">2025 Company. All rights reserved.</p>
        </div>
      </div>
    </div>
  </footer>
</div>
```
