# Layout Utilities

## Max Width — `max-width-[size]`

| Class | Value | | Class | Value |
|---|---|---|---|---|
| `max-width-xxlarge` | 80rem | | `max-width-small` | 20rem |
| `max-width-xlarge` | 64rem | | `max-width-xsmall` | 16rem |
| `max-width-large` | 48rem | | `max-width-xxsmall` | 12rem |
| `max-width-medium` | 32rem | | | |

### Responsive max-width resets

Remove max-width at a specific breakpoint and below:

| Class | Breakpoint | Effect |
|---|---|---|
| `max-width-full` | All | `max-width: none` |
| `max-width-full-tablet` | 991px and below | `max-width: none` |
| `max-width-full-mobile-landscape` | 767px and below | `max-width: none` |
| `max-width-full-mobile-portrait` | 479px and below | `max-width: none` |

## Hide — `hide-[breakpoint]`

Hide elements at specific breakpoints:

| Class | Effect |
|---|---|
| `hide` | Hidden at all breakpoints (`display: none`) |
| `hide-tablet` | Hidden at 991px and below |
| `hide-mobile-landscape` | Hidden at 767px and below |
| `hide-mobile-portrait` | Hidden at 479px and below |

```html
<!-- Desktop-only element -->
<div class="hide-tablet">Only visible on desktop</div>

<!-- Mobile-only element -->
<div class="hide">
  <!-- Override with show class at mobile breakpoint via custom CSS -->
</div>
```

## Background — `background-color-[name]`

| Class | Typical use |
|---|---|
| `background-color-primary` | Main brand background |
| `background-color-secondary` | Secondary background |
| `background-color-tertiary` | Subtle background |
| `background-color-alternate` | Contrast sections |

Adapt color values to the project's design system.

## Icons

| Class | Effect |
|---|---|
| `icon-height-small` | Small icon height |
| `icon-height-medium` | Medium icon height |
| `icon-height-large` | Large icon height |
| `icon-1x1-small` | Square icon (small) |
| `icon-1x1-medium` | Square icon (medium) |
| `icon-1x1-large` | Square icon (large) |

## Positioning & Effects

| Class | CSS |
|---|---|
| `z-index-1` | `z-index: 1; position: relative` |
| `z-index-2` | `z-index: 2; position: relative` |
| `overflow-hidden` | `overflow: hidden` |
| `layer` | `position: absolute; inset: 0` (full-cover overlay) |
| `align-center` | `margin-left: auto; margin-right: auto` |
| `pointer-events-none` | `pointer-events: none` |

## Aspect Ratios

| Class | Ratio |
|---|---|
| `aspect-ratio-square` | `1 / 1` |
| `aspect-ratio-landscape` | `3 / 2` |
| `aspect-ratio-widescreen` | `16 / 9` |

```html
<div class="hero_image-wrapper aspect-ratio-widescreen overflow-hidden">
  <img src="hero.jpg" alt="Hero" class="hero_image" />
</div>
```
