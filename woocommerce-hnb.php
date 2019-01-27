<?php

namespace Ayesh\WooCommerceHNB;

use Ayesh\WooCommerceHNB\Gateway\WC_HNB_Gateway;

/**
 * Plugin Name: WooCommerce HNB
 * Plugin URI: https://wordpress.org/plugins/woocommerce-hnb
 * Description: WooCommerce Payment Gateway plugin integration with Hatton National Bank Sri Lanka.
 * Version: 1.0.0
 * Author: Ayesh Karunaratne
 * Author URI: https://ayesh.me/open-source
 * Text Domain:  woocommerce-sampath-bank
 * WC requires at least: 3.3
 * WC tested up to: 3.5
 *
 * @package Ayesh\WooCommerceHNB
 */

defined( 'ABSPATH' ) || die();

add_filter( 'plugin_action_links_woocommerce-hnb/woocommerce-hnb.php', __NAMESPACE__ . '\action_links');
add_filter('woocommerce_payment_gateways', __NAMESPACE__ . '\payment_gateway');


function load(): void {
	include_once dirname( __FILE__ ) . '/src/Gateway/WC_HNB_Gateway.php';
}

function action_links ($links) {
	array_unshift($links,
		'<a href="admin.php?page=wc-settings&tab=checkout&section=hnb_ipg">Settings</a>');
	return $links;
}

function payment_gateway($methods) {
	$methods[] = WC_HNB_Gateway::class;
	return $methods;
}
