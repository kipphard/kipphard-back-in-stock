=== Kipphard Back in Stock for WooCommerce ===
Contributors: kipphard
Tags: back in stock, woocommerce, stock notification, waitlist, lagerbenachrichtigung
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Notifies customers by email when an out-of-stock WooCommerce product becomes available again. Clean UX, honest scope.

== Description ==

**Kipphard Back in Stock for WooCommerce** adds a lightweight "Notify me" form to out-of-stock product pages. When stock is replenished, all subscribers are notified automatically — no manual work required.

**What this plugin does:**

* Displays a "Notify me" sign-up form on out-of-stock WooCommerce product pages
* Stores subscribers in a dedicated custom database table
* GDPR-compliant consent checkbox on the sign-up form
* Sends an automatic plain-text email to all subscribers when stock status changes to "In Stock" — uses configurable `{product}` and `{link}` placeholders in subject and body
* Duplicate prevention: registering twice with the same email and product has no effect
* Subscriber list in the WooCommerce admin (WooCommerce → Back in Stock)
* Configurable form heading, button label, consent text, success/error messages, email subject, and email body

**What this plugin does NOT do:**

This plugin does not send marketing emails or newsletters. It only sends a single transactional notification per subscriber when the specific product they signed up for comes back in stock. All data is stored on your own server — no external services, no tracking.


*Hinweis (DE): Dieses Plugin zeigt auf ausverkauften WooCommerce-Produktseiten ein „Benachrichtige mich"-Formular an und sendet eine automatische E-Mail, sobald das Produkt wieder auf Lager ist. Die Benutzeroberfläche ist auf Deutsch verfügbar.*

== Installation ==

1. Upload the `kipphard-back-in-stock` folder to `/wp-content/plugins/`, or install it from the Plugins screen in your WordPress admin.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. WooCommerce must be installed and activated.
4. Go to **WooCommerce → Back in Stock** to view the subscriber list.
5. Go to **WooCommerce → Back in Stock Settings** to customise form texts, email subject, and email body.

== Frequently Asked Questions ==

= Is WooCommerce required? =

Yes. The plugin requires WooCommerce and will show an admin notice if WooCommerce is not active.

= Is data removed when I uninstall the plugin? =

Yes. On uninstall, the plugin settings and the subscriptions table are completely removed from your database.

= Which placeholders can I use in the email? =

Use `{product}` for the product name and `{link}` for the direct URL to the product page. Both are available in the email subject and body fields in the settings.

= Does the form appear for products that are in stock? =

No. The form is only rendered when a product's stock status is "Out of Stock". It is hidden automatically once the product is back in stock.

= Is the plugin GDPR-compliant? =

Yes. The sign-up form includes a required consent checkbox, and no data is sent to external servers — everything is stored on your own site.

== Screenshots ==

1. The "Notify me" form on an out-of-stock product page — heading, email field, and consent checkbox.
2. Subscriber list in the WooCommerce admin: product, email address, and registration date.
3. Settings page: form texts, email subject and body with `{product}` and `{link}` placeholders.

== Changelog ==

= 0.4.0 =
* Renamed to Kipphard Back in Stock for WooCommerce. The free version is now fully functional with no license-gated features. Renamed all internal option/table/hook names to a unique plugin-specific prefix.

= 0.3.1 =
* Appearance settings translated to German (de_DE).

= 0.3.0 =
* Shared Kipphard design system (kip-ui) applied to the notify-me form for a consistent, theme-safe look.

= 0.2.0 =
* English source baseline with a German (de_DE) translation.

= 0.1.0 =
* Initial release.
* "Notify me" form on out-of-stock WooCommerce product pages.
* Custom database table for subscriptions with duplicate prevention.
* Automatic plain-text email on stock status change; `{product}` and `{link}` placeholders.
* Configurable form texts, email subject, and email body via WooCommerce settings.
* Subscriber list in WooCommerce admin.
* GDPR consent checkbox; all data stored locally — no external dependencies.

== Upgrade Notice ==

= 0.4.0 =
Renamed to Kipphard Back in Stock for WooCommerce; the free version is fully functional with no locked features.
