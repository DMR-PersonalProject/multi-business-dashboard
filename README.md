# Multi-Business Dashboard

A Laravel web app where one owner can monitor three businesses from a single place:

- **Kuwago One** (restaurant)
- **Kuwago Two** (restaurant)
- **UDDesign** (print and merch shop)

Each business has its own dashboard for sales, expenses, promos or deals, and customer feedback. The owner also gets an executive dashboard that combines all three.

> These setup instructions are for **Windows only**. All commands are for **PowerShell**.

## Features

- Login with role-based access (owner, general, kuwago, uddesign)
- Dashboards for each business, with charts for sales, orders and expenses
- Sales and expenses pages with date filters
- Promos (Kuwago One and Kuwago Two) and deals (UDDesign)
- Customer feedback pages, imported from Google Sheets
- Executive dashboard comparing all businesses, plus a weather page (owner only)
- Target sales and budget allocations (owner only)
- User account management (owner only) and business info management
- Personal settings page and password change for every user

## Tech stack

| Part | Technology |
|---|---|
| Backend | PHP 8.2+, Laravel 11, Laravel Sanctum |
| Auth scaffolding | Laravel Breeze (Blade) |
| Database | MySQL |
| Frontend | Blade, Tailwind CSS 3, Alpine.js, Bootstrap, Chart.js |
| Build tool | Vite 5 |
| External data | Each business app's REST API, Google Sheets API (`google/apiclient`) |
| Tests | Pest 3 |

## Prerequisites

- Windows 10 or 11
- PHP 8.2 or 8.3, with the `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `gd` and `curl` extensions enabled (on PHP 8.4, see Troubleshooting)
- Composer 2
- Node.js 18 or newer, with npm
- MySQL 8
- Git

### MySQL note

The easiest way to get MySQL on Windows is **DBngin** or **Laragon**:

- **DBngin:** create a MySQL service and start it. The default port is **3306** and the default user is `root` with no password.
- **Laragon:** click **Start All**. MySQL runs on port **3306** with user `root` and no password.

Then create an empty database named `dashboard_main`, using HeidiSQL, TablePlus or the MySQL command line:

```sql
CREATE DATABASE dashboard_main;
```

If port 3306 is already taken by another MySQL install, stop that one or change `DB_PORT` in `.env`.

## Installation

```powershell
git clone https://github.com/DMR-PersonalProject/multi-business-dashboard.git
cd multi-business-dashboard

composer install
npm install

Copy-Item .env.example .env
php artisan key:generate

php artisan migrate --seed
```

`php artisan migrate --seed` creates the tables, the four demo accounts and about 12 months of **fake demo data** for all three businesses (see [Demo data](#demo-data)). It takes a few seconds.

If `SEED_USER_PASSWORD` is empty in `.env`, the seeder generates a random demo password and **prints it once** in the output. Copy it right away; it is not saved anywhere else. The seeders refuse to run when `APP_ENV=production`.

### Starting over with fresh demo data

```powershell
php artisan migrate:fresh --seed
```

This **erases everything** in the database configured in `.env` (all tables are dropped), then recreates the tables and seeds new demo accounts and demo data. The demo password changes unless `SEED_USER_PASSWORD` is set. Use it on a local demo database only.

To reset only the demo account passwords and keep all other data, run `php artisan db:seed --class=UserSeeder`.

## Configuration

Open `.env` and set the values below. Everything else can keep the default from `.env.example`.

| Setting | What to put there |
|---|---|
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Your MySQL database and login (default `dashboard_main`, `root`, empty) |
| `SEED_USER_PASSWORD` | Optional. Password for the demo accounts. Leave empty to get a random one printed once when you seed |
| `UDDESIGN_API_URL`, `UDDESIGN_API_EMAIL`, `UDDESIGN_API_PASSWORD` | Address and login of the UDDesign app's API |
| `KUWAGO_ONE_API_URL`, `KUWAGO_ONE_API_EMAIL`, `KUWAGO_ONE_API_PASSWORD` | Address and login of the Kuwago One app's API |
| `KUWAGO_TWO_API_URL`, `KUWAGO_TWO_API_EMAIL`, `KUWAGO_TWO_API_PASSWORD` | Address and login of the Kuwago Two app's API |
| `GOOGLE_SHEET_ID`, `UD_GOOGLE_SHEET_ID` | IDs of the Google Sheets that hold the Kuwago and UDDesign feedback (optional) |

For feedback import, put the Google service-account key files at `storage\app\google-service-account.json` (Kuwago) and `storage\app\uddesign.json` (UDDesign). **Never commit these files or your `.env`.**

The dashboards still open without the business APIs and Google Sheets. They just show whatever data is already in the database.

## Demo accounts

For **local demo use only**. Change the passwords or remove these accounts before deploying anywhere.

| Email | Role |
|---|---|
| admin@example.com | owner |
| general@example.com | general |
| kuwago@example.com | kuwago |
| uddesign@example.com | uddesign |

All four accounts get the same password: the value of `SEED_USER_PASSWORD`, or the random password printed once by `php artisan db:seed`. To get a new password, run the seeder again (see Troubleshooting).

## Demo data

> **All seeded data is fake.** It is generated by the seeders in `database/seeders` for demos and testing only. It does not come from the real businesses, their apps or Google Sheets, and it does not describe real sales, customers or people.

`php artisan migrate:fresh --seed` fills every table with about 12 months of invented data ending today, in a few seconds:

- **Kuwago One and Kuwago Two:** menus, daily order lines, expenses and daily reports (busier on weekends and in December), promos, and customer feedback
- **UDDesign:** print and merch catalogs, daily print and merch sales, expenses, custom-order deals with their items, and customer feedback
- **All businesses:** business profiles, monthly target sales and budget allocations. The current month's Kuwago One target is marked as displayed, so the dashboard gauge has a value.

The totals agree across tables: report sales and expenses equal the sum of that day's order and expense lines, and UDDesign custom sales equal the deals closed that day. Names are labels like "Demo Client 012" or "Demo Guest 104", emails use `example.com`, phone numbers use `0900-555-xxxx`, and images are the placeholders committed in `public/`.

Faker uses a fixed seed, so every run produces the same data relative to the current date. The seeders refuse to run when `APP_ENV=production`, and each one skips its tables if they already contain data, so running `db:seed` twice doesn't create duplicates.

Clicking **refresh** on a dashboard replaces that business's demo data with data from its API (see [Data sync](#data-sync)).

## Running the app

Use two PowerShell windows:

```powershell
# Window 1: frontend assets
npm run dev
```

```powershell
# Window 2: web server
php artisan serve
```

Open http://127.0.0.1:8000 and log in. For a one-time production-style build, run `npm run build` instead of `npm run dev`.

## Usage guide

### Roles

| Role | What the user can open |
|---|---|
| owner | Everything: all three businesses, the executive dashboard, user accounts, promos, target sales and budget allocations, and the data refresh and feedback import buttons |
| general | Kuwago One, Kuwago Two and UDDesign (including deals and feedback) |
| kuwago | Kuwago One and Kuwago Two (including their feedback) |
| uddesign | UDDesign (including deals and feedback) |

There is no public sign-up page. The owner creates accounts under **Accounts**. Users can edit their own profile under settings, but only the owner can change a user's role.

Profile pictures, business images and logos must be JPG, PNG, GIF or WebP images of up to 2 MB.

### Kuwago One and Kuwago Two

Open `/kuwago-one` or `/kuwago-two` from the sidebar. Each restaurant has these pages:

- **Dashboard:** sales, orders, profit and expenses, with charts and target sales
- **Sales:** sales charts filtered by date
- **Expenses:** expenses by category and type
- **Promos:** current promos
- **Feedbacks:** customer feedback

### UDDesign

Open `/uddesign`. It has these pages:

- **Dashboard:** print and merch sales, with charts
- **Sales:** print and merch breakdown
- **Expenses:** expenses by category and type
- **Deals:** UDDesign deals
- **Feedbacks:** customer feedback

### Executive dashboard (owner only)

Open `/executive`. It compares sales, expenses and deals across all three businesses for a chosen period. A weather page is at `/executive/weather`.

## Data sync

- When a user logs in, the app logs in to each business API with the credentials from `.env` and keeps the API tokens in the session.
- The **refresh** button in the footer (owner only) **replaces the local data for that business** with fresh data from its API. On a feedback page, the same button reloads the feedback from Google Sheets.
- Everything is downloaded and checked first. If an API or the sheet returns an error or incomplete data, nothing is changed and an error message is shown. Otherwise the old rows are replaced in one step.
- Refresh and import are limited to 5 per minute. Other roles see a button that only reloads the page.

> Back up your database before refreshing if you have data you can't get back from the business apps.

## Project structure

```
app/
  Http/Controllers/
    KuwagoOne/  KuwagoTwo/  Uddesign/   per-business pages
    Executive/                          executive dashboard
    Api/DataController.php              data refresh from business APIs
  Http/Middleware/                      role checks
  Listeners/LoginSuccessful.php         gets API tokens at login
  Models/                               Eloquent models (per-business folders)
  Services/                             Google Sheets services
  Support/                              upload and feedback-sheet helpers
database/
  migrations/  seeders/  factories/
resources/views/
  general/kuwago-one, kuwago-two, uddesign, executive   dashboards
  roles/  settings/  promos/  deals/  business_info/    management pages
routes/
  web.php  api.php  auth.php
public/
  assets/                               CSS, JS and images
```

## Testing

```powershell
php artisan test
```

The tests run on an in-memory SQLite database (set in `phpunit.xml`), so they never touch your MySQL data. As a safety net, the tests stop with an error unless the database is in-memory SQLite or its name ends in `_test`. The tests don't need `.env` or `npm run build`.

The security tests are in `tests\Feature\Security`. Run only those with:

```powershell
php artisan test tests/Feature/Security
```

`tests\Feature\EmptyDatabasePagesTest.php` checks that every dashboard and list page opens on an empty database, and `tests\Feature\DemoSeederTest.php` checks that the demo seeders fill the main tables with consistent fake data.

Some of Laravel Breeze's original starter tests in `tests\Feature\Auth` and `tests\Feature\ProfileTest.php` currently fail, because this app disabled registration and changed the profile routes.

## Troubleshooting

| Problem | Fix |
|---|---|
| `SQLSTATE[HY000] [2002]` connection refused | Start MySQL in DBngin or Laragon and check `DB_PORT=3306` |
| `Unknown database 'dashboard_main'` | Create the database (see MySQL note) |
| `No application encryption key has been specified` | Run `php artisan key:generate` |
| `Vite manifest not found` | Run `npm run dev` or `npm run build` |
| Can't log in / lost the demo password | Run `php artisan db:seed --class=UserSeeder` again. It resets the four demo accounts and prints a new password once (or uses `SEED_USER_PASSWORD`) |
| `composer install` fails on PHP 8.4 (paratest requires PHP 8.2 or 8.3) | Use PHP 8.3, or run `composer install --ignore-platform-req=php` |
| No refresh button, or it only reloads the page | Data refresh and feedback import are for the owner account only |
| `419 Page Expired` after clicking refresh | Your session expired. Reload the page, log in again and retry |
| `429 Too Many Requests` | Refresh and import are limited to 5 per minute. Wait a minute |
| "The user image field must be…" | Use a JPG, PNG, GIF or WebP image of up to 2 MB |
| Dashboards are empty | For demo data, run `php artisan migrate:fresh --seed` (erases the database). For real data, the business APIs must be configured and reachable: check the `*_API_URL` values, then log out and back in |
| "API token not found in session" | Log out and back in so new API tokens are fetched |
| Settings changes don't apply | Run `php artisan config:clear` |
| `php` or `composer` isn't recognized | Add PHP and Composer to your Windows `PATH`, or use the Laragon terminal |

## Post-submission changes

The project as submitted for the capstone is tagged [`v1.0.0-capstone-submission`](https://github.com/DMR-PersonalProject/multi-business-dashboard/releases/tag/v1.0.0-capstone-submission). Changes made after that tag (mainly security fixes and tests) are listed in [CHANGELOG.md](CHANGELOG.md).

## Development and AI assistance

The baseline is the tag `v1.0.0-capstone-submission`. After it, Claude Code (an AI coding assistant by Anthropic) helped with follow-up adjustments such as security and bug fixes, tests and documentation. These are listed in [CHANGELOG.md](CHANGELOG.md).

## Authors

- **Dennis Rodriguez** ([DMR-PersonalProject](https://github.com/DMR-PersonalProject))
- **Aaron Malicdem** ([AaronSMalicdem](https://github.com/AaronSMalicdem)), mainly front end
