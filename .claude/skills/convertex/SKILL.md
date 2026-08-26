---
name: convertex
description: "Generate Webflow- or Framer-compatible HTML and CSS for Convertex. Triggers on: Convertex Capture, Webflow conversion, HTML to Webflow, recreate section, reproduce page, Client-First, Finsweet, w- classes, Framer conversion, HTML to Framer, recreate section for Framer, Framer page, Framer-friendly markup."
---

# Convertex — HTML & CSS Generation (Webflow + Framer)

**Language rule:** Always respond in the user's language. Detect it from their first message. These instructions are in English for precision; your replies must match the user's language.

Convertex converts HTML/CSS into **Webflow** OR **Framer**. The two targets have **fundamentally different and incompatible rules** — Webflow idioms break in Framer and vice versa. You pick exactly ONE target and follow ONLY its ruleset.

## Step 0 — Determine the target FIRST (before anything else)

Figure out Webflow vs Framer without interrogating the user about the target:
1. If the user states the target (Webflow or Framer), use it.
2. Otherwise, use the active project's tool from the Convertex MCP.
3. Only if neither is available AND the request is genuinely ambiguous, ask ONE short question: "Cible : Webflow ou Framer ?"

Never guess between Webflow and Framer — the rules are incompatible. Once the target is known, follow the matching RULES.md, which defines its own setup flow (Webflow asks a short pre-flight; Framer generates directly).

## Step 1 — Load ONLY the matching ruleset

- **Webflow** → read [`webflow/RULES.md`](webflow/RULES.md) and follow it. Read files under `webflow/` (elements, client-first, css, patterns) on demand.
- **Framer** → read [`framer/RULES.md`](framer/RULES.md) and follow it. Read files under `framer/` (layout, styling, typography, animations, elements, examples, patterns) on demand.

**Hard rule:** load and apply exactly ONE tree. Do NOT read or blend the other target's files. Applying Webflow rules to a Framer conversion (or the reverse) produces broken output. The full list of Webflow idioms that break Framer lives in [`framer/patterns/anti-patterns.md`](framer/patterns/anti-patterns.md).

## Asking questions — ALWAYS use AskUserQuestion

Whenever this skill needs to ask the user something (the Webflow/Framer target in Step 0, or the Webflow pre-flight questions), ALWAYS ask through the **AskUserQuestion** tool with structured options — never as plain prose. Batch related questions into a SINGLE AskUserQuestion call (it supports up to 4 questions at once). Only if AskUserQuestion is unavailable in the current environment, fall back to asking the same questions in one plain-text message.

- **Target question** → options: `Webflow` / `Framer`.
- **Webflow pre-flight** → see `webflow/RULES.md` (3 questions, one call).

## Delivery — push to the project, NEVER paste code in chat

For BOTH Webflow and Framer: do NOT write or paste the generated HTML / CSS / JS / TSX in the conversation. Push it into a Convertex project via the MCP. **Never auto-pick a project** — choose the destination WITH the user first:

1. Call `list_projects` to get the user's projects (each shows its tool: Webflow or Framer).
2. Ask which project via an **AskUserQuestion** call: propose the most relevant existing projects (prefer those whose tool matches the request, surface recent ones first) PLUS an option **"Create a new project"**. If there are more than a few, propose the top relevant ones and let the user pick "Other" to name another. Do NOT decide for them.
3. If they choose **Create a new project** → call `create_project` (with the right tool, Webflow or Framer) and use it. If they pick an existing project → use it; its tool confirms the Webflow/Framer target.
4. Pick the page inside that project: `list_pages`, then `create_page` for a new page or `update_page` / `patch_page` to modify an existing one (confirm via AskUserQuestion if ambiguous).
5. Push: `notify_sync_start()` → `create_page` / `update_page` / `patch_page`, then reply with a short summary of what you pushed (not the code).

The ONLY two exceptions where you output code in chat instead of pushing:
1. **The Convertex MCP is not installed / not connected.** Do not push blindly. Tell the user the MCP isn't connected and offer a choice: install the Convertex MCP (then you push), OR generate the code in the conversation this time.
2. **The user explicitly asks for the code in chat** (e.g. "donne-moi le code", "montre le code ici"). Then output the blocks as usual.

In every other case: push, don't display.

## Shared output conventions (both targets)

- **HTML block**: body content only. No `<html>`, `<head>`, `<body>`, or `<style>` tags. Start with the first element.
- **CSS block**: separate from HTML. Never use inline `style` attributes.
- **Preserve the user's copy verbatim — accents included.** Any ASCII-only constraint in a target's ruleset applies ONLY to JS/CSS comments, never to visible HTML text. Keep "Gérez", "simplicité", apostrophes and punctuation exactly as written in headings, paragraphs, links, button labels, `alt` and `aria-label`. Stripping accents from real content is a defect.

Everything else (selectors, sizing, components, animations, breakpoints, JS vs no-JS) is target-specific and lives in the matching `RULES.md`. When in doubt about a rule, it belongs to one target only — re-check which tree you are in.
