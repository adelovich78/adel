# Ghost Blocks — Dynamic States & Custom Code

## What Are Ghost Blocks?

Ghost blocks are `<div class="w-embed">` elements containing `<style>` tags for CSS that Webflow doesn't support natively (pseudo-elements, descendant selectors, keyframes). They're also used for raw HTML/SVG embeds.

## Dynamic Combo Classes

Define combo class styles in CSS to control element states. The base class must exist on an element in the HTML:

```css
/* Base: fully visible (editable in Webflow Designer) */
.modal_overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  transition: opacity 0.3s ease, visibility 0.3s ease;
}

/* Combo: active state (toggled by JS) */
.modal_overlay.is-active {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
}
```

## CRITICAL: Initial Hidden States in JS, NOT CSS

All elements must be visible by default in Webflow Designer. This is essential for editing.

**NEVER set these in base CSS:**
- `display: none`
- `opacity: 0`
- `visibility: hidden`
- `height: 0`

**ALWAYS set initial hidden states via JavaScript:**

```javascript
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.modal_overlay').forEach(function(el) {
    el.style.opacity = '0';
    el.style.visibility = 'hidden';
    el.style.pointerEvents = 'none';
  });
});
```

## CSS Transitions over JS Animations

Use CSS `transition` on the base class. JavaScript only toggles the class:

```javascript
trigger.addEventListener('click', function() {
  menu.classList.toggle('is-open');
});
```

**Important:** The combo class CSS (`.menu.is-open { ... }`) MUST be in the **flat CSS block**, not only in a ghost block. Flat CSS gets converted to native Webflow styles, which is required for combo classes to work after pasting.

For complete animation patterns (CSS transitions vs GSAP, decision guide, self-check), see **[JS Animations](js-animations.md)**.

## Ghost Block for Unsupported CSS

When you need CSS that Webflow doesn't support natively (nth-child, keyframes), add a ghost block embed in the HTML.

**FORBIDDEN:** `::before` and `::after` are **never allowed**, even in ghost blocks. They are poorly supported by Webflow and not manageable in the Designer. Always use a real HTML element instead (e.g. a `<div>` with a class for decorative elements, underlines, icons, or separators).

```html
<!-- Ghost block for keyframe animation -->
<div class="w-embed">
  <style>
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
    .status_dot { animation: pulse 2s ease-in-out infinite; }
  </style>
</div>
```

## Common Use Cases

| Pattern | Approach |
|---|---|
| Modal / dialog | Base visible, JS hides on load, `.is-open` combo shows |
| Mobile menu | Same pattern as modal |
| Dropdown mega menu | `.is-open` combo on the menu panel |
| Accordion | `.is-open` combo on the content panel |
| Toast notifications | JS creates and adds `.is-visible` combo |
| Tab underline animation | CSS transition on position/width with combo class |
| Hover card effects | `:hover` pseudo (natively supported) + `transition` |
