# Tabs

## Required Structure

```html
<div class="w-tabs" data-current="Tab 1" data-duration-in="300" data-duration-out="100" data-easing="">
  <div class="w-tab-menu">
    <a class="w-tab-link w--current" data-w-tab="Tab 1">Tab 1</a>
    <a class="w-tab-link" data-w-tab="Tab 2">Tab 2</a>
  </div>
  <div class="w-tab-content">
    <div class="w-tab-pane w--tab-active" data-w-tab="Tab 1">Content 1</div>
    <div class="w-tab-pane" data-w-tab="Tab 2">Content 2</div>
  </div>
</div>
```

## Data Attributes

| Attribute | Default | Description |
|---|---|---|
| `data-current` | `""` | Name of the active tab (must match a `data-w-tab` value) |
| `data-easing` | `""` | Easing function (`"ease"`, `"ease-in"`, `"ease-out"`, `"ease-in-out"`) |
| `data-duration-in` | `300` | Fade-in duration (ms) |
| `data-duration-out` | `100` | Fade-out duration (ms) |

## Required Elements

### Wrapper — `div.w-tabs`
Root element with data attributes.

### Menu — `div.w-tab-menu`
Contains the tab triggers.

### Tab Link — `a.w-tab-link`
Individual tab trigger. **Must have `data-w-tab` matching its corresponding pane.**

The active tab link gets the `w--current` class.

### Content — `div.w-tab-content`
Contains all tab panes.

### Tab Pane — `div.w-tab-pane`
Individual tab content. **Must have `data-w-tab` matching its corresponding link.**

The active pane gets the `w--tab-active` class.

## Critical: Matching Rule

`data-w-tab` on the link **must exactly match** `data-w-tab` on the pane:

```html
<a class="w-tab-link" data-w-tab="Pricing">Pricing</a>
<!-- ... -->
<div class="w-tab-pane" data-w-tab="Pricing">Pricing content here</div>
```

## Active State Classes

- `w--current` on the active tab link
- `w--tab-active` on the active tab pane

These are Webflow system classes. Only one tab should be active at a time.
