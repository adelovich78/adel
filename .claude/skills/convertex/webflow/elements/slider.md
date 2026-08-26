# Slider

## Required Structure

```html
<div class="w-slider hero-slider"
  data-animation="slide"
  data-duration="500"
  data-infinite="true"
  data-easing="ease"
  data-autoplay="false"
  data-delay="4000"
  data-hide-arrows="false">
  <div class="w-slider-mask">
    <div class="w-slide slide-1">Slide 1 content</div>
    <div class="w-slide slide-2">Slide 2 content</div>
  </div>
  <div class="w-slider-arrow-left"><div class="w-icon-slider-left"></div></div>
  <div class="w-slider-arrow-right"><div class="w-icon-slider-right"></div></div>
  <div class="w-slider-nav"></div>
</div>
```

## Data Attributes

| Attribute | Default | Description |
|---|---|---|
| `data-animation` | `"slide"` | Animation type |
| `data-duration` | `500` | Transition duration (ms) |
| `data-infinite` | `"true"` | Loop slides |
| `data-easing` | `"ease"` | Easing function |
| `data-disable-swipe` | `"false"` | Disable touch swipe |
| `data-autoplay` | `"false"` | Auto-advance slides |
| `data-delay` | `4000` | Autoplay delay (ms) |
| `data-autoplay-limit` | `0` | Max autoplay cycles (0 = infinite) |
| `data-hide-arrows` | `"false"` | Hide navigation arrows |
| `data-nav-spacing` | `3` | Dot nav spacing (px) |

## Required Elements

### Wrapper — `div.w-slider`
Root element with all data attributes.

### Mask — `div.w-slider-mask`
Contains all slides. Only children with `w-slide` class are treated as slides.

### Slide — `div.w-slide`
Individual slides. Put any content inside.

### Arrows — `div.w-slider-arrow-left` / `div.w-slider-arrow-right`
Navigation arrows. Each contains an icon element:

```html
<div class="w-slider-arrow-left"><div class="w-icon-slider-left"></div></div>
<div class="w-slider-arrow-right"><div class="w-icon-slider-right"></div></div>
```

### Dot Nav — `div.w-slider-nav`
Dot-style pagination. Empty element — dots are auto-generated.

## Minimal Example

If default data attributes are fine, you can simplify:

```html
<div class="w-slider hero-slider">
  <div class="w-slider-mask">
    <div class="w-slide">
      <h2>First Slide</h2>
      <p>Description here</p>
    </div>
    <div class="w-slide">
      <h2>Second Slide</h2>
      <p>Another slide</p>
    </div>
  </div>
  <div class="w-slider-arrow-left"><div class="w-icon-slider-left"></div></div>
  <div class="w-slider-arrow-right"><div class="w-icon-slider-right"></div></div>
  <div class="w-slider-nav"></div>
</div>
```
