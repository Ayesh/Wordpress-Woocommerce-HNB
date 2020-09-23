<?php

namespace Ayesh\WooCommerceHNB\Gateway;

use WC_Order;
use WC_Payment_Gateway;

use function __;
use function add_action;
use function add_query_arg;
use function array_merge;
use function base64_encode;
use function dirname;
use function esc_attr;
use function hash;
use function hash_equals;
use function hash_hmac;
use function home_url;
use function is_scalar;
use function is_string;
use function ob_get_clean;
use function ob_start;
use function plugins_url;
use function sprintf;
use function str_pad;
use function wc_add_notice;
use function wp_kses_post;
use function wp_redirect;
use function wp_salt;

use const STR_PAD_LEFT;

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
	private const TD = 'woo-hnb';

	private static $gateway_attributes = [
		'Version' => '1.0.0',
		'SignatureMethod' => 'SHA1',
		'CaptureFlag' => 'A',
	];

	private $MerID, $AcqID, $pass; // Required fields.

	public function __construct() {
		$this->initializeMetaData();
		$this->registerActions();
		$this->init_form_fields();
        $this->populateProperties();
	}

	private function initializeMetaData(): void {
		$this->id                 = self::ID;
		$this->icon               = apply_filters('woocommerce_hnb_icon',
			plugins_url('assets/images/cards.png',
                         dirname(plugin_dir_path(__FILE__))));
		$this->method_title       = __(self::NAME, 'woocommerce-hnb', self::TD);
		$this->method_description = __(self::DESCRIPTION, 'woocommerce-hnb', self::TD);
		$this->order_button_text  = __('Proceed to payment', 'woocommerce-hnb', self::TD);
		$this->has_fields         = FALSE;
	}

	private function registerActions(): void {
		add_action('woocommerce_receipt_' . $this->id, [$this, 'ipg_page']);
		add_action('woocommerce_update_options_payment_gateways_' . $this->id,
                   [$this, 'process_admin_options']);
	}

	private function populateProperties(): void {
		$this->init_settings();
		$this->title       = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->MerID       = $this->settings['MerID'];
		$this->AcqID       = $this->settings['AcqID'];
		$this->pass        = $this->settings['pass'];
    }

	public function init_form_fields(): void {
		$this->form_fields                = [];
		$this->form_fields['enabled']     = [
			'title'   => sprintf(__('Enable %s method', self::TD), self::INSTITUTION_NAME),
			'type'    => 'checkbox',
			'label'   => __('Enable HNB IPG Module.', self::TD),
			'default' => 'no',
		];
		$this->form_fields['title']       = [
			'title'       => __('Title', self::TD),
			'type'        => 'text',
			'description' => __('Payment method name customers see when making the payment.', self::TD),
			'default'     => __('Credit or debit card', self::TD),
		];
		$this->form_fields['description'] = [
			'title'       => __('Description', self::TD),
			'type'        => 'textarea',
			'description' => __('A description to show to when this payment method is selected.', self::TD),
			'default'     => sprintf(__('You will be sent to %s secure payment gateway to complete the payment.', self::TD),
                                     self::INSTITUTION_NAME),
		];
		$this->form_fields['_version']    = [
			'title' => __('Gateway Version', self::TD),
			'type'  => 'markup',
			'value' => self::$gateway_attributes['Version'],
		];
		$this->form_fields['MerID']       = [
			'title'             => __('Merchant ID', self::TD),
			'type'              => 'number',
			'description'       => sprintf(__('Merchant ID, provided by %s.', self::TD),
                                           self::INSTITUTION_NAME),
			'desc_tip'          => TRUE,
			'custom_attributes' => ['required' => 'required'],
		];
		$this->form_fields['AcqID']       = [
			'title'             => __('Acquirer ID', self::TD),
			'type'              => 'number',
			'description'       => sprintf(__('Acquirer ID, provided by %s.', self::TD),
                                           self::INSTITUTION_NAME),
			'desc_tip'          => TRUE,
			'custom_attributes' => ['required' => 'required'],
		];
		$this->form_fields['pass']        = [
			'title'             => __('Password', self::TD),
			'type'              => 'password',
			'description'       => sprintf(__('Payment gateway password, provided by %s.', self::TD),
                                           self::INSTITUTION_NAME),
			'desc_tip'          => TRUE,
			'custom_attributes' => ['required' => 'required'],
		];

		$currency = get_woocommerce_currency();
		if ($currency) {
			$currency_iso     = $this->getCurrencyList_ISO4217($currency);
			$currency_display = $currency_iso
				? sprintf('%s (%d)', $currency, $currency_iso)
				: sprintf('%s %s', $currency,
                          __('Unsupported. Gateway disabled.', self::TD));

			$this->form_fields['_currency'] = [
				'title'       => __('Currency'),
				'type'        => 'markup',
				'description' => sprintf(__('%s gateway requires an ISO 4217 currency code. This currency code taken from your %s default currency.', self::TD),
                                         self::INSTITUTION_NAME, __('WooCommerce')), // No text domain.
				'value'       => $currency_display,
				'desc_tip'    => TRUE,
			];

			$this->form_fields['_currency_exponent'] = [
				'title'       => __('Currency Exponent', self::TD),
				'type'        => 'markup',
				'description' => __('The exponent value used to normalize currencies. This value is automatically deduced.', self::TD),
				'value'       => $this->getCurrencyExponent($currency),
				'desc_tip'    => TRUE,
			];
		}

		$this->form_fields['_signature'] = [
			'title'       => __('Signature Method', self::TD),
			'type'        => 'markup',
			'description' => sprintf(__('Make sure this value matches the documentation provided %s. This is an important aspect of payment validation, and a mismatch can indicate this plugin version is not compatible with your implementation.', self::TD),
                                     self::INSTITUTION_NAME),
			'value'       => self::$gateway_attributes['SignatureMethod'],
			'desc_tip'    => TRUE,
		];

		$this->form_fields['_capture'] = [
			'title'       => __('Capture Flag', self::TD),
			'type'        => 'markup',
			'description' => __('Payment capturing method. A value of "A" means automatic authorization and capturing. This value is not configurable at the moment.', self::TD),
			'value'       => self::$gateway_attributes['CaptureFlag'],
			'desc_tip'    => TRUE,
		];

		$this->form_fields['_gateway_url'] = [
			'title'       => __('Gateway URL', self::TD),
			'type'        => 'markup',
			'description' => __('The URL endpoint to submit data. This value is indicative.', self::TD),
			'value'       => self::IPG_URL,
			'desc_tip'    => TRUE,
		];
	}

	private function getCurrencyList_ISO4217(string $currency_code = 'LKR'): int {
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

		return $map[$currency_code] ?? 0;
	}

	private function getCurrencyExponent(string $currency = 'LKR'): int {
	    return 2; // All supported currencies use 2.
    }

	public static function handlePayload(array $payload): void {
	    if (!self::validatePayload($payload)) {
	        wp_die(1);
        }

	    $instance = new static();
	    $instance->handlePayloadReal($payload);
	    die(0);
	}

	private static function validatePayload(array $payload): bool {
		if (empty($payload['order_id'])
            || !($order_id = (int) $payload['order_id'])
            || empty($payload['token'])
            || !is_string($payload['token'])
        ) {
			return false;
		}

		if (!self::validateToken($payload['token'], $order_id)) {
		    return false;
        }

		return true;
    }

	private function generateCallbackUrl(int $order_id): string {
		return add_query_arg(
            [
		        'wc-api' => __CLASS__,
                'order_id' => $order_id,
                'token' => self::generateToken($order_id),
            ], home_url('/' ) );
    }

    private static function generateToken(int $order_id): string {
      $key = wp_salt('nonce');
      return hash_hmac('sha256', __CLASS__ . '-' . $order_id, $key);
    }

    private static function validateToken(string $given_token, int $order_id): bool {
        $valid_token = self::generateToken($order_id);
        return hash_equals($valid_token, $given_token);
    }

	private function handlePayloadReal(array $payload): void {
	    $order = new WC_Order($payload['order_id']);
	    if (!$order || empty($payload['ResponseCode']) || !is_scalar($payload['ResponseCode'])|| !$order->get_id()) {
			wp_redirect(home_url('/')); die();
        }

	    $this->handleResponse($payload, $order);
	    die();
	}

	private function handleResponse(array $payload, WC_Order $order): void {
	    $payload_stub = [
	        'ResponseCode' => '',
            'ReasonCodeDesc' => '',
            'ReasonCode'=> '',
        ];
	    $payload = array_merge($payload_stub, $payload);

	    switch ($payload['ResponseCode']) {
            case 2:
				$order->add_order_note(sprintf('Payment Declined: Reason code: %s. Reason: %s', esc_html($payload['ReasonCode']), esc_html($payload['ReasonCodeDesc'])));
				$order->add_order_note('Payment declined.', 1);
				wc_add_notice(__('Payment declined. Please try again.', self::TD), 'error');
				wp_redirect($order->get_checkout_payment_url());
				break;

            case 1:
				$valid = $this->validateSignature($payload, $order);
				if ($valid) {
					$order->add_order_note(sprintf('Payment Successful: Reason code: %s. Reason: %s', esc_html($payload['ReasonCode']), esc_html($payload['ReasonCodeDesc'])));
					$order->payment_complete();
					wp_redirect($this->get_return_url($order));
					break;
                }
				$order->add_order_note('Payment signature mismatch. This indicates a possible forged request.');
				$order->add_order_note('Payment validation failed.', 1);
				wc_add_notice(__('Payment validation error. Please try again.', self::TD), 'error');
				wp_redirect($order->get_checkout_payment_url());
				break;

			default:
				$order->add_order_note(sprintf('Payment Error: Reason code: %s. Reason: %s', esc_html($payload['ReasonCode']), esc_html($payload['ReasonCodeDesc'])));
				$order->add_order_note('Payment Error', 1);
				wc_add_notice(__('Payment error. Please try again.', self::TD), 'error');
				wp_redirect($order->get_checkout_payment_url());
				break;
		}
    }

    private function validateSignature(array $payload, WC_Order $order): bool {
	    if (empty($payload['Signature']) || !is_string($payload['Signature'])) {
	        return false;
        }

        $valid_signature = $this->generateSignatureOrder($order, $payload['ResponseCode']);
        return hash_equals($valid_signature, $payload['Signature']);
    }

	public function process_payment($order_id): array {
		$order = new WC_Order($order_id);
		$order->get_checkout_payment_url(true);
		return [
		        'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
        ];
	}

	public function ipg_page(int $order_id): void {
	    $order = new WC_Order($order_id);
	    $fields = $this->getIPGFields($order);
	    $input_fields = '';
	    foreach ($fields as $key => $value) {
			$input_fields .= "<input type='hidden' name='$key' value='$value' />";
        }
	    $url = esc_attr(self::IPG_URL);
	    $submit_text = __('Proceed to payment', self::TD);
	    $cancel_text = __('Cancel and return', self::TD);
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

    private function generateSignatureOrder(WC_Order $order, string $formatted_total = null, int $currency_code = null): string {
	    $string = "{$this->pass}{$this->MerID}{$this->AcqID}{$order->get_id()}{$formatted_total}{$currency_code}";
		return base64_encode(hash('sha1', $string, true));
	}

	public function is_available(): bool {
	    if (!parent::is_available()) {
	        return false;
        }

		$currency = get_woocommerce_currency();
	    return !empty($this->MerID) && !empty($this->AcqID) && !empty($this->pass) && $this->getCurrencyList_ISO4217($currency);
	}

	private function getIPGFields(WC_Order $order): array {
		$currency = get_woocommerce_currency();
		$exponent = $this->getCurrencyExponent($currency);
		$total = $order->get_total();
		$total_formatted = (int) ($total * (10 ** $exponent));
		$total_formatted = str_pad($total_formatted, 12, 0, STR_PAD_LEFT);
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

	public function generate_markup_html($key, $data) {
		$field_key = $this->get_field_key($key);
		$defaults  = [
			'title'             => '',
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => FALSE,
			'description'       => '',
			'custom_attributes' => [],
			'value'             => '',
		];

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

	public function __debugInfo() {
		return [
		    'attributes' => self::$gateway_attributes,
        ];
	}
}
