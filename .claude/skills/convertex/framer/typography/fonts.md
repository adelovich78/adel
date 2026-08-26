# Fonts catalog: Inter, Framer Sans, Google Fonts

Framer routes fonts through one of three providers depending on the family. The converter detects the family and emits the correct provider prefix in the `--font-selector` base64. **You don't write the prefix, just write the CSS family name correctly, the converter handles the rest.**

---

## Provider routing (auto-detected)

| Provider | Format in `--font-selector` | When the converter picks it |
|---|---|---|
| **Framer built-in** | bare `Inter` / `Inter-Bold` | Family is `Inter` |
| **Framer Sans (`FS;`)** | `FS;General Sans-semibold` | Family is in the curated FS list (below) |
| **Google Fonts (`GF;`)** | `GF;Roboto-medium` | Default, anything else |

If the font isn't actually on Google Fonts, Framer falls back to a system font and shows a yellow warning. The user can swap in 1 click in Framer's font picker. So **unknown fonts are a soft failure**, not a hard break.

---

## Framer built-in

- `Inter`, comes bundled with Framer. Default fallback.

```css
font-family: 'Inter', sans-serif;
font-weight: 400;                  /* → bare "Inter" face */
font-weight: 700;                  /* → "Inter-Bold" face */
```

**Quirk**: do not write `Inter-Regular` anywhere, Framer's registry has `Inter` (bare) for the default weight. The converter emits the bare form for `font-weight: 400`. Adding `-Regular` triggers a "font not recognized" yellow warning.

---

## Framer Sans (curated list)

These fonts are hosted on Framer's Sans service. Use them when the brand calls for a polished sans-serif beyond Inter:

- `Plus Jakarta Sans`
- `General Sans`
- `Satoshi`
- `Cabinet Grotesk`
- `Switzer`
- `Synonym`
- `Author`
- `Erode`
- `Explora`
- `HK Grotesk`
- `Boldonse`
- `Aspekta`
- `Aeonik`
- `Manrope`

```css
font-family: 'Plus Jakarta Sans', sans-serif;
font-weight: 600;                  /* → "FS;Plus Jakarta Sans-semibold" */
```

The list grows as the converter adds support. If the brand uses a Framer Sans font not in this list, write it as you'd write any other family (the converter will default to GF, and Framer's font picker can correct it after paste).

---

## Google Fonts (default fallback)

Anything not in the Inter / Framer Sans lists routes to Google Fonts. Common picks for landing pages:

- `Roboto`, `Open Sans`, `Lato`, `Montserrat`, `Poppins`, workhorses
- `DM Sans`, `Inter` (already built-in), `Work Sans`, `Sora`, `Outfit`, modern sans
- `Playfair Display`, `Cormorant Garamond`, `EB Garamond`, serif display
- `Space Grotesk`, `IBM Plex Sans`, `Geist`, geometric / mono-flavored
- `Fraunces`, `Bricolage Grotesque`, variable / playful

```css
font-family: 'DM Sans', sans-serif;
font-weight: 500;                  /* → "GF;DM Sans-medium" */
```

---

## Family stack format

Always write the family name in single quotes, with a generic fallback:

```css
font-family: 'Plus Jakarta Sans', sans-serif;
font-family: 'DM Sans', sans-serif;
font-family: 'Inter', sans-serif;
font-family: 'Playfair Display', serif;
font-family: 'JetBrains Mono', monospace;
```

The converter takes the **first** family name as the primary; the fallback is ignored at the Framer level (Framer has its own font fallback engine), but keeps the CSS sane.

**Don't** write multiple custom families in the stack:

```css
/* ❌ converter only takes the first; second is silently dropped */
font-family: 'Inter', 'Helvetica Neue', sans-serif;

/* ✅ pick one */
font-family: 'Inter', sans-serif;
```

---

## Numeric weights

Always use numeric weights (100, 200, 300, 400, 500, 600, 700, 800, 900). Framer converts keyword weights but it's an extra round-trip:

```css
font-weight: 400;                  /* ✅, explicit, maps directly */
font-weight: normal;               /* OK but maps to 400 internally */
font-weight: 600;                  /* ✅ */
font-weight: bold;                 /* OK but maps to 700 */
```

Each numeric weight maps to a Framer face name:

| Weight | Inter (built-in) | Other (FS / GF) |
|---|---|---|
| 100 | `Inter-Thin` | `<family>-thin` |
| 200 | `Inter-ExtraLight` | `<family>-extralight` |
| 300 | `Inter-Light` | `<family>-light` |
| 400 | `Inter` (bare) | `<family>-regular` |
| 500 | `Inter-Medium` | `<family>-medium` |
| 600 | `Inter-SemiBold` | `<family>-semibold` |
| 700 | `Inter-Bold` | `<family>-bold` |
| 800 | `Inter-ExtraBold` | `<family>-extrabold` |
| 900 | `Inter-Black` | `<family>-black` |

If you use a weight a font doesn't actually ship with (e.g. Plus Jakarta Sans-thin doesn't exist), Framer falls back to the closest available weight.

---

## Italic

```css
font-style: italic;                /* + the weight you've set */
```

Maps to the italic face: `Inter-Italic`, `Inter-BoldItalic`, `FS;General Sans-bold italic`, `GF;Roboto-medium italic`.

---

## Picking a font for a brief

Default workflow:
1. **No brand directive** → `Inter`. Always works, no fallback risk.
2. **Brand says "modern, geometric"** → `Plus Jakarta Sans`, `Sora`, or `DM Sans`.
3. **Brand says "luxury, serif"** → `Playfair Display`, `Fraunces`, or `Cormorant Garamond`.
4. **Brand says "tech, mono"** → `JetBrains Mono`, `Geist Mono`, or `IBM Plex Mono`.
5. **Brand provides a specific font** → use it. If it's outside Framer's curated lists, the user gets a yellow warning and swaps in 1 click, that's fine.

For a landing page, **one or two families maximum**. A common pattern: one display family for headings + one neutral sans for body. Don't introduce a third unless the brief explicitly asks.

---

## Don'ts

- **Don't use system font stacks** like `font-family: -apple-system, BlinkMacSystemFont, …`, these don't translate to Framer.
- **Don't use `@font-face` rules**, Framer manages its own font loading. Custom `@font-face` is dropped by the converter.
- **Don't rely on `font` shorthand** (`font: 1rem/1.5 'Inter'`). Always longhand.
- **Don't load Google Fonts via `<link>`**, Framer fetches them itself based on the `--font-selector`.
