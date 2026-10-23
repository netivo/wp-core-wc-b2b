<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 09.06.2026
 * Time: 00:00
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Woocommerce;

use Netivo\Module\WooCommerce\B2B\Model\Discount;
use Netivo\Module\WooCommerce\B2B\Module;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Order {

	public function __construct() {
		add_action( 'woocommerce_checkout_order_created', [ $this, 'mark_b2b_order' ] );
		add_filter( 'woocommerce_order_number', [ $this, 'format_order_number' ], 10, 2 );
		// Priority 20 - must run after WCML's setTotalsForOrderNewItem (10), which overwrites item totals.
		add_filter( 'woocommerce_ajax_order_item', [ $this, 'apply_b2b_price_to_admin_item' ], 20, 4 );
	}

	/**
	 * Items added from the admin order edit screen are priced by WC core (WC_Order::add_product)
	 * and WCML from the plain product price. Product::change_price() never applies there: the
	 * request is not a b2b context and the current user is the admin, not the order's client -
	 * so the item would get the b2c (possibly sale) price. Re-price it for the order's customer.
	 *
	 * @param WC_Order_Item_Product|mixed $item The added order item.
	 * @param int $item_id The order item ID.
	 * @param WC_Order|mixed $order The order the item was added to.
	 * @param WC_Product|mixed $product The added product.
	 *
	 * @return WC_Order_Item_Product|mixed
	 */
	public function apply_b2b_price_to_admin_item( $item, $item_id, $order, $product = null ) {
		if ( ! $item instanceof WC_Order_Item_Product || ! $order instanceof WC_Order || ! $product instanceof WC_Product ) {
			return $item;
		}

		if ( ! $order->get_meta( '_is_b2b_order' ) || ! $order->get_customer_id() ) {
			return $item;
		}

		// Never the b2c sale price - b2b always starts from the regular price.
		$regular_price = $product->get_regular_price( 'edit' );
		if ( $regular_price === '' ) {
			return $item;
		}

		$currency = $order->get_currency();
		$price    = (float) $regular_price;
		if ( $currency !== get_option( 'woocommerce_currency' ) ) {
			// WCML manual price for this currency, falling back to automatic conversion.
			$custom_price = $product->get_meta( '_regular_price_' . $currency );
			$price        = $custom_price !== '' ? (float) $custom_price : (float) apply_filters( 'wcml_raw_price_amount', $price, $currency );
		}

		$discount = Discount::get_product_discount_for_user( $order->get_customer_id(), $product );
		$price    = Product::apply_discount( $price, $discount, $currency );

		$total = wc_get_price_excluding_tax( $product, [
			'qty'   => $item->get_quantity(),
			'price' => $price,
			'order' => $order,
		] );

		$item->set_subtotal( $total );
		$item->set_total( $total );
		// Keep WCML's converted totals in sync, otherwise it may restore the old values.
		$item->update_meta_data( '_wcml_converted_subtotal', $item->get_subtotal() );
		$item->update_meta_data( '_wcml_converted_total', $item->get_total() );
		$item->save();

		return $item;
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
