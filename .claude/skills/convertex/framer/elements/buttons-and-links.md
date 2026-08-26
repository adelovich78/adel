# Buttons and Links

Framer treats `<a>` and `<button>` as text by default, they classify as text tags and become RichTextNodes. To get a **proper Framer button** (a Frame layer with the label inside), the element needs **frame-style CSS**.

---

## Frame-intent promotion

The converter promotes `<a>` or `<button>` to a FrameNode (with the label as a child RichTextNode) when **all of these are true**:

1. `display` is one of: `flex`, `inline-flex`, `block`, `inline-block`, `grid`
2. AND any of: `background-color` / `background-image`, `border-radius > 0`, `padding > 0`, `border-width > 0`

If both conditions fail, the link/button stays as a plain text node.

**Practical rule**: if you want it to look like a button, make it look like a button. Background, padding, radius. The promotion is automatic.

---

## Recipes

### Primary CTA button

```html
<a href="/start" class="button is-primary">Get started</a>
```

```css
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
  box-shadow: 0 12px 32px rgba(255, 91, 58, 0.3);
}
.button.is-primary:active {
  transform: scale(0.97);
}
```

The converter:
- Sees `display: inline-flex` + `padding > 0` + `background` → promotes to FrameNode.
- Wraps the label "Get started" as a RichTextNode child carrying the typography.
- Reads `:hover` and `:active` as Framer's hover/press gestures.
- Uses the `transition` spring tuning.

### Secondary button (outline)

```html
<a href="/docs" class="button is-secondary">Read docs</a>
```

```css
.button.is-secondary {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #ffffff;
}
.button.is-secondary:hover {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.4);
}
```

Note: Framer's `whileHover` doesn't animate `background-color` or `border-color`. The `:hover` selector still applies as a static state, but the visible transition is only on properties Framer's hover gesture handles (transform, opacity, shadow). For background/border color changes, design for "the user sees them but they appear instantly", or pair with a `transform: translateY(-1px)` so something animates.

### Button with icon

```html
<a href="/start" class="button is-primary">
  <span class="button_label">Get started</span>
  <svg class="button_icon" width="14" height="14" viewBox="0 0 14 14" aria-hidden="true">
    <path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
  </svg>
</a>
```

```css
.button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 24px;
  background: #ff5b3a;
  color: #fff;
  border-radius: 999px;
  font-weight: 600;
  font-size: 1rem;
  text-decoration: none;
  transition: transform 0.3s spring;
}
.button_label {
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 1rem;
  line-height: 1;
  color: #fff;
}
.button_icon {
  width: 14px;
  height: 14px;
  color: #fff;
}
.button:hover .button_icon {
  /* Note: descendant transition won't always animate in Framer.
     If you need icon-specific motion, animate the whole button. */
}
```

For an icon-only button, set `aria-label` on the anchor so it stays accessible:

```html
<a href="/" class="icon_button" aria-label="Home">
  <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true">…</svg>
</a>
```

### Text link (no frame styling)

```html
<a href="/manifesto" class="text_link">Read the manifesto</a>
```

```css
.text_link {
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  font-size: 1rem;
  color: #6366f1;
  text-decoration: underline;
  text-underline-offset: 4px;
}
.text_link:hover {
  color: #818cf8;
}
```

Without frame-style CSS, `<a>` stays a RichTextNode. That's the right shape for a plain inline text link.

### Button group (CTAs side by side)

```html
<div class="button_group">
  <a href="/start" class="button is-primary">Start free</a>
  <a href="/demo" class="button is-secondary">Book a demo</a>
</div>
```

```css
.button_group {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12px;
}
@media (max-width: 810px) {
  .button_group { flex-direction: column; align-items: stretch; gap: 12px; }
  .button { width: 100%; justify-content: center; }
}
```

On mobile, the group stacks and buttons fill width.

---

## `<button>` vs `<a>`

| Use | Why |
|---|---|
| `<a href="...">` | **Default for navigation / CTAs**. Crawlable, has a real URL, works with right-click "Open in new tab". |
| `<button type="button">` | Triggers an in-page action (open modal, submit form). Use only when there's no URL to navigate to. |

The converter promotes both equivalently. The choice is semantic.

For form submit buttons:
```html
<button type="submit" class="button is-primary">Submit</button>
```

---

## `target` and `rel`

```html
<a href="https://external.com" class="button" target="_blank" rel="noopener noreferrer">External</a>
```

Both pass through to Framer. Always pair `target="_blank"` with `rel="noopener noreferrer"` for security.

---

## When the button has multiple text spans

A button with split typography (e.g. "Save" + a price "($29)"), declare typography on each span:

```html
<a href="/buy" class="button is-primary">
  <span class="button_label">Buy now</span>
  <span class="button_meta">$29</span>
</a>
```

```css
.button {
  display: inline-flex;
  align-items: baseline;
  gap: 8px;
  padding: 14px 24px;
  background: #ff5b3a;
  color: #fff;
  border-radius: 999px;
  text-decoration: none;
}
.button_label {
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 1rem;
  color: #fff;
}
.button_meta {
  font-family: 'Inter', sans-serif;
  font-weight: 400;
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.75);
}
```

The converter sees a flex container with multiple children, each span becomes its own RichTextNode with its own typography (Aurora bug-fix #1 from Session 5).

---

## Don'ts

- **Don't put `<div>`s inside `<a>` for nothing**. A bare `<a>Get started</a>` with frame-style CSS becomes a button correctly. Adding wrapper divs makes the layer tree noisier without benefit.
- **Don't use `<button>` if there's a URL to navigate to**, use `<a>` with `href`. Keeps SEO and right-click semantics intact.
- **Don't write `class="w-button"` or `class="w-inline-block"`**, those are Webflow classes. The converter ignores them and they confuse readers.
- **Don't omit `text-decoration: none` on `<a>`** styled as a button, browsers default to underline.
- **Don't omit `:hover` on a button**, feels broken without feedback. At minimum, a 2px translate.
