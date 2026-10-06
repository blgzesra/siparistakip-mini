# SiparişTakip Mini

A small PHP + MySQL admin panel for keeping track of customers, products and orders. It is aimed at small businesses that take orders over WhatsApp. The user interface is in Turkish.

## Features

- **WhatsApp order link**: each order in the list has a `wa.me` link. The link opens WhatsApp with a ready-made message holding the order number, product, quantity, total and status. It goes to the customer's phone number. When the customer has no phone, it falls back to the business's WhatsApp number from Settings.
- **Customer management**: add, edit and delete customers (name, phone, address, note). The customer detail page shows that customer's orders and total spend.
- **Product management**: add, edit and delete products with a price, a stock quantity and an active flag.
- **Order management**: create, edit and delete orders. Each order has one product and a quantity. The total is price × quantity, and order numbers are generated automatically (`SP-YYYYMMDD-NNNN`).
- **Payment status tracking**: each order is marked waiting, paid or partially paid (`waiting` / `paid` / `partial`).
- **Order status tracking**: new, preparing, shipped, delivered and cancelled (`pending` / `preparing` / `shipped` / `completed` / `cancelled`).
- **Admin dashboard**: today's order count and revenue, the counts of new and delivered orders, and the five latest orders.
- **Demo database**: `database.sql` creates the tables, a demo admin and sample customers, products and orders.

## Security

- All database access goes through PDO prepared statements, with emulated prepares turned off.
- Passwords are stored as `password_hash` (bcrypt) hashes and checked with `password_verify`.
- Output is escaped with `htmlspecialchars`.
- Every POST form carries a per-session CSRF token, checked with `hash_equals`. Delete and logout run only as POST requests.
- After 5 wrong passwords from the same IP, logins from that IP are locked for 15 minutes.
- A session idle for more than 30 minutes is signed out. The session cookie is `HttpOnly` and `SameSite=Lax`, and it is `Secure` over HTTPS.

## Requirements

- PHP 7.4 or newer, with the PDO MySQL extension
- MySQL 5.7 or newer, or MariaDB
- Apache (for example WAMP)

## Installation (WAMP)

1. Copy the project folder to `C:\wamp64\www\siparistakip-mini`. The default `APP_URL` is `/siparistakip-mini`; if you use another folder name, change `APP_URL` in step 3.
2. Open phpMyAdmin (`http://localhost/phpmyadmin`) and import `database.sql`. The import creates the `siparistakip_mini` database itself.
3. Copy `includes/config.example.php` to `includes/config.php` and edit the values if needed. The example file holds the WAMP defaults (`root`, empty password). `includes/config.php` is ignored by git, so your credentials stay out of the repository.
4. Open `http://localhost/siparistakip-mini/admin/login.php`.

### Demo login

| E-mail | Password |
|---|---|
| `admin@demo.com` | `admin123` |

These credentials are for demo purposes only. **Change the password after your first login.** The panel has no password-change screen yet. To change it:

1. Create a new hash: `php -r "echo password_hash('your-new-password', PASSWORD_DEFAULT);"`
2. In phpMyAdmin, put the hash into the `password_hash` column of the `users` row.

## Project structure

```
admin/        Panel pages (login, dashboard, orders, customers, products, settings)
includes/     Config, PDO connection, session/auth/CSRF, shared layout and helpers
assets/       CSS and a small JavaScript file
database.sql  Schema plus demo data
```

## Known limitations

- There is no installation wizard; the database is imported by hand.
- There is no Instagram integration. WhatsApp support is limited to the `wa.me` link.
- There is no shipping or courier tracking. Delivery is covered only by two order statuses: "shipped" and "delivered".
- There are no automated tests.
- There is no password-change screen in the panel.
- An order holds a single product. Stock quantities are not reduced when an order is saved.
- The currency and panel-name settings are stored, but they are not applied yet. Amounts are always shown in TL, and the panel name comes from `includes/config.php`.
- The login lockout uses the client IP (`REMOTE_ADDR`). Behind a reverse proxy, all visitors share the proxy's IP.

## License

[MIT](LICENSE)
