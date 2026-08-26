# Media: img, video, iframe, svg

The converter handles each media tag with the right Framer mechanism. **Just write standard HTML.**

---

## `<img>`

Standard HTML, with sizing attributes for layer intrinsics:

```html
<img class="hero_image"
     src="https://cdn.example.com/hero.jpg"
     alt="Workspace with laptop and notebook"
     width="1280"
     height="720"
     loading="lazy" />
```

```css
.hero_image {
  width: 100%;
  aspect-ratio: 16 / 9;
  border-radius: 16px;
  object-fit: cover;
}
```

What the converter does:
- `width` / `height` HTML attrs → `intrinsicWidth` / `intrinsicHeight` (helps Framer reserve space).
- `alt` → `altAttribute` on the FrameNode (Framer's accessibility field).
- `loading="lazy"` is passed through.
- `object-fit: cover` → `fillImageResize: "fill"` (Framer's "fill" = CSS cover). `contain` / `scale-down` → `fit`. `none` → `stretch`.
- `src` becomes `fillImage` on a FrameNode (the converter creates a single-color fallback fill behind, with `fillColor: "#333"`).

### Object position

```css
.hero_image {
  object-fit: cover;
  object-position: center top;
}
```

`object-position` keywords (`top`, `center`, `right`, `bottom`, `left`) and percent values (`50% 30%`) are handled.

### Decorative images

For images that don't add information (icons, dividers), use empty `alt`:

```html
<img class="divider_icon" src="/divider.svg" alt="" aria-hidden="true" />
```

### Don't use `<img>` for complex SVG

If you have an inline SVG with strokes, gradients, animations, or interactivity, **embed it inline** (next section).

---

## `<svg>`

Inline SVGs are routed to Framer's **Embed Code Component** with the SVG markup as the embedded HTML:

```html
<svg class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
  <path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
</svg>
```

```css
.icon {
  width: 24px;
  height: 24px;
  color: #fff;             /* drives currentColor inside the SVG */
}
```

The converter wraps this in a Framer Embed CC (mode `html`) carrying the SVG markup. The SVG renders natively in Framer.

For currentColor-driven SVGs (icon stroke = parent text color), set `color` on the SVG or its parent.

### When to use `<svg>` inline vs `<img src="...svg">`

| Pattern | When |
|---|---|
| `<img src="/icon.svg" />` | Static, simple icon, no color theming needed |
| Inline `<svg>` | Need `currentColor`, animations, hover-driven stroke, accessible labels |

For icons inside hover-state buttons, prefer inline SVG so `:hover` color changes propagate.

---

## `<video>`

```html
<video class="hero_video"
       src="https://cdn.example.com/hero.mp4"
       autoplay
       muted
       loop
       playsinline
       poster="https://cdn.example.com/hero-poster.jpg">
</video>
```

```css
.hero_video {
  width: 100%;
  aspect-ratio: 16 / 9;
  border-radius: 16px;
  overflow: hidden;
}
```

The converter routes `<video>` to Framer's **Video Code Component** (`module:lRDHiNWNVWmE0lqtoVHP/.../Video.js:Video`), with the `src`, autoplay/muted/loop/poster passed through.

**Important sizing constraint**: don't set `aspectRatio: null` on a video CC, it'll fail to render. The converter handles aspect via Framer's CC engine internally; just write the standard CSS `aspect-ratio` and `width: 100%`.

### Multiple sources / formats

For multiple `<source>` children, use the primary source on `<video src="...">`. The converter only reads the top-level `src`.

---

## `<iframe>` (YouTube: Vimeo, generic)

The converter detects YouTube and Vimeo URLs and routes to dedicated Code Components.

### YouTube

```html
<iframe class="video_embed"
        src="https://www.youtube.com/embed/dQw4w9WgXcQ"
        allow="autoplay; encrypted-media; picture-in-picture"
        allowfullscreen></iframe>
```

```css
.video_embed {
  width: 100%;
  aspect-ratio: 16 / 9;
}
```

Routes to Framer's YouTube CC (`module:NEd4VmDdsxM3StIUbddO/.../YouTube.js:Youtube`).

URLs supported: `youtube.com/watch?v=...`, `youtube.com/embed/...`, `youtu.be/...`. The converter detects via the hostname.

### Vimeo

```html
<iframe class="video_embed"
        src="https://player.vimeo.com/video/76979871"
        allow="autoplay; fullscreen; picture-in-picture"
        allowfullscreen></iframe>
```

Routes to Framer's Vimeo CC (`module:0sWquksFr1YDkaIgrl9Z/.../Vimeo.js:default`).

### Generic embed (other iframes)

Anything that's not YouTube / Vimeo routes to Framer's **Embed CC** (mode `url`) with the src pre-filled:

```html
<iframe src="https://codesandbox.io/embed/abc123" allowfullscreen></iframe>
```

The Embed CC accepts a URL and renders it in Framer's iframe container.

---

## Embed inline HTML

For an arbitrary HTML/JS embed (e.g. a Twitter widget script, a Calendly inline embed), wrap in a recognized pattern:

```html
<iframe srcdoc='<script src="https://platform.twitter.com/widgets.js"></script><blockquote class="twitter-tweet">…</blockquote>'></iframe>
```

This routes to the Embed CC mode `html` with the inline content. Reliability varies by content, some scripts won't run inside Framer's sandboxed iframe.

For things like a Calendly schedule embed, prefer their official iframe URL (`https://calendly.com/.../inline`).

---

## Sizing patterns for media

### Full-width hero video (16:9)

```html
<div class="hero_visual">
  <video class="hero_video" src="..." autoplay muted loop playsinline></video>
</div>
```

```css
.hero_visual {
  width: 100%;
  border-radius: 24px;
  overflow: hidden;
}
.hero_video {
  width: 100%;
  aspect-ratio: 16 / 9;
}
```

### Square card image

```html
<div class="card_visual">
  <img src="..." alt="..." />
</div>
```

```css
.card_visual {
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 16px;
  overflow: hidden;
}
.card_visual img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
```

### Logo / icon at fixed size

```html
<img class="logo" src="/logo.svg" alt="Brand" />
```

```css
.logo {
  width: 120px;
  height: 32px;
  object-fit: contain;
}
```

---

## Don'ts

- **Don't mix `width` / `height` HTML attrs with conflicting CSS sizing**, pick one source. CSS wins for layout, HTML attrs help Framer reserve space.
- **Don't set `heightType: 2` (hug)** on video / iframe layers, the CC's internal rendering needs `widthType: 3 + heightType: 0` with aspectRatio. The converter handles this; don't override.
- **Don't load Lottie inline yet**, the converter has a `lottie` / `dotlottie` registry but doesn't auto-route from HTML. Coming in a future pass.
- **Don't use `background-image: url(video.mp4)`**, videos can't be backgrounds.
- **Don't set `controls` on a hero `<video>`** that should autoplay silently. Combine `autoplay muted loop playsinline` and omit `controls`.
