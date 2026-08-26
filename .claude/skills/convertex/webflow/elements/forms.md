# Forms

## Required Structure

A Webflow form needs a 3-part wrapper: the form wrapper (`div.w-form`), the form itself (`<form>`), and success/error states.

```html
<div class="w-form">
  <form name="Contact Form" method="get">
    <!-- form fields go here -->
    <input type="submit" value="Send" class="submit-button" />
  </form>
  <div class="w-form-done"><div>Thank you!</div></div>
  <div class="w-form-fail"><div>Oops, something went wrong.</div></div>
</div>
```

**Always include the full wrapper** with `w-form-done` and `w-form-fail` for proper success/error states and wrapper styling control.

## Input Types

All text-like inputs use `<input>` with the appropriate `type`:

| HTML `type` | Notes |
|---|---|
| `text` (or omitted) | Default text input |
| `email` | Email validation |
| `password` | Password field |
| `tel` | Phone number |
| `number` | Numeric input |
| `url` | URL input |

```html
<input type="email" name="email" placeholder="you@example.com" required class="text-field" />
```

Key attributes: `name` (required), `placeholder`, `maxlength`, `id`, `required`.

## Checkbox

```html
<label class="w-checkbox checkbox-field">
  <input type="checkbox" name="agree" class="w-checkbox-input" />
  <span class="w-form-label">I agree to the terms</span>
</label>
```

## Radio Button

```html
<label class="w-radio radio-field">
  <input type="radio" name="choice" value="A" class="w-radio-input" />
  <span class="w-form-label">Option A</span>
</label>
```

### Boolean attributes

`required`, `checked`, `disabled`, `autofocus` are detected by presence, not value. Write `<input required>`, not `required="true"`.

## Select Dropdown

```html
<select name="country" class="select-field">
  <option value="">Select one...</option>
  <option value="fr">France</option>
  <option value="us">United States</option>
</select>
```

## Textarea

```html
<textarea name="message" placeholder="Your message" class="text-area"></textarea>
```

## Submit Button

```html
<input type="submit" value="Send" class="submit-button" />
```

Or:

```html
<button type="submit" class="submit-button">Send</button>
```

**Important:** A `<button>` without `type="submit"` becomes a regular button, NOT a form submit. Only `type="submit"` triggers form submission behavior.

## Complete Example

```html
<div class="w-form">
  <form name="Contact" method="get">
    <label for="name" class="field-label">Name</label>
    <input type="text" name="name" id="name" placeholder="John Doe" required class="text-field" />

    <label for="email" class="field-label">Email</label>
    <input type="email" name="email" id="email" placeholder="john@example.com" required class="text-field" />

    <label for="phone" class="field-label">Phone</label>
    <input type="tel" name="phone" id="phone" placeholder="+33 6..." class="text-field" />

    <label for="subject" class="field-label">Subject</label>
    <select name="subject" id="subject" class="select-field">
      <option value="">Choose...</option>
      <option value="general">General Inquiry</option>
      <option value="support">Support</option>
    </select>

    <label for="message" class="field-label">Message</label>
    <textarea name="message" id="message" placeholder="Your message..." required class="text-area"></textarea>

    <label class="w-checkbox checkbox-field">
      <input type="checkbox" name="newsletter" class="w-checkbox-input" />
      <span class="w-form-label">Subscribe to newsletter</span>
    </label>

    <input type="submit" value="Send Message" class="submit-button" />
  </form>
  <div class="w-form-done"><div>Thank you! Your message has been sent.</div></div>
  <div class="w-form-fail"><div>Oops! Something went wrong.</div></div>
</div>
```

## Styling Tips

```css
.text-field {
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
  padding-left: 1rem;
  padding-right: 1rem;
  border-width: 1px;
  border-style: solid;
  border-color: #d1d5db;
  border-radius: 0.5rem;
  font-size: 1rem;
  width: 100%;
}

.text-field:focus {
  border-color: #2563eb;
  outline: none;
}

.field-label {
  font-weight: 500;
  margin-bottom: 0.5rem;
  display: block;
}

.submit-button {
  background-color: #2563eb;
  color: #fff;
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
  padding-left: 2rem;
  padding-right: 2rem;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  width: 100%;
}

.submit-button:hover {
  background-color: #1d4ed8;
}
```
