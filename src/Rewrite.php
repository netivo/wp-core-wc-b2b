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


		add_filter( 'template_include', [ $this, 'change_template_include' ], 99 );
		add_filter( 'wc_get_template_part', [ $this, 'change_wc_get_template_part' ], 99, 3 );
		add_filter( 'woocommerce_locate_template', [ $this, 'b2b_locate_template' ], 10, 4 );
		add_filter( 'wc_get_template', [ $this, 'b2b_get_template' ], 5, 5 );
		add_action( 'wp_footer', [ $this, 'get_footer' ], 99 );

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
		$wc_query_vars = WC()->query->get_query_vars();
		add_rewrite_rule( $b2b_base . '/' . $checkout_page->post_name . '/'.$wc_query_vars['order-received'].'/(.+?)/?$', 'index.php?page_id=' .
		                                                                       $checkout_page->ID . '&b2b=1&'.$wc_query_vars['order-received'].'=$matches[1]', 'top' );
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

	public function change_wc_get_template_part( $template, $slug, $name ) {

		if ( $slug !== 'b2b' || ! Module::is_b2b_context() ) {
			return $template;
		}


		$template_name = $name . '.php';
		$template_path = 'woocommerce/' . $slug . '/';
		$default_path  = Module::get_module_path() . '/woocommerce/b2b/';

		$located = wc_locate_template( $template_name, $template_path, $default_path );

		return $located ? $located : $template;

	}

	/**
	 * Prepends two B2B-specific search locations to every wc_locate_template() call
	 * when the current request is a B2B context, giving the full 4-step priority:
	 *
	 *   1. Theme:  {theme}/woocommerce/b2b/{template_name}   (B2B theme override)
	 *   2. Module: {module}/woocommerce/{template_name}       (module generic WC override)
	 *   3. Theme:  {theme}/woocommerce/{template_name}        (standard theme override — already in $template)
	 *   4. Plugin: WooCommerce default                        (already in $template)
	 *
	 * Steps 3 & 4 are the result already resolved by wc_locate_template() and passed
	 * in as $template — we return it unchanged when neither custom location is found.
	 *
	 * Note: wc_get_template_part() calls with slug 'b2b' are intercepted earlier via
	 * the wc_get_template_part filter (different naming convention) and never reach
	 * wc_locate_template(), so this filter does not affect them.
	 */
	public function b2b_locate_template( string $template, string $template_name, string $template_path, string $default_path ): string {
		if ( ! Module::is_b2b_context() ) {
			return $template;
		}

		// 1. Theme: woocommerce/b2b/
		$theme_b2b = locate_template( 'woocommerce/b2b/' . $template_name );
		if ( $theme_b2b ) {
			return $theme_b2b;
		}

		// 2. Module: woocommerce/
		$module_template = Module::get_module_path() . '/woocommerce/' . $template_name;
		if ( file_exists( $module_template ) ) {
			return $module_template;
		}

		// 3 & 4: standard result (theme woocommerce/ or WooCommerce plugin default)
		return $template;
	}

	/**
	 * Re-applies the B2B template lookup after WooCommerce's template path cache.
	 *
	 * wc_get_template() caches the located path in the object cache under a key built only from
	 * template name/path/default path and WC version, and calls wc_locate_template() (and so the
	 * 'woocommerce_locate_template' filter above) only on a cache miss. With a persistent object
	 * cache (Redis) the first resolution is shared by all requests, so B2B requests can get the
	 * retail template and retail requests can get the B2B one. 'wc_get_template' runs on every
	 * call, after the cache lookup, so the context-dependent part is resolved here.
	 */
	public function b2b_get_template( $template, $template_name, $args, $template_path, $default_path ) {
		if ( Module::is_b2b_context() ) {
			return $this->b2b_locate_template( (string) $template, (string) $template_name, (string) $template_path, (string) $default_path );
		}

		// Cached path came from a B2B request - resolve the regular template instead.
		// b2b_locate_template() is a no-op outside the B2B context, so this returns the theme/WC template.
		if ( $this->is_b2b_template( (string) $template ) ) {
			return wc_locate_template( $template_name, $template_path, $default_path );
		}

		return $template;
	}

	/**
	 * Checks whether a located template path is one of the B2B overrides
	 * ({theme}/woocommerce/b2b/* or anything inside this module).
	 */
	protected function is_b2b_template( string $template ): bool {
		$template = wp_normalize_path( $template );

		if ( str_contains( $template, '/woocommerce/b2b/' ) ) {
			return true;
		}

		$module_path = Module::get_module_path();

		return ! empty( $module_path ) && str_starts_with( $template, wp_normalize_path( $module_path ) . '/' );
	}

	public function change_template_include( $template ) {

		if (
			(
				strpos( $template, 'archive-product.php' ) !== false ||
				strpos( $template, 'taxonomy-product-cat.php' ) !== false
			)
			&& Module::is_b2b_context()
		) {

			$template_name = 'archive-product.php';
			$template_path = 'woocommerce/b2b/';
			$default_path  = Module::get_module_path() . '/woocommerce/b2b/';

			$template = wc_locate_template( $template_name, $template_path, $default_path );

		}

		return $template;

	}

	public function get_footer() {
		if ( Module::is_b2b_context() ) {
			include Module::get_module_path() . '/woocommerce/b2b/footer.php';
		}
	}


}