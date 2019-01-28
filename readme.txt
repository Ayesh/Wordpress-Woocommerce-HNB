=== WooCommerce - Hatton National Bank Payment Gateway ===
Contributors: ayeshrajans
Tags: woocommerce, woo commerce, payment, sri lanka, payment gateway, lkr
Requires at least: 4.9
Tested up to: 5.1
Requires PHP: 7.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

WooCommerce HNB plugin is a free and open source plugin to integrate Hatton National Bank Internet Payment Gateway with your WooCommerce store.

### Features
 - Totally free! No need to buy a licenses.
 - Lightweight yet fully functional integration.
 - Written with modern PHP code, making the code light weight and easy to read.
 - Thoroughly and securely validates the payments upon receipt.
 - Security measures to prevent sensitive data exposure.
 - Translated to Sinhala (සිංහල) and Tamil (தமிழ்) by native speakers.

### Prerequisites
 - PHP 7.1 or later
 - `Acquire ID`, `Merchant ID`, and `Password` obtained from HNB.
 - Store served under a public-accessible HTTPS connection.

Note that PHP 5.6 and older versions no longer receive official security updates. PHP 7.1 only receives security fixes, it is highly recommended that you use the latest PHP version. This plugin is tested with PHP versions upto 7.3.

### Configuration
Once enabled, you will see a *Settings* link under the *WooCommece HNB* plugin name. This button, or *WooCommerce Settings -> Payments -> HNB Online Payment* will take you to the plugin configuration page.

In this page, enter the Acquirer ID, Merchant ID, and Password exactly as provided by HNB.

### Functionality

When the customers are about pay for the order, they will see the option to pay by credit/debit cards via HNB payment gateway. User will be sent to HNB payment gateway to complete the payment.

Upon completion, user is sent back to your store, and depending on the transaction status, user will either see the order-complete page, or sent back to the checkout page with a message saying the payment failed.

If a transaction fails (card declined, configuration error, etc), this plugin logs an admin-note to the order. This note tries to put as much as possible information for administrators to help resolve any problems. The error codes are available to refer in the PDF file sent by HNB.

== Frequently Asked Questions ==

= Use this plugin on older PHP versions =

As indicated above, this plugin requires PHP 7.1. This is a hard requirement and we are strict about this requirement. Touch cookies.

= I get a "Payment Error" message when I click "Proceed to payment" =

A "Payment Error" (as opposed to "Payment declined") often means there is something wrong with your configuration. Double check your Acquirer and Merchant IDs. You also need to make sure the site is accessible over public internet and is served with HTTPS.

= How do I contribute? =

Please head over to [GitHub repository](https://github.com/Ayesh/wordpress-woocommerce-hnb). We use GitHub and Git, but individual releases are aded to WordPress.org SVN repository.
