# Links & Buttons

## The 3 Clickable Types in Webflow

| You want | Use this HTML | Result in Webflow |
|---|---|---|
| **Button** | `<button class="button">Click me</button>` | Button element (text only, no children) |
| **Link Block** (link with nested elements) | `<a href="/page" class="card">...<div>...</div>...</a>` | Link Block |
| **Text Link** (simple text link) | `<a href="/page" class="text-link">Click here</a>` | Text Link |

## How Tags Determine the Type

Detection is **tag-based**:

- `<button>` (without `type="submit"`) → **Button**
- `<a>` with child elements inside → **Link Block**
- `<a>` with text only → **Text Link**

**Key rule:** `<a class="button">` produces a **Link**, NOT a Button. To get a Webflow Button, use `<button>`.

## Button Patterns

**CRITICAL: `<button>` in Webflow is an atomic element — it CANNOT have children.** It only contains plain text. If you need a button with an icon or multiple child elements, use `<a>` (Link Block) instead.

### Standard button (text only)
```html
<button class="button">Get started</button>
```

### Button with icon — use `<a>` Link Block
```html
<a href="/page" class="button">
  <div class="button_text">Explore</div>
  <div class="w-embed">
    <svg class="button_icon" width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true">
      <path d="M1 5h8M5 1l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>
</a>
```

### Form submit (special case)
```html
<button type="submit" class="submit-button">Send</button>
<!-- or -->
<input type="submit" value="Send" class="submit-button" />
```

`type="submit"` makes it a Form Button, not a regular Button.

## Link Block Patterns

### Card as link
```html
<a href="/project" class="project_card">
  <img src="thumb.jpg" alt="Project" class="project_image" />
  <h3 class="project_title">Project Name</h3>
  <p class="project_desc">Brief description</p>
</a>
```

### Link with icon
```html
<a href="/page" class="nav_link">
  <div class="nav_link-text">About</div>
  <div class="w-embed">
    <svg class="nav_link-arrow" width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true">
      <path d="M1 5h8M5 1l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>
</a>
```

## Text Link Patterns

```html
<a href="/terms" class="text-link">Terms of service</a>
<a href="/privacy" class="footer_link">Privacy policy</a>
```

## Best Practices

1. **Prefer Link Blocks over text links** when you need styling flexibility — a `<div>` child gives you independent control over text styling, padding, and icon placement.

2. **Always set `href`** on `<a>` elements — even `href="#"` is better than no href.

3. **`<button>` = text only, no children.** If you need an icon or nested elements, use `<a>` (Link Block) instead. `<button>` in Webflow is atomic.

4. **Link with text + icon** — always use `<div>` children, never `<span>`. Text in a `<div>`, icon SVG in a `<div class="w-embed">`:
   ```html
   <a href="/page" class="button">
     <div class="button_text">Download</div>
     <div class="w-embed"><svg>...</svg></div>
   </a>
   ```
   **NEVER** place text directly inside `<a>` alongside a `w-embed`. **NEVER** use `<span>` inside links — always `<div>`. **NEVER** put children inside `<button>`.
