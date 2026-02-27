## 2024-12-05 - Adding Loading State to Registration Form
**Learning:** Users need immediate feedback during potentially slow operations like sending emails to prevent double-submissions and uncertainty.
**Action:** Implemented a JavaScript listener on the registration form to disable the submit button and show a loading spinner with text change upon submission. This pattern should be replicated for all forms triggering email or database operations.
