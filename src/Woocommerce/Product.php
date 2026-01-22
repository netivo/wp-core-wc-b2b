<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 20.01.2026
 * Time: 17:07
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Woocommerce;

use Netivo\Module\WooCommerce\B2B\Model\Discount;
use Netivo\Module\WooCommerce\B2B\Module;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Product {

	public function __construct() {
		add_filter( 'woocommerce_product_get_price', [ $this, 'change_price' ], 10, 2 );
		add_filter( 'woocommerce_product_get_backorder', [ $this, 'enable_backorder' ] );
	}

	/**
	 * Adjusts the price of a product based on the current context and applicable discounts.
	 *
	 * @param string $price The original price of the product.
	 * @param \WC_Product $product The product object for which the price is being changed.
	 *
	 * @return string The modified price of the product.
	 */
	public function change_price( string $price, \WC_Product $product ): string {
		if ( ! is_admin() && Module::is_b2b_context() ) {
			$discount = Discount::get_product_discount_for_user( get_current_user_id(), $product );
			if ( ! empty( $discount ) ) {
				if ( $discount->get_price_type() == 'fixed' ) {
					return $discount->get_value();
				} elseif ( $discount->get_price_type() == 'percentage' ) {
					return ( ( 100 - intval( $discount->get_value() ) ) / 100 ) * $price;
				}
			}
		}

		return $price;
	}

	public function enable_backorder( $backorder ) {
		if ( ! is_admin() && Module::is_b2b_context() ) {
			return 'yes';
		}

		return $backorder;
	}
}