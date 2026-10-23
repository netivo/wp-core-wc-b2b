<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 08.06.2026
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Woocommerce\Gateway;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Proforma payment gateway for B2B clients.
 *
 * Behaves like a bank-transfer gateway: sets the order on-hold and displays
 * configurable instructions on the thank-you page and in order emails.
 * Visibility on the checkout is controlled externally (Module.php).
 */
class Proforma extends \WC_Payment_Gateway {

	const ID = 'nt_proforma';

	public string $instructions;

	public function __construct() {
		$this->id                 = self::ID;
		$this->icon               = '';
		$this->has_fields         = false;
		$this->method_title       = __( 'Proforma (B2B)', 'netivo' );
		$this->method_description = __( 'Płatność na podstawie faktury proforma — dostępna wyłącznie dla klientów B2B.', 'netivo' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', '' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'thankyou_page' ] );
		add_action( 'woocommerce_email_before_order_table', [ $this, 'email_instructions' ], 10, 3 );
	}

	public function init_form_fields(): void {
		$this->form_fields = [
			'enabled'      => [
				'title'   => __( 'Włącz/Wyłącz', 'netivo' ),
				'type'    => 'checkbox',
				'label'   => __( 'Włącz płatność proforma', 'netivo' ),
				'default' => 'no',
			],
			'title'        => [
				'title'       => __( 'Tytuł', 'netivo' ),
				'type'        => 'safe_text',
				'description' => __( 'Nazwa metody płatności widoczna dla klienta podczas składania zamówienia.', 'netivo' ),
				'default'     => __( 'Proforma', 'netivo' ),
				'desc_tip'    => true,
			],
			'description'  => [
				'title'       => __( 'Opis', 'netivo' ),
				'type'        => 'textarea',
				'description' => __( 'Krótki opis widoczny przy wyborze metody płatności na checkoucie.', 'netivo' ),
				'default'     => __( 'Otrzymasz fakturę proforma. Po zaksięgowaniu wpłaty zamówienie zostanie zrealizowane.', 'netivo' ),
				'desc_tip'    => true,
			],
			'instructions' => [
				'title'       => __( 'Instrukcje', 'netivo' ),
				'type'        => 'textarea',
				'description' => __( 'Treść wyświetlana na stronie potwierdzenia zamówienia oraz w e-mailu do klienta.', 'netivo' ),
				'default'     => '',
				'desc_tip'    => true,
			],
		];
	}

	/**
	 * Places the order on-hold awaiting proforma payment confirmation.
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			$order->update_status(
				apply_filters( 'nt_b2b_proforma_order_status', 'on-hold', $order ),
				__( 'Oczekiwanie na wpłatę proforma.', 'netivo' )
			);
		} else {
			$order->payment_complete();
		}

		WC()->cart->empty_cart();

		return [
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		];
	}

	/**
	 * Displays instructions on the thank-you page.
	 *
	 * @param int $order_id
	 */
	public function thankyou_page( int $order_id ): void {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}
	}

	/**
	 * Appends instructions to WooCommerce order emails.
	 *
	 * @param \WC_Order $order
	 * @param bool      $sent_to_admin
	 * @param bool      $plain_text
	 */
	public function email_instructions( \WC_Order $order, bool $sent_to_admin, bool $plain_text = false ): void {
		if ( $sent_to_admin || $order->get_payment_method() !== self::ID ) {
			return;
		}

		if ( $order->has_status( apply_filters( 'nt_b2b_proforma_email_order_status', 'on-hold', $order ) ) && $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
		}
	}
}
