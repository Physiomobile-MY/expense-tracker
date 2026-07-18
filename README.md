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

Create an empty database, then configure `.env`:

```env
APP_NAME="Physiomobile ExpenseFlow"
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=physiomobile_expenseflow
DB_USERNAME=expenseflow_app
DB_PASSWORD=

OPENAI_API_KEY=
OPENAI_RECEIPT_MODEL=gpt-4.1-mini
AI_RECEIPT_EXTRACTION_ENABLED=true
AI_DAILY_SCAN_LIMIT=50
FINANCE_APPROVAL_EMAIL=finance.hq@physiomobile.com
APP_THEME_PRIMARY="#D71920"
```

Run migrations and seed catalog data:

```bash
php artisan migrate --force
php artisan db:seed --force
```

The public repository must not contain SQL dumps, real staff accounts, password hashes, tokens, receipt data, or production settings. Bootstrap director/admin users with environment-specific credentials only. Do not use shared or documented default passwords.

Directors can create staff-level Executive users in **Administration > Users** by choosing the `Executive` role. Executive users have the same claim upload and own-record workflow as staff, but can only see their own expense data.

To create safe local-only demo director users, explicitly generate one-time temporary credentials:

```bash
php artisan expenseflow:ensure-demo-users --generate --force
```

Do not run demo/bootstrap commands on production unless a maintainer has approved the deployment-specific rotation and audit checklist.

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
npm run build
php artisan serve
```

## Validation

```bash
php artisan test
vendor/bin/pint --test
npm run build
```
