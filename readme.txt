=== WooCommerce - Hatton National Bank Payment Gateway ===
Contributors: ayeshrajans
Tags: woocommerce, woo commerce, payment, sri lanka, payment gateway, lkr, WooCommerce - Hatton National Bank Payment Gateway, Hatton National Bank, HNB, HNB IPG
Requires at least: 4.9
Tested up to: 6.3
Requires PHP: 7.1
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

WooCommerce HNB plugin is a free and open source plugin to integrate Hatton National Bank Internet Payment Gateway with your WooCommerce store.

### Features
 - Totally free! No need to buy a license.
 - Lightweight yet fully functional integration.
 - Written with modern PHP code, making the code light weight and easy to read.
 - Thoroughly and securely validates the payments upon receipt.
 - Security measures to prevent sensitive data exposure.
 - Translated to Sinhalese (සිංහල) and Tamil (தமிழ்)  by native speakers.

### Prerequisites
 - PHP 7.1 or later
 - `Acquire ID`, `Merchant ID`, and `Password` obtained from HNB.

Note that PHP 5.6 and older versions no longer receive official security updates. PHP 7.1 only receives security fixes, it is highly recommended that you use the latest PHP version. This plugin is tested with PHP versions upto [PHP 8.0](https://php.watch/versions/8.0).

### Configuration
Once enabled, you will see a *Settings* link under the *WooCommerce HNB* plugin name. This button, or *WooCommerce Settings -> Payments -> HNB Online Payment* will take you to the plugin configuration page.

In this page, enter the Acquirer ID, Merchant ID, and Password exactly as provided by HNB.

### Functionality

When the customers are about pay for the order, they will see the option to pay by credit/debit cards via HNB payment gateway. User will be sent to HNB payment gateway to complete the payment.

Upon completion, user is sent back to your store, and depending on the transaction status, user will either see the order-complete page, or sent back to the checkout page with a message saying the payment failed.

If a transaction fails (card declined, configuration error, etc.), this plugin logs an admin-note to the order. This note tries to put as much as possible information for administrators to help resolve any problems. The error codes are available to refer in the PDF file sent by HNB.

== Frequently Asked Questions ==

= Use this plugin on older PHP versions =

As indicated above, this plugin requires PHP 7.1. This is a hard requirement, and we are strict about this requirement. Touch cookies.

= I get a "Payment Error" message when I click "Proceed to payment" =

A "Payment Error" (as opposed to "Payment declined") often means there is something wrong with your configuration. Double-check your Acquirer and Merchant IDs. You also need to make sure the site is accessible over public internet and is served with HTTPS.

= How do I contribute? =

Please head over to [GitHub repository](https://github.com/Ayesh/wordpress-woocommerce-hnb). We use GitHub/Git, but individual releases are added to WordPress.org SVN repository.

== Installation ==

= Minimum Requirements =

- PHP 7.1 or later
- `Acquire ID`, `Merchant ID`, and `Password` obtained from HNB.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself, and you don’t need to leave your web browser. To do an automatic installation of this plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “WooCommerce – Hatton National Bank Payment Gateway” and click Search Plugins. Once you’ve found our plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

== Screenshots ==

1. Enable the plugin
2. WooCommerce Payments tab shows the new "HNB Online Payment" method
3. Configure the payment gateway
4. Payment option in the checkout page
5. Payment configuration page
6. HNB payment gateway page

== Changelog ==

**1.0**

 - Initial release.

**1.0.1**

 - Fix a missing sprintf() call in order notes.
 - Add class constant modifiers to gateway class constants.
 - Various code performance improvements with FQFN and FQCN tweaks.

**1.1**

 - Update plugin for PHP 6.3 compatibility.
