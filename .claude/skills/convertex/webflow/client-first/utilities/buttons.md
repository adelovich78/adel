# Button Utilities

## Base Button — `button`

Every button uses the `button` base class.

```html
<a href="/start" class="button">Get started</a>
```

### Default CSS

```css
.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
  padding-left: 1.5rem;
  padding-right: 1.5rem;
  background-color: #000;
  color: #fff;
  border-radius: 0.5rem;
  font-weight: 600;
  text-decoration: none;
  transition: background-color 0.2s ease, transform 0.2s ease;
}

.button:hover {
  opacity: 0.9;
}
```

**Adapt colors, sizes, and border-radius to the project's design system.**

## Variants — Combo Classes

### Secondary — `button is-secondary`

```html
<a href="/learn" class="button is-secondary">Learn more</a>
```

```css
.button.is-secondary {
  background-color: transparent;
  color: #000;
  border-width: 1px;
  border-style: solid;
  border-color: #000;
}
```

### Text-only — `button is-text`

```html
<a href="/details" class="button is-text">Read more</a>
```

```css
.button.is-text {
  background-color: transparent;
  color: #000;
  padding-top: 0;
  padding-bottom: 0;
  padding-left: 0;
  padding-right: 0;
}
```

### Small — `button is-small`

```html
<a href="/tag" class="button is-small">Tag</a>
```

```css
.button.is-small {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  padding-left: 1rem;
  padding-right: 1rem;
  font-size: 0.875rem;
}
```

### Large — `button is-large`

```html
<a href="/start" class="button is-large">Get started now</a>
```

```css
.button.is-large {
  padding-top: 1rem;
  padding-bottom: 1rem;
  padding-left: 2.5rem;
  padding-right: 2.5rem;
  font-size: 1.125rem;
}
```

## Button with Icon

```html
<a href="/start" class="button">
  <span class="button_text">Get started</span>
  <span class="button_icon">→</span>
</a>
```

```css
.button_icon {
  margin-left: 0.5rem;
  transition: transform 0.2s ease;
}

.button:hover .button_icon {
  /* Note: this hover effect needs a ghost block embed since it's a descendant selector */
}
```

**For the hover effect on the icon**, add a ghost block:

```html
<div class="w-embed">
  <style>
    .button:hover .button_icon { transform: translateX(4px); }
  </style>
</div>
```

## Button Group Pattern

Wrap multiple buttons in a custom wrapper:

```html
<div class="hero_button-wrapper">
  <a href="/start" class="button">Get started</a>
  <a href="/learn" class="button is-secondary">Learn more</a>
</div>
```

```css
.hero_button-wrapper {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

@media (max-width: 479px) {
  .hero_button-wrapper {
    flex-direction: column;
  }
  .hero_button-wrapper .button {
    /* Note: needs ghost block embed for descendant selector */
    width: 100%;
    text-align: center;
  }
}
```

## Rules

- `button` is a **utility class** (no underscore) — reusable across all sections
- `is-secondary`, `is-text`, `is-small`, `is-large` are **combo classes** — never used alone
- CSS uses compound selector: `.button.is-secondary { }` not `.is-secondary { }`
- Button wrappers are **custom classes**: `hero_button-wrapper`, `cta_button-wrapper`
- For form submit: use `<input type="submit" class="button">` or `<button type="submit" class="button">`
