# Video (YouTube / Vimeo Embed)

## Required Structure

```html
<div class="w-video hero_video">
  <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="940" height="528" title="Video title" frameborder="0" allowfullscreen></iframe>
</div>
```

The wrapper `<div>` must have the `w-video` class. Additional classes (e.g. `hero_video`) are applied as combo classes on the Video element in Webflow.

## Supported Providers

### YouTube

Use one of these URL formats in the iframe `src`:

| Format | Example |
|---|---|
| Embed | `https://www.youtube.com/embed/VIDEO_ID` |
| Watch | `https://www.youtube.com/watch?v=VIDEO_ID` |
| Short | `https://youtu.be/VIDEO_ID` |
| No-cookie | `https://www.youtube-nocookie.com/embed/VIDEO_ID` |

### Vimeo

| Format | Example |
|---|---|
| Standard | `https://vimeo.com/VIDEO_ID` |
| Player embed | `https://player.vimeo.com/video/VIDEO_ID` |

## Aspect Ratio

The aspect ratio is derived from the iframe's `width` and `height` attributes. Defaults: `940 x 528` (roughly 16:9).

To force a specific aspect ratio, set `width` and `height` on the `<iframe>`:

```html
<!-- 16:9 -->
<iframe src="..." width="1920" height="1080" ...></iframe>

<!-- 4:3 -->
<iframe src="..." width="940" height="705" ...></iframe>
```

## Bare Iframe (No Wrapper)

A standalone `<iframe>` with a YouTube or Vimeo `src` is also converted into a Video element automatically:

```html
<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="940" height="528" title="Product demo" frameborder="0" allowfullscreen></iframe>
```

This is useful when embedding a video inline without needing a separate wrapper with custom classes.

## Full Example

```html
<section class="section_demo">
  <div class="padding-global padding-section-large">
    <div class="container-large">
      <div class="demo_content">
        <h2 class="demo_heading">Watch the demo</h2>
        <div class="w-video demo_video">
          <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="940" height="528" title="Product demo" frameborder="0" allowfullscreen></iframe>
        </div>
      </div>
    </div>
  </div>
</section>
```

## Important Notes

- **Do NOT use `<video>` HTML tag** for YouTube/Vimeo embeds. The `<video>` tag is for self-hosted HTML5 video. Use `w-video` + `<iframe>` for embed providers.
- **Do NOT confuse with Background Video** (`w-background-video`). Background Video is a full-bleed ambient video using self-hosted files. `w-video` is an embedded player from YouTube or Vimeo.
- The `title` attribute on the `<iframe>` sets the video title in Webflow's Video settings.
