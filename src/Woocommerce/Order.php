<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 09.06.2026
 * Time: 00:00
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Woocommerce;

use Netivo\Module\WooCommerce\B2B\Module;
use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Order {

	public function __construct() {
		add_action( 'woocommerce_checkout_order_created', [ $this, 'mark_b2b_order' ] );
		add_filter( 'woocommerce_order_number', [ $this, 'format_order_number' ], 10, 2 );
	}

	public function mark_b2b_order( WC_Order $order ): void {
		if ( Module::is_b2b_context() ) {
			$order->update_meta_data( '_is_b2b_order', 1 );
			$order->save();
		}
	}

	public function format_order_number( string $order_number, WC_Order $order ): string {
		if ( $order->get_meta( '_is_b2b_order' ) ) {
			return $order->get_id() . ' - B2B';
		}

		return $order_number;
	}

}
