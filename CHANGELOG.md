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
