# React Code Components

For interactive UI that Framer's native primitives cannot express, the page can host a **Code Component slot** that the user fills with a React/TSX component pasted into Framer's Code Files panel.

---

## The Split Principle (read this first, every time)

**A Code Component must contain the bare minimum that cannot be expressed natively in Framer. Everything else stays in the page's HTML and CSS.**

When a complex design lands in front of you, think like a Framer designer would: "What's actually unreachable from Framer's native toolbox, and what would I build with normal layers and animations?" Build the second category natively. Isolate ONLY the first in a slot.

Framer's native toolbox already covers a lot:
- Layout: flex / grid stacks, breakpoints (1199 / 810), padding, gap, sizing (fill / hug / fixed / relative)
- Text: typography per element, gradients (via CSS), `data-framer-text-effect` (per-character / per-word / per-line appear with spring or tween, configurable stagger and direction)
- Hover / Press: `:hover` and `:active` CSS, mapped to `whileHover*` / `whileTap*`
- Mount / scroll appear: `data-framer-enter` and `data-framer-scroll-appear` with presets, distances, durations, easings, springs, staggers
- Loops: `data-framer-loop` (rotate, pulse, bounce, shake)
- Continuous motion: `data-framer-ticker` (horizontal or vertical), `data-framer-drag`
- Media: `<video>`, YouTube / Vimeo `<iframe>`, generic embeds, inline SVG, all routed to Framer's built-in Code Components automatically

A Code Component is justified only when ALL of the following apply:
- The effect cannot be reached by stacking the markers above
- AND the effect is contained (a single visual feature, not a whole hero)
- AND keeping it in a component lets the rest of the page stay editable in Framer's Designer

**Concrete examples of correct splits:**

| Whole design | Native page (HTML/CSS + data-framer-*) | Code Component slot |
|---|---|---|
| Hero with 36 animated SVG paths in the background, big animated title, CTA | section + h1 with `data-framer-text-effect` + `<a>` CTA with `:hover` | Just the FloatingPaths SVG (absolute background layer) |
| Pricing page with a monthly/yearly toggle that switches numbers | section + plan card grid + toggle UI shell | Just the small "toggle + price state" piece |
| Signup form with 3 visual steps and validation | section + intro copy + visual progress dots | Multi-step form component |
| Landing with a marquee logo strip, hover-lift cards, scroll-fade-in sections | All native: `data-framer-ticker`, `:hover`, `data-framer-scroll-appear` | Nothing — no slot needed |
| Hero with a counter that ticks from 0 to 12,500 on viewport entry | section + h1 + everything around | Just the animated number |

**Bad splits to avoid:**
- Whole hero as one big Code Component (kills editability, defeats Framer's design tool)
- Code Component for a fade-up — that's `data-framer-enter="fade-up"` natively
- Code Component for a marquee — that's `data-framer-ticker="true"` natively
- Code Component for a hover scale — that's `transform: scale(1.05)` in `:hover` natively

Once you're sure a Code Component is needed, keep its surface area minimal: pass props through `addPropertyControls` so the user can still tweak text / colors / sizes / links from Framer's property panel.

---

Triggers for proposing a Code Component instead of plain HTML/CSS:
- Multi-step forms with input validation and progress state
- Custom carousels or sliders with non-standard transitions
- Live counters, animated stats, data viz
- Anything stateful or controller-driven that would need JS in Webflow

Framer's native effects already cover marquees (`data-framer-ticker`), loops, drags, scroll appears, hovers and presses, so **don't propose a Code Component for purely visual motion**, use the data-* markers instead. See [Animations](../animations/).

---

## Output contract

When a section needs a Code Component, you produce **two synchronized outputs**:

1. **HTML block (page structure)** with a slot placeholder marker where the component should mount:
   ```html
   <section class="signup_section">
     <div class="signup_grid">
       <div class="signup_intro">
         <h2 class="signup_heading">Join the waitlist</h2>
         <p class="signup_lead">Three quick steps, zero spam.</p>
       </div>
       <div class="signup_slot"
            data-framer-component-slot="signup-form"
            data-framer-component-label="Multi-step signup form">
       </div>
     </div>
   </section>
   ```

2. **Component block (TSX)** pushed to the dedicated "Composant" panel via `update_page` / `patch_page`'s `js` field (the panel is repurposed to React/TSX when the project tool is `framer`):
   ```tsx
   import { useState } from "react"
   import { addPropertyControls, ControlType } from "framer"

   export default function SignupForm(props: { accent?: string }) {
     const [step, setStep] = useState(0)
     // ...
     return (
       <form>
         {/* component markup */}
       </form>
     )
   }

   addPropertyControls(SignupForm, {
     accent: { type: ControlType.Color, defaultValue: "#0099FF" },
   })
   ```

---

## Slot marker attributes

| Attribute | Required | Purpose |
|---|---|---|
| `data-framer-component-slot` | yes | Stable identifier for the slot (e.g. `signup-form`, `pricing-toggle`). Used in the placeholder label so the user can match it with the right Code File. |
| `data-framer-component-label` | recommended | Human-readable label shown in the placeholder (e.g. "Multi-step signup form"). |

The CSS for the slot div should reserve the visual footprint the component will occupy (size, padding, background, border-radius) so the layout looks consistent before AND after the component is wired in Framer:

```css
.signup_slot {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 480px;
  padding: 32px;
  background: #0f0f0f;
  border-radius: 16px;
  border: 1px solid #2a2a2a;
}
```

---

## TSX rules

- **Default export** the component, Framer's Code File panel expects this.
- **Props via `addPropertyControls`** for anything the user might want to tweak in the Framer property panel. Use `ControlType.Color`, `String`, `Number`, `Boolean`, `Enum`, `File`, `Image`, `Link`.
- **Hooks are fine** (`useState`, `useEffect`, `useMemo`). Avoid third-party packages that aren't already in Framer's runtime, the user can't `npm install` per-Code-File.
- **No DOM hacks** (no `document.querySelector` reaching outside the component), Framer renders Code Components in isolation.
- **Style with inline `style={{ ... }}`** or a single CSS-in-JS approach inside the component. Don't reference external classes from the page CSS, they don't reach the Code Component's render context.

---

## Two-paste user flow (current V1)

When the user converts a page that contains a slot, the Convertex UI guides them through two paste actions:

1. **Paste 1**: clipboard contains the Framer page layout. The user pastes in their Framer page. The slot appears as a Framer-native FrameNode with a dashed border and the slot label.
2. **Paste 2**: clipboard contains the TSX. The user goes to Framer → Assets → New Code File → paste → save. The component appears in Assets.
3. **Manual wiring** (one-time in Framer): the user drags the Code Component from Assets onto the slot to replace the placeholder.

This is intentionally explicit so users learn the mapping. A future Convertex Framer plugin will automate steps 2 and 3.

---

## When NOT to propose a Code Component

- The interaction can be expressed with `data-framer-*` markers, marquees, scrolls, loops, hovers, drags, tickers, text effects. Always check [Animations](../animations/) first.
- The element is a media embed, `<video>`, `<iframe>`, `<svg>` are already routed to Framer's built-in Video / YouTube / Vimeo / Embed components by the converter. See [Media](./media.md).
- The component would only be used once and is mostly styling. Inline it in HTML/CSS, simpler for the user.

---

## Example: pricing toggle (monthly / yearly)

When the toggle drives a state change that re-renders prices, a Code Component is the right call.

```html
<section class="pricing_section">
  <div class="pricing_header">
    <h2 class="pricing_heading">Simple pricing</h2>
    <p class="pricing_lead">Switch between billing cycles.</p>
  </div>
  <div class="pricing_slot"
       data-framer-component-slot="pricing-toggle-grid"
       data-framer-component-label="Pricing plans with monthly/yearly toggle">
  </div>
</section>
```

```css
.pricing_section {
  padding: 96px 24px;
  background: #0a0a0a;
  display: flex;
  flex-direction: column;
  gap: 64px;
  align-items: center;
}
.pricing_slot {
  width: 100%;
  max-width: 1120px;
  min-height: 560px;
}
```

```tsx
import { useState } from "react"
import { addPropertyControls, ControlType } from "framer"

const PLANS = [
  { name: "Starter", monthly: 19, yearly: 15 },
  { name: "Pro",     monthly: 49, yearly: 39 },
  { name: "Team",    monthly: 99, yearly: 79 },
]

export default function PricingToggleGrid(props: { accent?: string }) {
  const [cycle, setCycle] = useState<"monthly" | "yearly">("monthly")
  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 32 }}>
      <div /* toggle UI */>
        <button onClick={() => setCycle("monthly")}>Monthly</button>
        <button onClick={() => setCycle("yearly")}>Yearly</button>
      </div>
      <div /* plan cards */>
        {PLANS.map(p => (
          <article key={p.name}>
            <h3>{p.name}</h3>
            <p>${cycle === "monthly" ? p.monthly : p.yearly}/mo</p>
          </article>
        ))}
      </div>
    </div>
  )
}

addPropertyControls(PricingToggleGrid, {
  accent: { type: ControlType.Color, defaultValue: "#0099FF" },
})
```
