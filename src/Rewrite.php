<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 20.01.2026
 * Time: 13:43
 *
 */

namespace Netivo\Module\WooCommerce\B2B;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Rewrite {

	public function __construct() {
		add_action( 'init', [ $this, 'register_shop_endpoints' ], 1 );
		add_filter( 'query_vars', [ $this, 'register_query_vars' ], 0 );
	}

	/**
	 * Registers custom rewrite rules for shop-related endpoints in a Business-to-Business (B2B) context.
	 *
	 * This method modifies the rewrite rules to support custom URL structures for product listings,
	 * categories, cart, and checkout pages with B2B-specific query parameters.
	 *
	 * Custom rewrite rules:
	 * - Handles pagination for product listings.
	 * - Handles category filtering with pagination support.
	 * - Adds rules for custom cart and checkout page URLs.
	 *
	 * @return void
	 */
	public function register_shop_endpoints(): void {
		$permalinks = wc_get_permalink_structure();
		$b2b_base   = get_option( 'nt_b2b_base_url' );

		add_rewrite_rule( $b2b_base . '/page/([0-9]{1,})/?$', 'index.php?post_type=product&b2b=1&paged=$matches[1]', 'top' );
		add_rewrite_rule( $b2b_base . '/?$', 'index.php?post_type=product&b2b=1', 'top' );
		add_rewrite_rule( $b2b_base . '/' . $permalinks['category_rewrite_slug'] . '/(.+?)/page/([0-9]{1,})/?$', 'index.php?product_cat=$matches[1]&b2b=1&paged=$matches[2]', 'top' );
		add_rewrite_rule( $b2b_base . '/' . $permalinks['category_rewrite_slug'] . '/(.+?)/?$', 'index.php?product_cat=$matches[1]&b2b=1', 'top' );

		$cart_page = get_post( get_option( 'woocommerce_cart_page_id' ) );
		add_rewrite_rule( $b2b_base . '/' . $cart_page->post_name . '/?$', 'index.php?page_id=' . $cart_page->ID . '&b2b=1', 'top' );

		$checkout_page = get_post( get_option( 'woocommerce_checkout_page_id' ) );
		add_rewrite_rule( $b2b_base . '/' . $checkout_page->post_name . '/?$', 'index.php?page_id=' .
		                                                                       $checkout_page->ID . '&b2b=1', 'top' );
	}

	/**
	 * Registers custom query variables.
	 *
	 * Adds a custom query variable to the provided array of variables.
	 *
	 * @param array $vars An array of query variables.
	 *
	 * @return array Modified array of query variables including the custom variable.
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = 'b2b';

		return $vars;
	}
}