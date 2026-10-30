# Changelog

[`v1.0.0-capstone-submission`](https://github.com/DMR-PersonalProject/multi-business-dashboard/releases/tag/v1.0.0-capstone-submission) is the project as submitted for the capstone. The entries below are post-submission improvements made after that tag.

## Before the baseline

These changes were made before the baseline commit, so they are already part of the tag. No values or personal details are listed.

- `.env.example`: real-looking values replaced with blanks or placeholders (database, Redis, mail, Google Sheet IDs, business API emails and passwords). All variable names are unchanged.
- `config/mail.php`: default mail from-address and from-name changed to neutral values.
- `database/seeders/UserSeeder.php`: demo account emails changed to `example.com` addresses.
- `app/Http/Controllers/Api/ApiTokenController.php`: removed a commented-out block that contained hardcoded account details.
- Personal photos, screenshots and possibly copyrighted stock images were moved out of the repository and replaced with generated placeholder images.
- `resources/views/general/navbar.blade.php` and `resources/views/roles/admin/account/create.blade.php`: image references updated to the placeholders.
- Account and navbar views: users without a profile picture now see the default avatar instead of a broken image.
- `.gitignore`: upload folders (`public/user_images`, `public/business_images`) ignored, except the default avatar, placeholders, the app logo and `.gitkeep` files.
- Removed an unused duplicate background image.

## Post-submission improvements

### 1. Tests can no longer wipe the real database

- **Problem:** the feature tests reset the database before each test, and nothing pointed them to a separate database. Running the tests deleted all data in the database configured in `.env`.
- **Files:** `phpunit.xml`, `tests/TestCase.php`, `tests/Unit/TestDatabaseGuardTest.php`, `tests/Feature/Security/TestDatabaseTest.php`
- **Change:** tests are forced onto an in-memory SQLite database. Before any migration runs, the test setup checks the database and stops with an error unless it is in-memory SQLite or its name ends in `_test`. Tests also use a random app key and no built Vite assets, so they run without `.env` or `npm run build`.
- **Commit:** _pending_

### 2. Users can no longer change their own role

- **Problem:** the "my account" settings form accepted a role value and saved it, so any logged-in user could give themselves the owner role and full access.
- **Files:** `app/Http/Controllers/UserAccountController.php`, `resources/views/settings/account-edit.blade.php`, `tests/Feature/Security/SelfRoleChangeTest.php`
- **Change:** the settings update no longer accepts or saves a role. The role is shown read-only on the settings page. Only the owner can change roles, from account management.
- **Commit:** _pending_

### 3. Data refresh and feedback import are owner-only, CSRF-protected and safe on failure

- **Problem:** the data refresh (`GET /api/refresh-data/{type}`) and the feedback imports (`GET /feedback/import-one`, `/feedback/import-two`, `/uddesignfeedback/fetch`) changed data on a simple GET request. The imports did not require a login, and the refresh worked for any logged-in user. They emptied tables before checking the new data, so a failed or malformed response could leave tables empty, and the refresh reported success even when it had failed.
- **Files:** `routes/web.php`, `routes/api.php`, `app/Http/Controllers/Api/DataController.php`, `app/Http/Controllers/FeedbackController.php`, `app/Http/Controllers/UddesignFeedbackController.php`, `app/Support/SheetFeedbackRows.php`, `resources/views/general/footer.blade.php`, `resources/views/uddesignfeedback/index.blade.php`, `tests/Feature/Security/DataRefreshTest.php`, `tests/Feature/Security/FeedbackImportTest.php`
- **Change:**
  - The routes are now `POST /refresh-data/{type}`, `POST /feedback/import-one`, `POST /feedback/import-two` and `POST /uddesignfeedback/fetch`. They require login and the owner role, need a CSRF token, and are limited to 5 requests per minute. `{type}` must be `uddesign`, `kuwago_one` or `kuwago_two`.
  - Every API endpoint and every sheet row is fetched and checked first. If anything is missing or invalid, nothing is deleted and an error message is shown.
  - Tables are replaced inside one database transaction (rows are deleted, not truncated, so the change can be rolled back).
  - The refresh buttons are shown only to owners. Other roles get a plain page reload. An invalid nested form in the footer was removed.
- **Commit:** _pending_

### 4. Routes that had no login check now require login and the right role

- **Problem:** some routes could be used without logging in: the deals pages (including creating, editing and deleting deals with client contact details), the Kuwago target-sale data, choosing which target sale is displayed, and the old standalone feedback pages and their date filters.
- **Files:** `routes/web.php`, `tests/Feature/Security/RouteAccessTest.php`
- **Change:**
  - Deals and the UDDesign feedback page and filter: login + owner, general or uddesign role.
  - Kuwago target-sale data, the Kuwago feedback page and filter: login + owner, general or kuwago role.
  - Choosing the displayed target sale: login + owner role.
- **Commit:** _pending_
