# Lightbox

## Required Structure

```html
<div class="w-lightbox">
  <img src="thumbnail.jpg" alt="Image description" class="lightbox-image" />
  <script type="application/json" class="w-json">{
    "items": [
      { "type": "image", "url": "full-size.jpg", "caption": "Image caption" }
    ],
    "group": "gallery-1"
  }</script>
</div>
```

## How It Works

The `w-lightbox` class creates a clickable lightbox element. The `<script class="w-json">` block defines the media items and gallery grouping.

### JSON Format

```json
{
  "items": [
    { "type": "image", "url": "full-size.jpg", "caption": "Optional caption" },
    { "type": "video", "url": "https://youtube.com/...", "caption": "" }
  ],
  "group": "gallery-name"
}
```

- `items`: Array of media objects. Each has `type` ("image" or "video"), `url`, and optional `caption`.
- `group`: Use the same group name on multiple lightboxes to enable gallery navigation between them.

### Children

Non-script children (typically an `<img>` for the thumbnail) display as the clickable trigger.

## Gallery Example

Multiple lightboxes with the same `group` form a navigable gallery:

```html
<div class="gallery-grid">
  <div class="w-lightbox gallery-item">
    <img src="thumb-1.jpg" alt="Photo 1" class="gallery-image" />
    <script type="application/json" class="w-json">{
      "items": [{ "type": "image", "url": "full-1.jpg", "caption": "Photo 1" }],
      "group": "portfolio"
    }</script>
  </div>
  <div class="w-lightbox gallery-item">
    <img src="thumb-2.jpg" alt="Photo 2" class="gallery-image" />
    <script type="application/json" class="w-json">{
      "items": [{ "type": "image", "url": "full-2.jpg", "caption": "Photo 2" }],
      "group": "portfolio"
    }</script>
  </div>
</div>
```
