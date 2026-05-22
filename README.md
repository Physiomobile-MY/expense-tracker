# Physiomobile ExpenseFlow

Mobile-first internal expense management and receipt capture for Physiomobile.

## Stack

- Laravel 13
- MySQL
- Tailwind CSS
- Spatie Laravel Permission
- DomPDF
- OpenAI Responses API for receipt extraction

Reports export to CSV, native XLSX, and PDF.

## MySQL Setup

Create the database from the included SQL file:

```bash
mysql -u root -p < database/sql/physiomobile_expenseflow_mysql.sql
```

Then configure `.env`:

```env
APP_NAME="Physiomobile ExpenseFlow"
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=physiomobile_expenseflow
DB_USERNAME=root
DB_PASSWORD=

OPENAI_API_KEY=
OPENAI_RECEIPT_MODEL=gpt-4.1-mini
AI_RECEIPT_EXTRACTION_ENABLED=true
AI_DAILY_SCAN_LIMIT=50
FINANCE_APPROVAL_EMAIL=finance.hq@physiomobile.com
APP_THEME_PRIMARY="#D71920"
```

The SQL file includes the MVP schema, departments, expense categories, roles, permissions, OpenAI settings, and the initial Director Super Admin and Executive accounts.

## Initial Accounts

All initial accounts use temporary password:

```text
password
```

Users must change this password after first login.

Director Super Admin:

- `nidzamyatimi@physiomobile.com`
- `saiful@physiomobile.com`

Executive staff-level accounts:

- `executive1@physiomobile.com`
- `executive2@physiomobile.com`

Executive accounts have the same claim upload and record features as staff, but can only see their own expense data.

To reset these accounts on an existing deployment:

```bash
php artisan expenseflow:ensure-demo-users
```

To create or repair the default departments and expense categories:

```bash
php artisan expenseflow:ensure-catalog
```

Category auto-matching is configurable in **Categories**. Add comma-separated keywords to a category, for example `kopitiam, nasi, kopi` for `Meal`. Receipt merchant names, descriptions, and line items are checked against those keywords when a user submits without choosing a category.

## Finance Email Notifications

Claimable expenses email finance when they are submitted for approval and when they are approved.

Configure mail delivery and recipient in `.env`:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=no-reply@physiomobile.com
MAIL_FROM_NAME="${APP_NAME}"
FINANCE_APPROVAL_EMAIL=finance.hq@physiomobile.com
```

For port `587`, use `MAIL_SCHEME=smtp`. For port `465`, use `MAIL_SCHEME=smtps`. Do not set `MAIL_SCHEME=null` in Laravel Cloud.

Test SMTP from the server with:

```bash
php artisan expenseflow:test-finance-email
```

## Clear Test Expense Records

To delete all expense records, receipts, approvals, AI logs, notifications, and audit logs while keeping users, roles, departments, categories, and settings:

```bash
php artisan expenseflow:clear-records --force
```

## Run Locally

```bash
composer install
npm install
php artisan key:generate
php artisan storage:link
npm run build
php artisan serve
```

If you prefer Laravel migrations instead of importing SQL:

```bash
php artisan migrate:fresh --seed
```

## Validation

```bash
php artisan test
vendor/bin/pint --test
npm run build
```
