# CSS Property Reference for Webflow

Detailed writing guide for CSS properties. For rules and selectors, see [RULES.md](../RULES.md).

## Spacing

```css
/* All forms work */
padding: 2rem;
padding: 1rem 2rem;
padding-top: 1rem;
margin: 0 auto;
gap: 1.5rem;
```

## Borders

```css
/* Per-side shorthand — preferred */
border-top: 1px solid #e5e7eb;
border-bottom: 2px solid #000;

/* All sides — use 3 separate properties */
border-width: 1px;
border-style: solid;
border-color: #e5e7eb;

/* Border radius */
border-radius: 0.5rem;
border-top-left-radius: 1rem;
border-top-right-radius: 1rem;
```

## Backgrounds & Gradients

```css
/* Color */
background-color: #1a1a2e;

/* Linear gradient */
background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Radial gradient */
background-image: radial-gradient(circle at center, #667eea 0%, transparent 70%);

/* Image */
background-image: url('hero-bg.jpg');
background-size: cover;
background-position: center;
background-repeat: no-repeat;

/* Shorthand */
background: linear-gradient(to bottom, rgba(0,0,0,0.6), transparent);
```

## Transitions

```css
/* Shorthand — preferred */
transition: color 0.3s ease, transform 0.2s ease-in-out;
transition: all 0.3s ease;

/* Longhand */
transition-property: opacity, transform;
transition-duration: 300ms, 200ms;
transition-timing-function: ease, ease-in-out;
```

## Flexbox

```css
display: flex;
flex-direction: column;
align-items: center;
justify-content: space-between;
gap: 1.5rem;
flex-wrap: wrap;
```

## Grid

```css
/* Standard grid */
display: grid;
grid-template-columns: repeat(3, 1fr);
gap: 2rem;

/* Grid placement */
grid-area: 1 / 1 / 3 / 4;
grid-column: span 2;
grid-row: 1 / 3;
```

## Typography

```css
font-family: 'Inter', sans-serif;
font-size: 1rem;
font-weight: 600;
line-height: 1.5;
letter-spacing: -0.02em;
text-transform: uppercase;
text-decoration: none;
color: #1a1a2e;
text-shadow: 0 2px 4px rgba(0,0,0,0.3);
-webkit-text-stroke-width: 1px;
-webkit-text-stroke-color: #000;
```

## Filters & Effects

```css
opacity: 0.9;
mix-blend-mode: overlay;
filter: blur(4px) brightness(0.8);
backdrop-filter: blur(10px) saturate(1.5);
transform: translateY(-4px) scale(1.02);
box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
```

## Sizing & Layout

```css
/* Dimensions */
width: 100%;
max-width: 80rem;
min-height: 100vh;

/* Object fit — for images and videos */
object-fit: cover;
object-fit: contain;
aspect-ratio: 16 / 9;

/* Positioning */
position: relative;
position: absolute;
position: sticky;
top: 0;
z-index: 10;
inset: 0;

/* Overflow */
overflow: hidden;
overflow: auto;
overflow-x: hidden;
overflow-y: auto;
```

## Cursor

```css
cursor: pointer;
cursor: default;
cursor: not-allowed;
```

## Animations & Keyframes

**Keyframes must go in a ghost block embed, not in the standalone CSS.**

```html
<div class="w-embed"><style>
  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .hero_heading { animation: fadeInUp 0.6s ease forwards; }
</style></div>
```

Prefer CSS `transition` over `animation` wherever possible — transitions are natively supported without ghost blocks.

## Responsive Pattern

Desktop-first base styles, override downward. Only include properties that change:

```css
.hero_heading {
  font-size: 4rem;
  line-height: 1.1;
}

@media (max-width: 991px) {
  .hero_heading { font-size: 3rem; }
}

@media (max-width: 479px) {
  .hero_heading { font-size: 2rem; }
}
```
