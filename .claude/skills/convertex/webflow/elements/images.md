# Images

## Standard Image

```html
<img src="photo.jpg" alt="Description" class="hero-image" loading="lazy" />
```

## Attributes

| Attribute | Default | Notes |
|---|---|---|
| `src` | — | Image source URL (required) |
| `alt` | `""` | Alt text — required for accessibility |
| `loading` | `"lazy"` | Use `"eager"` for above-the-fold images |
| `width` / `height` | `"auto"` | Optional dimensions |

## Responsive Images

```html
<img
  src="photo-800.jpg"
  srcset="photo-400.jpg 400w, photo-800.jpg 800w, photo-1200.jpg 1200w"
  sizes="(max-width: 479px) 100vw, (max-width: 767px) 80vw, 50vw"
  alt="Responsive photo"
  class="responsive-image"
/>
```

Both `srcset` and `sizes` are preserved.

## SVG Handling

### SVG as Image (decorative)
```html
<img src="icon.svg" alt="Icon" class="icon" />
```

### Inline SVG (needs CSS control or animation)

**Must be wrapped in `<div class="w-embed">`:**

```html
<div class="w-embed">
  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M12 2L2 22h20L12 2z" stroke="currentColor" stroke-width="2"/>
  </svg>
</div>
```

A raw `<svg>` without the `w-embed` wrapper will render incorrectly in Webflow.

## Object Fit & Aspect Ratio

For images that must fill a container without distortion:

```css
.hero_image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hero_image-wrapper {
  aspect-ratio: 16 / 9;
  overflow: hidden;
}
```

`object-fit: cover`, `object-fit: contain`, and `aspect-ratio` are all supported.

## Background Images

Applied via CSS, not HTML attributes:

```css
.hero-section {
  background-image: url('hero-bg.jpg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}
```

## Accessibility

- Every `<img>` must have an `alt` attribute
- Decorative images: use `alt=""`
- Informative images: describe the content, not the file (`alt="Team meeting in conference room"`, not `alt="photo.jpg"`)
- SVG icons without text: add `aria-label` on the parent element
