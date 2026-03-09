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
