# Background Video

## Required Structure

```html
<div class="w-background-video hero-video"
  data-autoplay="true"
  data-loop="true"
  data-wf-ignore="true"
  data-video-urls="video.webm,video.mp4"
  data-poster-url="poster.jpg">
  <div class="hero-content">
    <h1>Hero Title</h1>
    <p>Subtitle text</p>
  </div>
</div>
```

## Data Attributes

| Attribute | Description |
|---|---|
| `data-video-urls` | Comma-separated video URLs (`.webm` preferred first) |
| `data-poster-url` | Fallback poster image URL |
| `data-autoplay` | `"true"` / `"false"` |
| `data-loop` | `"true"` / `"false"` |
| `data-wf-ignore` | `"true"` / `"false"` |

## Children

All non-video children are rendered on top of the video (overlay content, headings, buttons, etc.). `<video>` and `<noscript>` tags inside the wrapper are ignored — only the `data-*` attributes matter.

## Play/Pause Button (Optional)

```html
<div class="w-backgroundvideo-backgroundvideoplaypausebutton">
  <img src="pause-icon.svg" alt="Pause video" />
  <img src="play-icon.svg" alt="Play video" />
</div>
```

The button images are identified by their `alt` text: `"Pause video"` and `"Play video"`.

## Full Example with Play/Pause

```html
<div class="w-background-video hero-video"
  data-autoplay="true"
  data-loop="true"
  data-wf-ignore="true"
  data-video-urls="hero.webm,hero.mp4"
  data-poster-url="hero-poster.jpg">
  <div class="w-backgroundvideo-backgroundvideoplaypausebutton">
    <img src="pause.svg" alt="Pause video" />
    <img src="play.svg" alt="Play video" />
  </div>
  <div class="hero-overlay">
    <h1 class="hero_heading">Welcome</h1>
    <p class="hero_subtitle">Watch the video</p>
  </div>
</div>
```
