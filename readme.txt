=== Plugin Name ===
Contributors: SdeWijs
Donate link: https://www.mollie.com/pay/link/1006571/D2A4A1C0/2.5/Koffie%20voor%20de%20Grinthorst/a60be34ef573cefa17c1a00e90002f526b723683
Tags: online payments, payment gateway, mollie, ideal, restrict content pro, paypal, belfius
Requires at least: 3.0.1
Tested up to: 4.9.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin enables the Mollie Payment Gateway for Restrict Content Pro with all payment methods and support for recurring payments.


== Description ==

This plugin enables the Mollie Payment Gateway for Restrict Content Pro with all payment methods and support for recurring payments.
The plugin loads all active gateways in live mode, and all available Mollie gateways in test mode.
After checkout on the website it redirects the user to the Mollie payment screen where the customer can make the payment.

The payment methods and icons are loaded automatically through the Mollie API. If you add a new payment gateway in your
Mollie Dashboard it will become available in the plugin settings and in the Restrict Content Pro payment gateway settings screen.

The plugin requires a test and live API key which can be acquired from your Mollie account settings at www.mollie.com

If you have a previous version installed which was downloaded directly from my website www.degrinthorst.nl, please remove it and
install this plugin from the WP plugins directory.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/edd-mollie` directory, or install the plugin through the WordPress plugins screen directly (reccommended).
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Enter your API keys and choose test-mode for making test payments
4. Visit the Easy Digital Downlads payment gateway settings and select the Payment gateways you want to activate in your checkout page.
5. Select the payment icons you want to be visible on the checkout page.

== Frequently Asked Questions ==

= I just added and activated a new payment gateway in the Mollie Dashboard, but it's not visible in the plugin settings =

Please go to the RCP-Mollie plugin settings and save your settings, and the new gateway will be fetched from the Mollie API.

= How can I use Mollie recurring payments/subscriptions? =

To activate recurring payments, enter an amount for the initial test-payment in the RCP Mollie settings. If a customer choses for recurring payments upon checkout, they are redirected to the Mollie checkout page where they pay the test amount. This will authorize the next payments directly from the webshop.
If the testpayment is completed, the first term of the subscription will be immediately charged to the customer.

Please note: recurring payments are not suitable for every use-case. If your customers switch or upgrade/downgrade subscriptions often, you will get issues with prorating when working with IDEAL payments. If your customers tend to stick with the same subscriptions or upgrade when the old subscription expires, recurring payments are a great way to increase revenue. Before you start and offer recurring payments to your customers, carefully read the documentation on our website and at the Mollie website.

= Mollie only supports transactions in Euro. Can I use a different currency for my subscriptions? =

You can. As of version 2.3 all currencies other than Euro will be automatically converted to Euro's before the transaction is passed to Mollie. The conversion is done using the current exchange rates, provided by http://fixer.io

== Screenshots ==

== Changelog ==

= 2.3.8.1 =
* Bump version number

= 2.3.8 =
* Bugfix for duplicate member renewals on succesful payment
* Bugfix where fixr api key was nog saved in the settings

= 2.3.7.3 =
* Minor bugfix

= 2.3.7.2 =
* Fix notification message when currency could not be converted.

= 2.3.7.1 =
* Bugfix

= 2.3.7 =
* Add settings field for Fixr API key. This is now required if you need to convert non-Euro payments to Euros. Go to https://fixr.io for pricing and info.

= 2.3.6 =
* Please re-activate your payment methods in RCP settings after this update
* Updated Mollie API to version 1.9.6
* Tested with WP 4.9.5
* Fixed conflict with Mollie Payment method names and RCP built in PayPal and creditcard gateways
* Fixed issue with recurring payments cancel link

= 2.3.1 =
* Bugfix: payments in non-Euro currencies are now properly handled in Webhook calls.

= 2.3 =
* Added support for non-Euro currencies. All currencies are now automatically converted to Euros before being passed to Mollie.

= 2.2.7 =
* Fixed conflict with Mollie PayPal/CreditCard and built in RCP PayPal and CreditCard gateways.
* RCP PayPal and CreditCard gateways are now deactivated to enable Mollie PayPal and CreditCard payments

= 2.2.6 =
* Fixed issue where total amount instead of prorated amount was passed to the gateway
* Updated to latest Mollie PHP API version 1.9.4

= 2.2.5 =
* Fixed typo in settings field

= 2.2.4 =
* Minor bug fixes

= 2.2.3 =
* Removed obsolete option to delete plugin data after deinstallation.
* Added option to deactivate the extra checkout fields for users who only require the default fields.
* Extra checkout fields are now translated in Dutch

= 2.2.2 =
* Bugfixes and optimized recurring payments

= 2.2.1 =
* Bugfix, RCP Mollie Gateway could nog be found because it was loaded before Restrict Content Pro

= 2.2 =
* Introducing Mollie recurring payments support
* Bugfix: Solved error that occurred on a clean installation with no API keys filled in.

== Upgrade Notice ==

= 2.3.7.1 =
* Fix notification message when currency could not be converted
