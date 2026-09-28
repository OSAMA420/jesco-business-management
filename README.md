# JESCO Business Management

Inventory, sales, purchasing and accounts software for **JESCO** ([jesco.pk](https://www.jesco.pk)), a Karachi engine oil brand run by PAK OIL LUBRICANTS (motor oil, motorcycle oil, gear oil and ATF).

One dashboard covers stock, orders, customers, suppliers, payments, expenses and reports. It is built with Laravel so it can run on ordinary PHP and MySQL shared hosting (cPanel).

> Private client project. Please don't share the code or data outside the team.

## Features

| Area | What it does |
|---|---|
| **Dashboard** | This month's sales, cash collected, net profit and dues; sales and stock trend charts (7 days, 5 weeks, 12 months); a "Needs Attention" list; recent orders; top products. Every block follows the user's role. |
| **Products** | Products with SKU, category, cost and selling price, stock and reorder level. Filters for category, stock level and active/inactive. |
| **Inventory** | Current stock with low and out-of-stock flags. Manual Stock In / Stock Out, and a full movement history that links back to the order or purchase that caused it. |
| **Customers** | Profiles with order history, a transaction ledger and the outstanding balance. "Receive Payment" can pay the oldest bills first or a chosen bill. |
| **Orders** | Line items with live totals. Stock is taken out when an order is placed and put back if it is cancelled or deleted. Payments are recorded per order (at creation or later) and the Paid / Partial / Unpaid status is worked out from them. Printable A4 invoice with the amount in words. |
| **Suppliers & Purchases** | Purchases can be Ordered, Received or Cancelled. Only a received purchase adds stock and creates money owed to the supplier, and receiving one updates the product's cost price. Supplier payments work like customer payments. |
| **Finance & Accounts** | Cash in, cash out and net cash flow for any period, a six-month cash chart, receivable and payable lists, a searchable ledger of every transaction, and expenses by category. |
| **Reports** | Sales, Profit & Loss, Stock, Purchases and Dues (aging by 0-30 / 31-60 / 61-90 / 90+ days). Each has a date filter, CSV download and Print / Save as PDF. |
| **Users & roles** | Admin, Manager, Sales Staff and Warehouse Staff, each limited to their own areas. The last admin can't be deleted or demoted. |

Every list has filters (search, status, payment, party, dates with Today / This Week / This Month / Last Month / This Year / Custom Range).

### Roles

| Role | Can use |
|---|---|
| Admin | Everything, including Users |
| Manager | Everything except Users |
| Sales Staff | Customers, Orders |
| Warehouse Staff | Inventory |

### Business rules worth knowing

- **Money:** `transactions.amount` is positive for money in and negative for money out. Bills are `sale` and `purchase` rows; money actually moves with `payment_in`, `payment_out` and `expense` rows.
- **Payments belong to a bill.** A bill's status is never typed in; it's calculated from the payments recorded against it. To fix a wrong payment, remove it from the order or purchase page.
- **An order or purchase with payments can't be cancelled, un-received or deleted** until those payments are removed.
- **Stock can't go negative through a purchase reversal.** If stock from a purchase has already been sold, that purchase can't be cancelled.
- **Profit** uses `order_items.unit_cost`, the product's cost at the moment it was sold, so later price changes don't rewrite old profit.
- **Time zone** is Asia/Karachi.

## Tech stack

- Laravel 12 (PHP 8.2+), Blade, Alpine.js, Tailwind CSS (Vite)
- MySQL (MariaDB via XAMPP locally)
- Chart.js for the dashboard line charts; other charts are plain HTML/CSS
- PHPUnit feature tests

## Running it locally (Windows + XAMPP)

1. Start **Apache** and **MySQL** from XAMPP, then create a database called `jesco_inventory` (phpMyAdmin at http://localhost/phpmyadmin).
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Create the environment file and app key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   `.env.example` is already set up for XAMPP's MySQL (`root`, no password).
4. Create the tables and sample data (real JESCO products, sample customers, orders and a purchase):
   ```bash
   php artisan migrate --seed
   ```
5. Build the front end and start the server:
   ```bash
   npm run build
   php artisan serve
   ```
6. Open http://127.0.0.1:8000 and sign in with `admin@jesco.pk` / `password`. Change this password straight away on any copy other people can reach.

Blade and PHP changes show up on refresh. Changes under `resources/css` or `resources/js`, or Tailwind classes used for the first time, need `npm run build` again (or run `npm run dev` while working).

## Tests

```bash
php artisan test
```

The suite runs on an in-memory SQLite database and covers stock movements, purchases, per-bill payments, list filters, finance totals, every report's figures, the dashboard for each role, and invoices. The stock Laravel `ExampleTest` fails because `/` redirects to the login page; that's expected.

## Configuration

- **Invoice details** (legal name, address, phone, website, NTN, email) are in [`config/jesco.php`](config/jesco.php). Override them in `.env` with `JESCO_NTN`, `JESCO_EMAIL` and so on.
- **Expense categories and payment methods** are constants in `app/Http/Controllers/FinanceController.php`.

## Deployment

### Demo on Wasmer Edge

A demo copy runs on Wasmer Edge. Config is in [`deploy/wasmer/`](deploy/wasmer):

```bash
bash deploy/wasmer/build.sh        # clean build in .wasmer-build/ (no .env, node_modules or dev packages)
cd .wasmer-build
wasmer deploy --non-interactive
```

Things to know:
- `APP_KEY` is a Wasmer app secret, not part of the package.
- **Migrations don't run on deploy.** Run new ones against the Wasmer database first, and back the database up before any migration that changes data.
- Wasmer's PHP needs `php/php@=8.3.400` or later for MySQL, and it lacks `mb_split`, which `bootstrap/polyfills.php` provides.
- The file system is read-only, so views compile to `/tmp`, logs go to stderr, and sessions and cache live in the database.

### Production (cPanel or similar)

Any host with **PHP 8.2+** and MySQL works:
1. Upload the project (or `git pull`) and run `composer install --no-dev --optimize-autoloader`.
2. Build assets locally with `npm run build` and upload `public/build`.
3. Point the domain or subdomain (for example `app.jesco.pk`) at the `public/` folder.
4. Set `.env` with `APP_ENV=production`, `APP_DEBUG=false`, the real `APP_URL` and the database details, then run `php artisan migrate --force`.
5. Use HTTPS, change the admin password, and schedule regular database backups.

## Project layout

```
app/Http/Controllers   one controller per module (Order, Purchase, Finance, Report, Dashboard...)
app/Models             Eloquent models; Concerns/TracksPayments holds the shared bill/payment logic
app/Support            DateFilter, PartyPayment (spreads a payment over bills), AmountInWords
resources/views        Blade views per module, plus shared components (filters, payment panel, charts)
database/migrations    schema, plus data migrations for per-bill payments and cost at time of sale
deploy/wasmer          Wasmer Edge build and app config
tests/Feature          feature tests
```
