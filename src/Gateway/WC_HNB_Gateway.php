<?php


namespace Ayesh\WooCommerceHNB\Gateway;

use WC_Order;
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
	private const INSTITUTION_NAME = 'HNB';

	private static $gateway_attributes = [
		'Version' => '1.0.0',
		'SignatureMethod' => 'SHA1',
		'CaptureFlag' => 'A',
	];

	private $MerID, $AcqID, $pass;
	private $PurchaseCurrency, $PurchaseCurrencyExponent;
	private $TestFlag, $ShipToLastName, $ResponseCode, $MerRespURL;


	public function __construct() {
		$this->initializeMetaData();
		$this->registerActions();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->title                    = $this->get_option('title');
		$this->description              = $this->get_option('description');
		$this->MerID                    = $this->settings['MerID'];
		$this->AcqID                    = $this->settings['AcqID'];
		$this->pass                     = $this->settings['pass'];

		$this->PurchaseCurrency         = $this->settings['PurchaseCurrency'];
		$this->PurchaseCurrencyExponent = $this->settings['PurchaseCurrencyExponent'];
		$this->CaptureFlag              = $this->settings['CaptureFlag'];
	}

	private function registerActions(): void {
		add_action('woocommerce_receipt_' . $this->id, [$this, 'ipg_page']);
		add_action('woocommerce_update_options_payment_gateways_' . $this->id,
			[$this, 'process_admin_options']);
	}

	private function initializeMetaData(): void {
		$this->id                 = self::ID;
		$this->icon               = apply_filters('woocommerce_hnb_icon',
			plugins_url('assets/images/cards.png',
			\dirname(plugin_dir_path(__FILE__))));
		$this->method_title       = __(self::NAME, 'woocommerce-hnb');
		$this->method_description = __(self::DESCRIPTION, 'woocommerce-hnb');
		$this->order_button_text  = __('Proceed to payment', 'woocommerce-hnb');
		$this->has_fields         = FALSE;
	}

	public function init_form_fields(): void {
		$this->form_fields                = [];
		$this->form_fields['enabled']     = [
			'title'   => __('Enable/Disable'),
			'type'    => 'checkbox',
			'label'   => __('Enable HNB IPG Module.'),
			'default' => 'no',
		];
		$this->form_fields['title']       = [
			'title'       => __('Title'),
			'type'        => 'text',
			'description' => __('The title end users see when making the payment'),
			'default'     => __('Credit or debit card'),
		];
		$this->form_fields['description'] = [
			'title'       => __('Description'),
			'type'        => 'textarea',
			'description' => __('A description to show to end users.'),
			'default'     => sprintf(__('You will be sent to %s secure payment gateway to complete the payment.'), self::INSTITUTION_NAME),
		];
		$this->form_fields['_version']     = [
			'title'       => __('Gateway Version'),
			'type'        => 'markup',
			'description' => '',
			'value'     => self::$gateway_attributes['Version'],
		];
		$this->form_fields['MerID']       = [
			'title'       => __('Merchant ID'),
			'type'        => 'text',
			'description' => sprintf(__('Merchant ID, provided by %s.'), self::INSTITUTION_NAME),
			'default'     => '',
			'desc_tip'    => true,
		];
		$this->form_fields['AcqID']       = [
			'title'       => __('Acquirer ID'),
			'type'        => 'text',
			'description' => sprintf(__('Acquirer ID, provided by %s.'), self::INSTITUTION_NAME),
			'default'     => '',
			'desc_tip'    => true,
		];
		$this->form_fields['pass']        = [
			'title'       => __('Password'),
			'type'        => 'password',
			'description' => sprintf(__('Payment gateway password, provided by %s.'), self::INSTITUTION_NAME),
			'default'     => '',
			'desc_tip'    => true,
		];

		$currency = get_woocommerce_currency();
		if ($currency) {
			$currency_iso = $this->getCurrencyList_ISO4217($currency);
			$currency_display = $currency_iso
				? \sprintf('%s (%d)', $currency, $currency_iso)
				: \sprintf('%s %s', $currency, __('Unsupported'));

			$this->form_fields['_currency'] = [
				'title'       => __('Currency'),
				'type'        => 'markup',
				'description' => \sprintf(__('%s gateway requires an ISO 4217 currency code. This currency code taken from your %s default currency.'),
					self::INSTITUTION_NAME, __('WooCommerce')),
				'value'     => $currency_display,
				'desc_tip'    => true,
			];

			$this->form_fields['_currency_exponent'] = [
				'title'       => __('Currency Exponent'),
				'type'        => 'markup',
				'description' => 'The exponent value used to normalize currencies. This value is automatically deduced.',
				'value'     => $this->getCurrencyExponent($currency),
				'desc_tip'    => true,
			];
        }

		$this->form_fields['_signature'] = [
			'title'       => __('Signature Method'),
			'type'        => 'markup',
			'description' => \sprintf(__('Make sure this value matches the documentation provided %s. This is an important aspect of payment validation, and a mismatch can indicate this plugin version is not compatible with your implementation.'), self::INSTITUTION_NAME),
			'value'     => self::$gateway_attributes['SignatureMethod'],
			'desc_tip'    => true,
		];

		$this->form_fields['_caputure'] = [
			'title'       => __('Capture Flag'),
			'type'        => 'markup',
			'description' => __('Payment capturing method. A value of "A" means automatic authorization and capturing. This value is not configurable at the moment.'),
			'value'     => self::$gateway_attributes['CaptureFlag'],
			'desc_tip'    => true,
		];

		$this->form_fields['_gateway_url'] = [
			'title'       => __('Gateway URL'),
			'type'        => 'markup',
			'description' => __('The URL endpoint to submit data. This value is indicative.'),
			'value'     => self::IPG_URL,
			'desc_tip'    => true,
		];
	}


	public function generate_markup_html($key, $data) {
		$field_key = $this->get_field_key($key);
		$defaults  = array(
			'title'             => '',
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => FALSE,
			'description'       => '',
			'custom_attributes' => array(),
			'value'             => '',
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<span class="wc_input_markup <?php echo esc_attr( $data['class'] ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); // WPCS: XSS ok. ?> />
					<?php echo wp_kses_post($data['value']); ?>
					<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}


	private function getCurrencyList_ISO4217(string $currency_code = 'LKR'): ?int {
		$map = [
			'AED' => 784,
			'AUD' => 036,
			'CAD' => 124,
			'CNY' => 156,
			'EUR' => 978,
			'INR' => 356,
			'LKR' => 144,
			'USD' => 840,
		];

		return $map[$currency_code] ?? NULL;
	}

	private function getCurrencyExponent(string $currency = 'LKR'): int {
	    return 2; // All supported currencies use 2.
    }

	public static function handlePayload(array &$payload): void {
	    $code = self::validatePayload($payload);
	    if ($code !== 0) {
	        wp_die($code);
        }


	    $instance = new static();
	    $instance->handlePayloadReal($payload);
	    die(0);
	}

	private static function validatePayload(array $payload): int {
		if (empty($payload['order_id'])
            || !($order_id = (int) $payload['order_id'])
            || empty($payload['token'])
            || !\is_string($payload['token'])
        ) {
			return -1;
		}

		if (!self::validateToken($payload['token'], $order_id)) {
		    return -1;
        }

		return 0;
    }

	private function generateCallbackUrl(int $order_id): string {
		return add_query_arg(
		    [
		        'wc-api' => __CLASS__,
                'order_id' => $order_id,
                'token' => self::generateToken($order_id),
            ], home_url( '/' ) );
    }

    private static function generateToken(int $order_id): string {
      $key = wp_salt('nonce');
      return \hash_hmac('sha256', __CLASS__ . '-' . $order_id, $key);
    }

    private static function validateToken(string $given_token, int $order_id): bool {
        $valid_token = self::generateToken($order_id);
        return \hash_equals($valid_token, $given_token);
    }

	private function handlePayloadReal(array &$payload): void {

	}

	public function process_payment($order_id){
		$order = new WC_Order($order_id);
		return array('result' => 'success', 'redirect' => add_query_arg('order-pay',
			$order->get_id(), add_query_arg('key', $order->get_order_key(), get_permalink(woocommerce_get_page_id('pay' ))))
		);
	}

	public function ipg_page(int $order_id){
	    $order = new WC_Order($order_id);
	    $fields = $this->getIPGFields($order);
	    $input_fields = '';
	    foreach ($fields as $key => $value) {
			$input_fields .= "<input type='hidden' name='$key' value='$value' />";
        }
	    $url = esc_attr(self::IPG_URL);
	    $submit_text = __('Proceed to payment');
	    $cancel_text = __('Cancel and return');
	    $id = self::ID;
	    $cancel_url = $order->get_cancel_order_url();
	    echo <<<TEXT
<form action="{$url}" method="post" id="ipg_payment_form-{$id}">
{$input_fields}
<input type="submit" class="button button-primary" value="{$submit_text}" />
<a class="cancel" href="{$cancel_url}">{$cancel_text}</a>
</form>
TEXT;
    }

    private function generateSignatureOrder(WC_Order $order, string $formatted_total, int $currency_code): string {
	    $string = "{$this->pass}{$order->get_id()}{$formatted_total}{$currency_code}";
		return base64_encode(hash('sha1', $string, true));
	}

	public function is_available() {
	    if (!parent::is_available()) {
	        return false;
        }

	    return !empty($this->MerID) && !empty($this->AcqID) && !empty($this->pass);
	}

	private function getIPGFields(WC_Order $order): array {
		$currency = get_woocommerce_currency();
		$exponent = $this->getCurrencyExponent($currency);
		$total = $order->get_total();
		$total_formatted = (int) ($total * (10 ** $exponent));
		$total_formatted = \str_pad($total_formatted, 12, 0, \STR_PAD_LEFT);
		$currency_code = $this->getCurrencyList_ISO4217($currency);

		$signature = $this->generateSignatureOrder($order, $total_formatted, $currency_code);

		return [
			'Version' => self::$gateway_attributes['Version'],
			'MerID'   => $this->MerID,
			'AcqID'   => $this->AcqID,
			'MerRespURL' => $this->generateCallbackUrl($order->get_id()),
			'PurchaseCurrency' => $currency_code,
			'PurchaseCurrencyExponent' => $this->getCurrencyExponent($currency),
			'OrderID' => $order->get_id(),
			'SignatureMethod' => self::$gateway_attributes['SignatureMethod'],
			'Signature' => $signature,
			'CaptureFlag' => self::$gateway_attributes['CaptureFlag'],
			'PurchaseAmt' => $total_formatted,
		];
	}
}
