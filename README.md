# Simple Apache Site

A lightweight cleaning business site built to run on Apache with PHP backend support.

## Features

- Modern landing page for cleaning services
- Quote request form with CSV-backed persistence
- Admin login, request management, and status workflow
- Customer signup/login with a dashboard for quote and payment tracking
- Mock payment workflow for quotes
- SMTP-ready email notifications (configurable in `submit-quote.php`)
- Admin user management with super-admin role support
- Future-ready database migration scaffolding

## Running Locally

The site uses PHP for backend pages, so run it from the `public-html` folder:

```bash
cd /home/ben-j1/simple-apache-site/public-html
php -S localhost:8000
```

Then browse to:

- `http://localhost:8000/index.html` — homepage
- `http://localhost:8000/login.php` — admin login
- `http://localhost:8000/customer-login.php` — customer login
- `http://localhost:8000/customer-signup.php` — customer sign up

## Default Credentials

- Admin username: `admin`
- Admin password: `Clean@123`

## Data Storage

The current implementation stores data in CSV files under `public-html/data/`:

- `users.csv` — admin users
- `customers.csv` — customer accounts
- `requests.csv` — quote requests, status, and payment tracking

These files are excluded from git via `.gitignore`.

The project also supports runtime configuration through environment variables. Copy `.env.example` to `.env` and update values locally.

## Admin Workflow

Admin pages include:

- `admin-requests.php` — manage quote requests, set quote amounts, and update status
- `admin-users.php` — super-admin user management
- `request-details.php` — view request details and payment status

## Customer Workflow

Customer pages include:

- `customer-signup.php` — register a customer account
- `customer-login.php` — login
- `customer-dashboard.php` — view quotes and payment status
- `customer-request-detail.php` — view individual request details
- `pay.php` — mock payment page for completing quote payments

## Database Migration Roadmap

The app currently uses CSV storage, but database scaffolding is included for future migration.

### New files for database support

- `public-html/database.php` — PDO connection helper and migration runner
- `public-html/models/AdminUserModel.php` — admin user queries
- `public-html/models/CustomerModel.php` — customer account queries
- `public-html/models/RequestModel.php` — request and payment queries
- `public-html/sql/schema.sql` — SQL schema for `admin_users`, `customers`, and `requests`

### How to migrate to a database

1. Install and configure a MySQL/MariaDB database.
2. Set environment variables for database connectivity:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=cleaning_service
export DB_USER=dbuser
export DB_PASS=dbpass
```

3. Create the database schema:

```php
php -r "require 'public-html/database.php'; Database::migrate();"
```

4. Update the PHP backend to use the model classes instead of CSV helper functions.

### Suggested migration steps

- Replace `read_csv()` and `write_csv()` logic in `submit-quote.php` with `RequestModel::create()` and database queries.
- Replace admin user CSV lookups in `auth.php` with `AdminUserModel` methods.
- Replace customer CSV auth in `customer-auth.php` with `CustomerModel` methods.
- Replace request filtering and details page lookups with `RequestModel` queries.

## Notes

- All customer and admin passwords are hashed with PHP `password_hash()` before storage.
- The mock payment flow is intentionally simple and not a real payment gateway. It is ready for future replacement with Stripe, PayPal, or another processor.
- `public-html/data/` is kept out of git and should remain as runtime storage for this CSV-based prototype.

## Change Timeline

This timeline documents the main changes made during development and why they were implemented.

1. Initial site skeleton
   - Added a clean landing page and basic service pages.
   - Included a quote request form and static content for the cleaning business.
   - Purpose: provide a simple customer-facing site foundation.

2. CSV-backed persistence
   - Added CSV storage in `public-html/data/` for requests, customers, and admin users.
   - Purpose: keep the prototype lightweight without requiring a database.

3. Admin login and request management
   - Added `login.php`, `auth.php`, `admin-requests.php`, and `request-details.php`.
   - Implemented admin session handling and request status updates.
   - Purpose: let administrators review quote requests and manage work flow.

4. Customer signup/login and dashboard
   - Added `customer-signup.php`, `customer-login.php`, `customer-dashboard.php`, and `customer-request-detail.php`.
   - Implemented customer account creation, login, and request status visibility.
   - Purpose: allow customers to sign up, track requests, and view their quote details.

5. Mock payment workflow
   - Added `pay.php` and payment status tracking for quote requests.
   - Updated request status flow to include paid/unpaid tracking.
   - Purpose: provide a simple payment completion experience that can be upgraded later.

6. Admin user management
   - Added `admin-users.php` for super-admin user management.
   - Purpose: support multiple admin users and manage access securely.

7. Email notification support
   - Added SMTP-ready notification logic in `submit-quote.php`.
   - Purpose: enable alerting when new quote requests arrive.

8. Environment configuration and security hardening
   - Added `public-html/config.php` and `.env.example` for environment-driven settings.
   - Switched SMTP configuration to environment variables and removed hard-coded secrets.
   - Added secure session handling and request logging.
   - Purpose: improve secret management, reduce configuration drift, and harden the application.

9. Database migration scaffolding
   - Added `public-html/database.php`, model classes, and `sql/schema.sql`.
   - Purpose: prepare the app for future migration from CSV files to a database-backed architecture.

10. Documentation and operational notes
    - Updated `README.md` with running instructions, environment guidance, and migration notes.
    - Purpose: make it easier to deploy, maintain, and extend the project.
