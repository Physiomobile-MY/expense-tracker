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
APP_THEME_PRIMARY="#D71920"
```

The SQL file includes the MVP schema, departments, expense categories, roles, permissions, OpenAI settings, and the two initial Director Super Admin accounts.

## Initial Director Accounts

Both initial accounts use temporary password:

```text
password
```

Users must change this password after first login.

- `nidzamyatimi@physiomobile.com`
- `saiful@physiomobile.com`

To reset these accounts on an existing deployment:

```bash
php artisan expenseflow:ensure-demo-users
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
