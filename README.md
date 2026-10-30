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
- PHP 8.2 or newer, with the `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` and `curl` extensions enabled
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

php artisan migrate
php artisan db:seed
php artisan db:seed --class=UserSeeder
```

`php artisan db:seed` adds sample target sales and budget allocations for 2023 and 2024. `UserSeeder` adds the demo accounts and has to be run separately.

## Configuration

Open `.env` and set the values below. Everything else can keep the default from `.env.example`.

| Setting | What to put there |
|---|---|
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Your MySQL database and login (default `dashboard_main`, `root`, empty) |
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

The passwords are set in `database/seeders/UserSeeder.php`.

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
| owner | Everything: all three businesses, the executive dashboard, user accounts, promos, target sales and budget allocations |
| general | Kuwago One, Kuwago Two and UDDesign |
| kuwago | Kuwago One and Kuwago Two |
| uddesign | UDDesign |

There is no public sign-up page. The owner creates accounts under **Accounts**.

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
- The **refresh** buttons call `GET /api/refresh-data/{type}` (`uddesign`, `kuwago_one` or `kuwago_two`). This **deletes the local data for that business and reloads it** from the API.
- Feedback is reloaded from Google Sheets through `/feedback/import-one`, `/feedback/import-two` and `/uddesignfeedback/fetch`. These also replace the existing feedback.

> **Warning:** `GET api/refresh-data/{type}` has no login check and changes data. The feedback import links don't require login either. Only run this app on a trusted local machine, and back up your database before you refresh.

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

> **Warning:** the feature tests reset the database (`RefreshDatabase`), and `phpunit.xml` does not switch to a separate test database. Running the tests **wipes the database named in `.env`**. Create a separate test database and point the tests to it (for example with a `.env.testing` file) before you run them.

## Troubleshooting

| Problem | Fix |
|---|---|
| `SQLSTATE[HY000] [2002]` connection refused | Start MySQL in DBngin or Laragon and check `DB_PORT=3306` |
| `Unknown database 'dashboard_main'` | Create the database (see MySQL note) |
| `No application encryption key has been specified` | Run `php artisan key:generate` |
| `Vite manifest not found` | Run `npm run dev` or `npm run build` |
| Can't log in | Run `php artisan db:seed --class=UserSeeder` |
| Dashboards are empty | The business APIs aren't configured or reachable. Check the `*_API_URL` values, then log out and back in |
| "API token not found in session" | Log out and back in so new API tokens are fetched |
| Settings changes don't apply | Run `php artisan config:clear` |
| `php` or `composer` isn't recognized | Add PHP and Composer to your Windows `PATH`, or use the Laragon terminal |

## Authors

- **Dennis Rodriguez** ([DMR-PersonalProject](https://github.com/DMR-PersonalProject))
- **Aaron Malicdem** ([AaronSMalicdem](https://github.com/AaronSMalicdem)), mainly front end
