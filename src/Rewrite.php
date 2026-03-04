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

/**
 * Provides custom rewrite rules and query variables for shop-related endpoints in a Business-to-Business (B2B) setup.
 *
 * This class enables custom URL structures and query variables to enhance the functionality of
 * WooCommerce shop pages in a B2B context. It modifies the URL rewrite rules and registers
 * additional query parameters to support special use cases for products, categories, cart, and checkout.
 */
class Rewrite {

	/**
	 * Class constructor.
	 *
	 * Registers actions and filters to initialize shop endpoints and query variables.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_shop_endpoints' ], 1 );
		add_filter( 'query_vars', [ $this, 'register_query_vars' ], 0 );
		add_filter( 'body_class', [ $this, 'add_b2b_class' ] );

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
		$b2b_base   = get_option( 'nt_b2b_base_url', 'panel-b2b' );

		add_rewrite_rule( $b2b_base . '/page/([0-9]{1,})/?$', 'index.php?post_type=product&b2b=1&paged=$matches[1]', 'top' );
		add_rewrite_rule( $b2b_base . '/?$', 'index.php?post_type=product&b2b=1', 'top' );
		add_rewrite_rule( $b2b_base . '/' . $permalinks['category_rewrite_slug'] . '/(.+?)/page/([0-9]{1,})/?$', 'index.php?product_cat=$matches[1]&b2b=1&paged=$matches[2]', 'top' );
		add_rewrite_rule( $b2b_base . '/' . $permalinks['category_rewrite_slug'] . '/(.+?)/?$', 'index.php?product_cat=$matches[1]&b2b=1', 'top' );

		$cart_page = get_post( get_option( 'woocommerce_cart_page_id' ) );
		add_rewrite_rule( $b2b_base . '/' . $cart_page->post_name . '/?$', 'index.php?page_id=' . $cart_page->ID . '&b2b=1', 'top' );

		$checkout_page = get_post( get_option( 'woocommerce_checkout_page_id' ) );
		add_rewrite_rule( $b2b_base . '/' . $checkout_page->post_name . '/?$', 'index.php?page_id=' .
		                                                                       $checkout_page->ID . '&b2b=1', 'top' );


		add_filter( 'template_include', function ( $template ) {

			if ( strpos( $template, 'archive-product.php' ) !== false && Module::is_b2b_context() ) {

				$template_name = 'archive-product.php';
				$template_path = 'woocommerce/b2b/';
				$default_path  = Module::get_module_path() . '/woocommerce/b2b/';

				$template = wc_locate_template( $template_name, $template_path, $default_path );

			}

			return $template;
		}, 99 );

		add_filter( 'wc_get_template_part', function ( $template, $slug, $name ) {

			if ( $slug !== 'b2b' && Module::is_b2b_context()  ) {
				return $template;
			}



			$template_name = $name.'.php';
			$template_path = 'woocommerce/'.$slug.'/';
			$default_path  = Module::get_module_path() . '/woocommerce/b2b/';

			$located = wc_locate_template( $template_name, $template_path, $default_path );

			return $located ? $located : $template;

		}, 99, 3 );
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

	public function add_b2b_class( $classes ) {
		if ( Module::is_b2b_context() ) {
			$classes[] = 'woocommerce--b2b';
		}

		return $classes;
	}


}