# Navbar

**Default: build a CUSTOM navbar, NOT Webflow's native `w-nav`.** Convertex prefers a plain, fully controllable nav. Build the native Webflow navbar (the `w-nav` structure below) **only** when the user explicitly asks for it ("the Webflow navbar", "native navbar", or wants Webflow to handle the burger interaction).

## Custom navbar (default)

A normal semantic structure with your own flat classes — no `w-nav`, no `w-nav-*` classes, no Webflow nav interaction. Handle the mobile menu toggle with JS class-toggling (see Rule #5 and the [Animations guide](../patterns/js-animations.md)), not Webflow's built-in nav.

```html
<nav class="navbar">
  <div class="navbar_container">
    <a href="/" class="navbar_brand"><img src="logo.svg" alt="Company" class="navbar_logo" /></a>
    <div class="navbar_menu">
      <a href="/about" class="navbar_link">About</a>
      <a href="/contact" class="navbar_link">Contact</a>
      <a href="/get-started" class="button is-primary">Get started</a>
    </div>
    <div class="navbar_burger" role="button" tabindex="0" aria-label="Open menu" aria-expanded="false">
      <div class="navbar_burger-line"></div>
      <div class="navbar_burger-line"></div>
    </div>
  </div>
</nav>
```

- **The burger is a `<div role="button">`, NOT a `<button>`.** A `<button>` is atomic in Webflow (Rule from `links-buttons.md`): it can only hold plain text, never child elements. A burger needs its lines (`<div>`s) or an icon inside, so it must be a `<div role="button" tabindex="0">` (keyboard-focusable, with `aria-label` + toggled `aria-expanded`) or an `<a>`. Writing a `<button>` with `<div>` children breaks in the Webflow Designer.
- Flat selectors only (`.navbar_link`, never `.navbar .link`). This applies to combo classes on children too: to style the CTA inside the nav, put the combo directly on it (`.button.is-primary`), never qualify by an ancestor (`.navbar_actions .button.is-primary` is a forbidden descendant selector). If a child needs context-specific styling, propagate a combo class onto that child instead of nesting selectors.
- Mobile: on burger click, toggle an `is-open` combo class on `.navbar_menu` via JS; define the open state in CSS. Never `display: none` in base CSS (Rule #5) — start visible, hide for mobile via a combo class set on load.
- Keep `<nav>` semantic; the icon-only burger needs `aria-label` and a toggled `aria-expanded`.

---

## Native Webflow navbar — ONLY if explicitly requested

Everything below is the native `w-nav` component. Use it **only** when the user explicitly wants Webflow's built-in navbar / burger interaction.

## Required Structure

```html
<div class="w-nav navbar"
  data-animation="default"
  data-collapse="medium"
  data-duration="400"
  data-easing="ease"
  data-easing2="ease">
  <div class="w-container nav-container">
    <a href="/" class="w-nav-brand"><img src="logo.svg" alt="Logo" /></a>
    <nav class="w-nav-menu nav-menu">
      <a href="/about" class="w-nav-link nav-link">About</a>
      <a href="/contact" class="w-nav-link nav-link">Contact</a>
    </nav>
    <div class="w-nav-button menu-button">
      <div class="w-icon-nav-menu"></div>
    </div>
  </div>
</div>
```

## Data Attributes

| Attribute | Default | Description |
|---|---|---|
| `data-animation` | `"default"` | Menu open/close animation type |
| `data-collapse` | `"none"` | Breakpoint where menu collapses to burger (`"medium"` = tablet) |
| `data-duration` | `400` | Animation duration (ms) |
| `data-easing` | `"ease"` | Open easing |
| `data-easing2` | `"ease"` | Close easing |

## Required Elements

### Root — `div.w-nav`
The outermost navbar wrapper. Must have the `w-nav` class.

### Container — `div.w-container`
Wraps all navbar content. Use `w-container` inside the navbar for proper layout.

### Brand — `a.w-nav-brand`
Logo/brand link. Typically contains an `<img>` or text.

```html
<a href="/" class="w-nav-brand">
  <img src="logo.svg" alt="Company" class="brand-logo" />
</a>
```

### Menu — `nav.w-nav-menu`
Contains all navigation links. Collapses into burger on mobile.

```html
<nav class="w-nav-menu nav-menu">
  <a href="/about" class="w-nav-link">About</a>
  <a href="/services" class="w-nav-link">Services</a>
</nav>
```

### Nav Links — `a.w-nav-link`
Individual navigation links inside the menu. The active link gets the `w--current` class:

```html
<a href="/about" class="w-nav-link nav-link w--current">About</a>
```

### Menu Button — `div.w-nav-button`
The hamburger/burger button for mobile. Contains the menu icon.

```html
<div class="w-nav-button menu-button">
  <div class="w-icon-nav-menu"></div>
</div>
```

### Menu Icon — `div.w-icon-nav-menu`
The hamburger icon inside the menu button. Renders Webflow's default burger icon.

## Dropdown Inside Navbar

Dropdowns are fully supported inside the navbar menu. See `elements/dropdown.md` for the structure.

```html
<nav class="w-nav-menu nav-menu">
  <a href="/about" class="w-nav-link">About</a>
  <div class="w-dropdown" data-hover="true" data-delay="0">
    <div class="w-dropdown-toggle">
      <div class="w-icon-dropdown-toggle"></div>
      <div>Services</div>
    </div>
    <nav class="w-dropdown-list">
      <a href="/web" class="w-dropdown-link">Web Design</a>
      <a href="/app" class="w-dropdown-link">App Dev</a>
    </nav>
  </div>
  <a href="/contact" class="w-nav-link">Contact</a>
</nav>
```
