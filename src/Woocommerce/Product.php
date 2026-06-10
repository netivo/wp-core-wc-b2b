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

	private static array $original_prices = [];

	public function __construct() {
		add_filter( 'woocommerce_product_get_price', [ $this, 'change_price' ], 5, 2 );
		add_filter( 'woocommerce_product_get_regular_price', [ $this, 'change_price' ], 5, 2 );
		add_filter( 'woocommerce_product_get_sale_price', [ $this, 'change_price' ], 5, 2 );
		//add_filter( 'woocommerce_get_price_html', [ $this, 'display_discount_price_html' ], 10, 2 );

		$this->enable_backorder();

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

		if ( ( ! is_admin() || wp_doing_ajax() ) && Module::is_b2b_context() && $price !== '') {
			$discount = Discount::get_product_discount_for_user( get_current_user_id(), $product );
			if ( ! empty( $discount ) ) {
				if((float) $discount->get_value() == 0 ) return $price;
//				self::$original_prices[ $product->get_id() ] = (float) $price;
				if ( $discount->get_price_type() == 'price' ) {
					$discount_value = (float) $discount->get_value();

					// Przelicz zniżkę kwotową jeśli waluta ≠ PLN
					$current_currency = get_woocommerce_currency();
					if ( $current_currency !== 'PLN' ) {
						$discount_value = (float) apply_filters( 'wcml_raw_price_amount', $discount_value, $current_currency );
					}

					return (string) max( 0.0, (float) $price - $discount_value );
				} elseif ( $discount->get_price_type() == 'percent' ) {
					return ( ( 100 - intval( $discount->get_value() ) ) / 100 ) * (float) $price;
				}
			}
		}

		return $price;
	}

	public function enable_backorder() {
		add_filter( 'woocommerce_product_get_backorders', function ( $backorders ) {
			if ( ( ! is_admin() || wp_doing_ajax() ) && Module::is_b2b_context() ) {
				return 'yes';
			}
			return $backorders;
		} );
		add_filter( 'woocommerce_product_variation_get_backorders', function ( $backorders ) {
			if ( ( ! is_admin() || wp_doing_ajax() ) && Module::is_b2b_context() ) {
				return 'yes';
			}
			return $backorders;
		} );
		add_filter( 'woocommerce_product_get_stock_status', function ( $status ) {
			if ( ( ! is_admin() || wp_doing_ajax() ) && Module::is_b2b_context() ) {
				return 'outofstock' === $status ? 'onbackorder' : $status;
			}
			return $status;
		} );
		add_filter( 'woocommerce_product_variation_get_stock_status', function ( $status ) {
			if ( ( ! is_admin() || wp_doing_ajax() ) && Module::is_b2b_context() ) {
				return 'outofstock' === $status ? 'onbackorder' : $status;
			}
			return $status;
		} );
		add_filter( 'woocommerce_quantity_input_max', function ( $max, $product ) {
			if ( ( ! is_admin() || wp_doing_ajax() ) && Module::is_b2b_context() ) {
				return -1;
			}
			return $max;
		}, 10, 2 );
	}
}
