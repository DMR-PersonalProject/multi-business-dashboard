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
- **Commit:** [`bd5873d`](https://github.com/DMR-PersonalProject/multi-business-dashboard/commit/bd5873dcb65a83ab68c812a7d1feeea5eeb327bd)

### 2. Users can no longer change their own role

- **Problem:** the "my account" settings form accepted a role value and saved it, so any logged-in user could give themselves the owner role and full access.
- **Files:** `app/Http/Controllers/UserAccountController.php`, `resources/views/settings/account-edit.blade.php`, `tests/Feature/Security/SelfRoleChangeTest.php`
- **Change:** the settings update no longer accepts or saves a role. The role is shown read-only on the settings page. Only the owner can change roles, from account management.
- **Commit:** [`d8efb8d`](https://github.com/DMR-PersonalProject/multi-business-dashboard/commit/d8efb8d6d2ce3ca6b9088b3b28f91688bf841bef)

### 3. Data refresh and feedback import are owner-only, CSRF-protected and safe on failure

- **Problem:** the data refresh (`GET /api/refresh-data/{type}`) and the feedback imports (`GET /feedback/import-one`, `/feedback/import-two`, `/uddesignfeedback/fetch`) changed data on a simple GET request. The imports did not require a login, and the refresh worked for any logged-in user. They emptied tables before checking the new data, so a failed or malformed response could leave tables empty, and the refresh reported success even when it had failed.
- **Files:** `routes/web.php`, `routes/api.php`, `app/Http/Controllers/Api/DataController.php`, `app/Http/Controllers/FeedbackController.php`, `app/Http/Controllers/UddesignFeedbackController.php`, `app/Support/SheetFeedbackRows.php`, `resources/views/general/footer.blade.php`, `resources/views/uddesignfeedback/index.blade.php`, `tests/Feature/Security/DataRefreshTest.php`, `tests/Feature/Security/FeedbackImportTest.php`
- **Change:**
  - The routes are now `POST /refresh-data/{type}`, `POST /feedback/import-one`, `POST /feedback/import-two` and `POST /uddesignfeedback/fetch`. They require login and the owner role, need a CSRF token, and are limited to 5 requests per minute. `{type}` must be `uddesign`, `kuwago_one` or `kuwago_two`.
  - Every API endpoint and every sheet row is fetched and checked first. If anything is missing or invalid, nothing is deleted and an error message is shown.
  - Tables are replaced inside one database transaction (rows are deleted, not truncated, so the change can be rolled back).
  - The refresh buttons are shown only to owners. Other roles get a plain page reload. An invalid nested form in the footer was removed.
- **Commit:** [`70e9623`](https://github.com/DMR-PersonalProject/multi-business-dashboard/commit/70e96239808b8148478427992dca57d01b00bb27)

### 4. Routes that had no login check now require login and the right role

- **Problem:** some routes could be used without logging in: the deals pages (including creating, editing and deleting deals with client contact details), the Kuwago target-sale data, choosing which target sale is displayed, and the old standalone feedback pages and their date filters.
- **Files:** `routes/web.php`, `tests/Feature/Security/RouteAccessTest.php`
- **Change:**
  - Deals and the UDDesign feedback page and filter: login + owner, general or uddesign role.
  - Kuwago target-sale data, the Kuwago feedback page and filter: login + owner, general or kuwago role.
  - Choosing the displayed target sale: login + owner role.
- **Commit:** [`0e0a5c3`](https://github.com/DMR-PersonalProject/multi-business-dashboard/commit/0e0a5c3cc1a825ff091eaa90d16c8ae7422a3c47)

### 5. Demo accounts no longer use hardcoded weak passwords

- **Problem:** the demo accounts were created with short, hardcoded passwords that are visible in the repository, so anyone could log in as the owner on any install that ran the seeder. The seeder could also run in production, and `php artisan db:seed` did not create the demo accounts at all.
- **Files:** `database/seeders/UserSeeder.php`, `database/seeders/DatabaseSeeder.php`, `.env.example`, `tests/Feature/Security/UserSeederTest.php`
- **Change:** the demo password is read from `SEED_USER_PASSWORD`. If that is empty, a random 16-character password is generated and printed once in the seeding output. The seeder refuses to run when `APP_ENV=production`, can be run again without creating duplicates, and is now called by `DatabaseSeeder`. `.env.example` has an empty `SEED_USER_PASSWORD` entry.
- **Commit:** [`2601e18`](https://github.com/DMR-PersonalProject/multi-business-dashboard/commit/2601e1834409e48874c68e4d840f76b123e45637)

### 6. Safer image uploads

- **Problem:** uploaded pictures (user photos, business images and logos) were saved in web-served folders under the name and extension chosen by the uploader, with no size limit and no explicit list of allowed formats.
- **Files:** `app/Support/ImageUpload.php`, `config/filesystems.php`, `app/Http/Controllers/AccountController.php`, `app/Http/Controllers/UserAccountController.php`, `app/Http/Controllers/BusinessInfoController.php`, `tests/Feature/Security/UploadTest.php`
- **Change:** all three upload places use one helper. Only JPEG, PNG, GIF and WebP images up to 2 MB are accepted, checked against the file's content (SVG is not accepted). New uploads are stored under a random 40-character name with the extension taken from the file's content, through a new `uploads` disk rooted at `public/`. The bundled default avatar, placeholder images and logos keep their names and keep working. Images uploaded before this change keep their existing names.
- **Commit:** [`65c00aa`](https://github.com/DMR-PersonalProject/multi-business-dashboard/commit/65c00aa1501c87e4f9c427666ca48bdfaaca7686)

### 7. Feedback text is escaped before it is shown

- **Problem:** customer feedback comes from a public Google Form. When feedback was filtered by date, the name, dish or product and comment were inserted into the page as HTML without escaping, so code typed into a feedback form could run in a logged-in user's browser.
- **Files:** `resources/views/general/kuwago-one/feedbacks-content.blade.php`, `resources/views/general/kuwago-two/feedbacks-content.blade.php`, `resources/views/general/uddesign/feedbacks-content.blade.php`, `resources/views/feedback/index.blade.php`, `resources/views/uddesignfeedback/index.blade.php`, `tests/Feature/Security/FeedbackEscapingTest.php`
- **Change:** all five feedback views escape these fields with a small `escapeHtml()` helper before inserting them, so script text is shown as plain text.
- **Commit:** [`1248561`](https://github.com/DMR-PersonalProject/multi-business-dashboard/commit/1248561faf0f4b7c70d4dcba7337c649bc4d3fba)

### 8. The Kuwago One target gauge works without a displayed target, and shows the right percentage

- **Problem:** on a fresh database, or whenever no target sale was marked as displayed, the Kuwago One dashboard and the `/fetch-target-sale` page failed with "Attempt to read property 'amount' on null". When a target was displayed, the gauge computed target ÷ sales instead of sales ÷ target, so it showed 500% when sales were at 20% of the target, and it divided by zero when there were no sales.
- **Files:** `resources/views/general/kuwago-one/dashboard-content.blade.php`, `resources/views/general/kuwago-one/sale.blade.php`
- **Change:** a missing target counts as 0, and the gauge then shows 0%. Otherwise the gauge shows sales ÷ target × 100, capped at 100%. There is no division when the target is 0.
- **Commit:** _added in a later commit_

### 9. The deals list pages open again

- **Problem:** `/deals` and `/uddesign/uddeals` failed with "Undefined variable $actionRoute" because the shared footer's date-filter form needs a variable these pages don't set. Once deals existed, `/deals` also failed because it loaded a `dealItems` relation that doesn't exist (the relation is `items`).
- **Files:** `resources/views/general/footer.blade.php`, `app/Http/Controllers/DealController.php`
- **Change:** the footer form falls back to the current page address when `$actionRoute` isn't set, and the deals list loads the `items` relation.
- **Commit:** _added in a later commit_

### 10. Feedback list pages no longer need the Google key files, and `/uddesignfeedback` redirects

- **Problem:** the feedback controllers created the Google Sheets client in their constructors, so the plain list pages `/feedback` and `/uddesignfeedback` failed with a server error on any install without the service-account key files, even though they only show rows already in the database. `/uddesignfeedback` also rendered the UDDesign feedback view without the interval data that view needs (`$votes`, the comment lists, `$actionRoute`), so it failed even with the key files present. Nothing in the app links to that page.
- **Files:** `app/Http/Controllers/FeedbackController.php`, `app/Http/Controllers/UddesignFeedbackController.php`
- **Change:**
  - The Google Sheets services are created only inside the import actions, within their existing error handling. A missing key file now shows the usual "could not read the sheet" message and changes no data.
  - `/uddesignfeedback` redirects to `/uddesign/feedbacks`, the working UDDesign feedback page in the sidebar, which shows the same data with date filters.
- **Commit:** _added in a later commit_

## Known issues

These were confirmed while testing the changes above and are not fixed yet.

- **UDDesign feedback date filter:** the UDDesign feedback page sends its date filter to the Kuwago feedback route. Because that route now requires a Kuwago role, the filter is refused for users with the `uddesign` role. Owner and general users get Kuwago feedback in the filtered results instead of UDDesign feedback.
- **Displayed target ignores the business:** the Kuwago One dashboard shows whichever target sale is marked as displayed, without checking its business type, so a Kuwago Two or UDDesign target can appear on the Kuwago One gauge. Only one target can be displayed at a time.
- **Starter tests:** some of Laravel Breeze's original tests (`tests/Feature/Auth`, `tests/Feature/ProfileTest.php`) fail because this app disabled registration and changed the profile routes.
