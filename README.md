# Cardology Reports — WordPress Plugin

Self-contained WordPress plugin that mirrors the React `/reports` flow on aquariusmaximus.com: customer picks one of 10 cardology reports, fills a birth-info form, pays via Stripe Checkout, and receives a personalized reading by email + on-site download.

## Quick start

1. Drop `cardology-reports/` into `wp-content/plugins/` and activate.
2. **Cardology Reports → Settings**:
   - Paste Stripe keys (test + live), choose mode.
   - Paste Report Writer API key.
   - Configure From name / From email.
3. **Cardology Reports → Reports Catalog**: edit each report's title, description, and price as needed.
4. Add the Stripe webhook endpoint shown on the Dashboard screen (subscribe to `checkout.session.completed`).
5. Create two pages and add the shortcodes:
   - Catalog page → `[cardology_reports]` (and/or `[cardology_report slug="life"]` for an individual report)
   - Status page → `[cardology_report_status]` (Stripe redirects here with `?session_id=...`)
6. Settings → **Pages** tab: select those two pages.

## Architecture

```
cardology-reports.php          Bootstrap + plugin header + autoloader
uninstall.php                  Drops tables/options if opted in
includes/Plugin.php            Wires subsystems
includes/Lifecycle.php         Activation, schema, custom cron interval
includes/Catalog.php           Default catalog + admin overrides
includes/Orders.php            wpdb access to {prefix}crwp_orders
includes/Stripe_Client.php     Checkout creation, promo lookup, webhook verify
includes/Report_Writer_Client.php  Upstream /generate + /status
includes/Mailer.php            wp_mail for order-received + report-ready
includes/Cron.php              2-min poll: catches reports completing in background
includes/REST.php              /wp-json/cardology-reports/v1/{checkout,webhook,status,validate-promo,claim-free}
includes/Frontend.php          Shortcodes + asset enqueuing
includes/Admin/Admin.php       Menu, Settings API screens, catalog editor
assets/css/{front,admin}.css   Styling
assets/js/front.js             Order form controller + status polling
templates/front/{catalog,single,status}.php  Public-facing markup
templates/admin/{dashboard,catalog,settings}.php
templates/emails/{order-received,report-ready}.php
```

## Test plan

After install + settings configured:

1. Visit the catalog page → all 10 reports render with the right prices.
2. Click into `relationship` → partner section shows. Submit with a Stripe test card (`4242 4242 4242 4242`).
3. Webhook fires → row created in `{prefix}crwp_orders` with `status='processing'` and a `job_id`.
4. Status page polls every 5 s, flips to "Your report is ready" with a download button (~10–15 min).
5. Customer email arrives.
6. Close the tab during processing — the 2-min cron still finishes the order and emails the link.

## Security notes

- All inputs sanitized via `sanitize_text_field` / `sanitize_email` / `wp_kses_post`.
- Webhook verified via `t=…,v1=…` HMAC with 5-min tolerance, constant-time compare.
- Admin screens gated by `manage_options` + nonces.
- Service keys stored in WP options (autoload disabled for secrets).
- All values escaped on output (`esc_html`, `esc_attr`, `esc_url`).

## License

GPL-2.0-or-later.
