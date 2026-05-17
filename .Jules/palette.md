## 2024-05-14 - Loading state button breaks POST variables
**Learning:** When disabling a named submit button via JavaScript to show a loading state, the browser omits the button's name and value from the `$_POST` payload. To prevent breaking backend logic that relies on checking `isset($_POST['button_name'])`, a corresponding `<input type="hidden" name="[button_name]" value="1">` must be added to the form.
**Action:** When adding loading states to submit buttons that use a `name` attribute, ensure to add a hidden input with the same name.

## $(date +%Y-%m-%d) - Missing Loading State on Admin Forms
**Learning:** Admin forms like `update-group-form` can be submitted multiple times without visual feedback, leading to confusion. Adding a visual loading state with `disabled=true` and `cursor=wait` along with a spinner (`<span class="loading" aria-hidden="true"></span>`) provides necessary feedback.
**Action:** When creating or modifying forms, always ensure the submit button has a visual loading state triggered via JavaScript on the `submit` event.

## 2024-05-14 - Loading state button breaks POST variables
**Learning:** When disabling a named submit button via JavaScript to show a loading state, the browser omits the button's name and value from the `$_POST` payload. To prevent breaking backend logic that relies on checking `isset($_POST['button_name'])`, a corresponding `<input type="hidden" name="[button_name]" value="1">` must be added to the form.
**Action:** When adding loading states to submit buttons that use a `name` attribute, ensure to add a hidden input with the same name.

## 2024-05-20 - Missing Loading State on Admin Forms
**Learning:** Admin forms like `update-group-form` can be submitted multiple times without visual feedback, leading to confusion. Adding a visual loading state with `disabled=true` and `cursor=wait` along with a spinner (`<span class="loading" aria-hidden="true"></span>`) provides necessary feedback.
**Action:** When creating or modifying forms, always ensure the submit button has a visual loading state triggered via JavaScript on the `submit` event.

## 2024-05-24 - Context for Optional Communication Fields
**Learning:** In this application, optional contact fields (like email for receiving Wichtel assignments) are actually critical for the full experience. Leaving them without context causes users to skip them. The `.form-hint` class combined with `aria-describedby` is the standard reusable pattern in this design system for providing this context accessibly.
**Action:** When adding optional fields that impact downstream notifications, always pair them with a `.form-hint` description linked via `aria-describedby` to explain the "why".

## 2026-03-12 - Extracting Common JavaScript Functions
**Learning:** Significant code duplication was found in inline scripts across multiple PHP files for common tasks like clipboard copying, toast notifications, and FAQ toggling. Centralizing these into a single `main.js` library improves maintainability and ensures consistent accessibility features (like ARIA roles for notifications).
**Action:** Always check for existing utility functions in `public/js/main.js` before implementing inline scripts for UI interactions.
## 2024-05-25 - ARIA Toggling on Mobile Navigation
**Learning:** Mobile navigation toggle buttons (hamburger menus) often lack proper ARIA attributes to indicate their state to screen readers. Adding `aria-expanded` and toggling it via JavaScript (along with `aria-label` and `aria-controls`) significantly improves accessibility for these components.
**Action:** When working with toggleable UI elements like mobile menus or dropdowns, ensure `aria-expanded` is present and correctly toggled by the associated JavaScript logic.

## 2024-05-24 - HTML5 Date Validation
**Learning:** Implementing the 'min' attribute on 'date' inputs is a quick, native way to prevent users from selecting past dates for future events like gift exchanges, improving form validation UX.
**Action:** Use native HTML validation constraints (like 'min' and 'max') wherever possible before relying on complex JS or backend-only validation to provide immediate user feedback.

## $(date +%Y-%m-%d) - Decorative Emoji Accessibility
**Learning:** Decorative emojis used as icons (e.g., `<span class="idea-icon">☕</span>` or inside buttons) lack built-in accessibility safeguards, leading to screen readers announcing literal descriptions like "smiling face" which interrupts the reading flow when the icon is purely visual.
**Action:** Always add `aria-hidden="true"` to any `span` or `div` containing purely decorative emojis to prevent redundant or confusing screen reader announcements.

## $(date +%Y-%m-%d) - Confirmation Dialog for Non-Destructive Actions
**Learning:** Actions that are technically non-destructive but have immediate, irreversible communicative side effects (like triggering an algorithm that sends out mass emails) can cause user anxiety if they lack a confirmation step. Users may accidentally click the button or not realize the action is final.
**Action:** Always add an `onsubmit` confirmation dialog (or a similar UI verification step) to forms that trigger mass communication or other irreversible actions, even if the action doesn't delete data.
## $(date +%Y-%m-%d) - Providing Context for Optional Fields with .form-hint
**Learning:** Optional fields in forms (like budget, description, and gift exchange date) often lack context, which can cause users to skip them or input incorrect information. Adding explanatory text helps users understand the purpose of these fields and improves the overall form-filling experience. By pairing this text with the `.form-hint` class and associating it with the input field using `aria-describedby`, we ensure both visual clarity and accessibility for screen reader users.
**Action:** Always provide context for optional form fields that could benefit from an explanation. Use the `.form-hint` class for the explanatory text and link it to the input field using `aria-describedby`.

## 2026-04-08 - Dynamic aria-current for Navigation
**Learning:** Global navigation menus often lack structural context for screen readers to indicate the currently active page. Relying only on visual cues or page titles forces users to guess their location within the navigation hierarchy. Adding `aria-current="page"` dynamically based on the current route solves this accessibly.
**Action:** Always dynamically apply `aria-current="page"` to the active link in navigation menus to provide explicit context to assistive technologies.

## 2024-06-25 - Accessibility of Loading States on Submit Buttons
**Learning:** When disabling a form submit button to indicate a loading state via JavaScript (e.g., inside `handleFormSubmit`), explicitly setting the `aria-disabled="true"` and `aria-busy="true"` attributes on the button element is necessary to properly inform screen readers of the ongoing background process and inactive state. This prevents confusion for assistive technology users who might not perceive visual cues like a spinner or cursor change.
**Action:** Always add `aria-disabled="true"` and `aria-busy="true"` when programmatically disabling interactive elements to indicate an ongoing asynchronous operation.
