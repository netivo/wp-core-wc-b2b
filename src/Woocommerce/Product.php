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
		add_filter( 'woocommerce_product_variation_get_price', [ $this, 'change_price' ], 5, 2 );
		add_filter( 'woocommerce_product_variation_get_regular_price', [ $this, 'change_price' ], 5, 2 );
		add_filter( 'woocommerce_product_variation_get_sale_price', [ $this, 'change_price' ], 5, 2 );
		//add_filter( 'woocommerce_get_price_html', [ $this, 'display_discount_price_html' ], 10, 2 );

		add_filter( 'woocommerce_get_catalog_ordering_args', [ $this, 'change_catalog_ordering_meta_key' ] );
		add_filter( 'posts_clauses', [ $this, 'add_regular_price_sort_clauses' ], 100, 2 );

		$this->enable_backorder();

	}

	/**
	 * "Sort by price" sorts directly in SQL against the stored `_price` meta value,
	 * which WooCommerce keeps synced to the b2c sale price whenever one is active -
	 * it never goes through the woocommerce_product_get_price filter above. B2b
	 * clients must never be ordered by a b2c promotion either.
	 *
	 * We can't just point WC_Query's own meta_key at `_regular_price` instead:
	 * WordPress builds that ordering as an INNER JOIN against postmeta, and variable
	 * products never have a `_regular_price` row on their parent post (only `_price`
	 * gets synced there) - they would silently vanish from the results entirely.
	 *
	 * Instead we swap to a custom orderby marker here and build our own LEFT JOIN +
	 * COALESCE ordering in add_regular_price_sort_clauses(), which keeps every
	 * product in the results (falling back to `_price` only for the rare product
	 * that truly has no `_regular_price` at all, e.g. a variable product parent).
	 *
	 * @param array $args Catalog ordering args built by WC_Query::get_catalog_ordering_args().
	 *
	 * @return array
	 */
	public function change_catalog_ordering_meta_key( array $args ): array {
		if ( ( ! is_admin() || wp_doing_ajax() ) && Module::is_b2b_context() ) {
			// Older WC: meta_key-based sort ('_price'). Newer WC: orderby is set to
			// the plain string 'price' and WC_Query joins its own price lookup table
			// via a separate posts_clauses hook instead of using meta_key at all.
			$is_price_sort = ( isset( $args['meta_key'] ) && '_price' === $args['meta_key'] )
				|| ( isset( $args['orderby'] ) && 'price' === $args['orderby'] );

			if ( $is_price_sort ) {
				$args['orderby'] = 'b2b_regular_price';
				unset( $args['meta_key'] );
			}
		}

		return $args;
	}

	/**
	 * Builds the actual ORDER BY for the 'b2b_regular_price' marker set above.
	 * LEFT JOINs both `_regular_price` and `_price` so no product is ever excluded,
	 * and sorts by whichever `_regular_price` provides, falling back to `_price`
	 * only when a product has no regular price of its own (e.g. variable parents).
	 */
	public function add_regular_price_sort_clauses( array $clauses, \WP_Query $query ): array {
		if ( 'b2b_regular_price' !== $query->get( 'orderby' ) ) {
			return $clauses;
		}

		global $wpdb;

		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS b2b_regular_price ON ( {$wpdb->posts}.ID = b2b_regular_price.post_id AND b2b_regular_price.meta_key = '_regular_price' )";
		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS b2b_active_price ON ( {$wpdb->posts}.ID = b2b_active_price.post_id AND b2b_active_price.meta_key = '_price' )";

		$order              = 'DESC' === strtoupper( (string) $query->get( 'order' ) ) ? 'DESC' : 'ASC';
		$clauses['orderby'] = "CAST( COALESCE( NULLIF( b2b_regular_price.meta_value, '' ), b2b_active_price.meta_value ) AS DECIMAL(10,2) ) {$order}";

		return $clauses;
	}

	/**
	 * Adjusts the price of a product based on the current context and applicable discounts.
	 *
	 * B2B clients must never receive a b2c promotional price: any active b2c sale is ignored
	 * entirely and the starting point is always the regular price, with only the client's own
	 * b2b discount (if any) applied on top.
	 *
	 * @param string $price The original price of the product.
	 * @param \WC_Product $product The product object for which the price is being changed.
	 *
	 * @return string The modified price of the product.
	 */
	public function change_price( string $price, \WC_Product $product ): string {

		if ( ( ! is_admin() || wp_doing_ajax() ) && Module::is_b2b_context() ) {
			$current_filter = current_filter();

			// No b2c "sale" ever exists for a b2b client.
			if ( str_ends_with( $current_filter, '_get_sale_price' ) ) {
				return '';
			}

			// The active price always starts from the regular price, never a b2c sale price.
			if ( str_ends_with( $current_filter, '_get_price' ) ) {
				$price = $product->get_regular_price( 'edit' );
			}

			if ( $price === '' ) {
				return $price;
			}

			$discount = Discount::get_product_discount_for_user( get_current_user_id(), $product );
			if ( empty( $discount ) || (float) $discount->get_value() == 0 ) {
				return $price;
			}

			return (string) self::apply_discount( (float) $price, $discount, get_woocommerce_currency() );
		}

		return $price;
	}

	/**
	 * Applies a b2b discount rule to a price.
	 *
	 * @param float $price The base (regular) price, already in $currency.
	 * @param Discount|null $discount The discount rule to apply.
	 * @param string $currency Currency of $price - amount discounts are defined in PLN and converted to it.
	 *
	 * @return float The discounted price.
	 */
	public static function apply_discount( float $price, ?Discount $discount, string $currency ): float {
		if ( empty( $discount ) || (float) $discount->get_value() == 0 ) {
			return $price;
		}

		if ( $discount->get_price_type() == 'price' ) {
			$discount_value = (float) $discount->get_value();

			// Przelicz zniżkę kwotową jeśli waluta ≠ PLN
			if ( $currency !== 'PLN' ) {
				$discount_value = (float) apply_filters( 'wcml_raw_price_amount', $discount_value, $currency );
			}

			return max( 0.0, $price - $discount_value );
		} elseif ( $discount->get_price_type() == 'percent' ) {
			return ( ( 100 - intval( $discount->get_value() ) ) / 100 ) * $price;
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
