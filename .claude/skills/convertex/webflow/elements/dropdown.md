# Dropdown

## Required Structure

```html
<div class="w-dropdown" data-hover="true" data-delay="0">
  <div class="w-dropdown-toggle">
    <div class="w-icon-dropdown-toggle"></div>
    <div>Menu</div>
  </div>
  <nav class="w-dropdown-list">
    <a href="/option1" class="w-dropdown-link">Option 1</a>
    <a href="/option2" class="w-dropdown-link">Option 2</a>
  </nav>
</div>
```

## Data Attributes

| Attribute | Default | Description |
|---|---|---|
| `data-hover` | `"false"` | Open on hover (`"true"` = enabled) |
| `data-delay` | `"0"` | Close delay on hover-out (ms) |

## Required Elements

### Wrapper — `div.w-dropdown`
Root element for the dropdown.

### Toggle — `div.w-dropdown-toggle`
The clickable trigger that opens the dropdown. Can contain:
- **Toggle icon** (`div.w-icon-dropdown-toggle`) — renders a default arrow/chevron
- Text content or other elements

### List — `nav.w-dropdown-list`
The dropdown panel that appears when opened.

### Links — `a.w-dropdown-link`
Links inside the dropdown list.

```html
<a href="/option" class="w-dropdown-link dropdown-item">Option Label</a>
```

## Notes

- Non-link children inside the list are also supported (divs, images, etc.)
- Dropdowns work both standalone and inside navbars (see `elements/navbar.md`)
- The toggle icon (`w-icon-dropdown-toggle`) renders Webflow's default chevron
