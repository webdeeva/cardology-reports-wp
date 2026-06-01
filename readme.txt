=== Cardology Reports ===
Contributors:      aquariusmaximus
Tags:              cardology, reports, stripe, payments, ecommerce
Requires at least: 6.4
Tested up to:      6.7
Requires PHP:      8.0
Stable tag:        1.0.1
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Sell personalized Cardology reports. Stripe Checkout, automatic report generation via the Report Writer API, email delivery.

== Description ==

Cardology Reports is a self-contained ecommerce module for selling 10 personalized Cardology readings on any WordPress site. Customers pick a report, share their birth details, pay through Stripe Checkout, and receive a personalized PDF/HTML report by email when generation completes (typically 10–15 minutes).

Features:

* 10 report types: Life, Yearly, Singles, Children's Life, Financial, Wealth, Relationship, Marriage, Blueprint, Astro-Cardology.
* Stripe Checkout — supports test + live modes, dynamic prices, and native promo codes.
* Configurable per-report name, description, and price (no code changes).
* Stripe-native promo code support, including 100%-off / sub-floor codes that bypass Stripe entirely.
* Background polling cron catches reports that complete while the customer's tab is closed.
* Order receipts and "report ready" emails via wp_mail (SMTP-plugin compatible).
* Three shortcodes: `[cardology_reports]`, `[cardology_report slug="…"]`, `[cardology_report_status]`.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/cardology-reports/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Cardology Reports → Settings** and paste in your Stripe keys + Report Writer API key.
4. Create two pages, add the catalog and status shortcodes to them, then point the **Pages** tab at them.
5. In your Stripe Dashboard add a webhook endpoint listening for `checkout.session.completed` with the URL shown on the plugin Dashboard screen.

== Frequently Asked Questions ==

= Does this work without a Stripe account? =
No. Stripe handles all payment processing.

= Where are reports generated? =
By the external Report Writer API (https://report-writer-qt7cc.ondigitalocean.app/api/v1). You provide the API key in Settings.

= Can I disable a report or change its price? =
Yes. Use **Cardology Reports → Reports Catalog**.

== Changelog ==

= 1.0.1 =
* Auto-create the Catalog and Report Status pages on install/upgrade and store them in settings, so the post-payment redirect always has a valid destination.
* Existing installs self-heal on update (no reactivation needed).
* Add an admin warning when no Status page is configured.
* Redesign the catalog cards as ornate report covers (document emblem, themed via appearance presets).
* Fix a fatal on the Settings screen and make appearance swatch selection update live.
* Add GitHub Releases-backed auto-updates.

= 1.0.0 =
* Initial release.
