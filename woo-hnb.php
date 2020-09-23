<?php

namespace Ayesh\WooCommerceHNB;

use Ayesh\WooCommerceHNB\Gateway\WC_HNB_Gateway;

/**
 * Plugin Name: WooCommerce HNB
 * Plugin URI: https://wordpress.org/plugins/woo-hnb
 * Description: WooCommerce Payment Gateway plugin integration with Hatton National Bank Sri Lanka.
 * Version: 1.0.1
 * Author: Ayesh Karunaratne
 * Author URI: https://ayesh.me/open-source
 * Text Domain: woo-hnb
 * WC requires at least: 3.3
 * WC tested up to: 4.5
 *
 * @package Ayesh\WooCommerceHNB
 */

defined( 'ABSPATH' ) || die();

add_filter('plugin_action_links_woo-hnb/woo-hnb.php', __NAMESPACE__ . '\action_links');
add_filter('woocommerce_payment_gateways', __NAMESPACE__ . '\payment_gateway');
/** @noinspection SpellCheckingInspection */
add_action('woocommerce_api_ayeshwoocommercehnbgatewaywc_hnb_gateway', __NAMESPACE__ . '\handle_callback');


function load(): void {
	include_once __DIR__ . '/src/Gateway/WC_HNB_Gateway.php';
}

function action_links (array $links): array {
	array_unshift($links,'<a href="admin.php?page=wc-settings&tab=checkout&section=hnb_ipg">Settings</a>');
	return $links;
}

function payment_gateway(array $methods): array {
	load();
	$methods[] = WC_HNB_Gateway::class;
	return $methods;
}

function handle_callback() {
	load();
	WC_HNB_Gateway::handlePayload($_REQUEST);
}
