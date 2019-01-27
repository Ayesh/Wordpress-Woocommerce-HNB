<?php


namespace Ayesh\WooCommerceHNB\Gateway;

use WC_Payment_Gateway;

/**
 * WooCommerce Payment Gateway integration for Hatton National Bank IPG.
 *
 * @package     Ayesh/WooCommerceHNB
 */
final class WC_HNB_Gateway extends WC_Payment_Gateway {
	private const NAME = 'HNB Online Payment';
	private const ID = 'hnb_ipg';
	private const DESCRIPTION = 'Hatton National Bank Internet Payment Gateway to accept Visa/Master credit and debit cards issued both locally and internationally.';
	private const IPG_URL = 'https://www.hnbpg.hnb.lk/SENTRY/PaymentGateway/Application/ReDirectLink.aspx';

}
