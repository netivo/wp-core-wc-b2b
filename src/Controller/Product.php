<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 20.01.2026
 * Time: 13:30
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Controller;

use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Product {
	/**
	 * Checks whether a user with the given email address exists.
	 *
	 * @param string $email The email address to check for existence.
	 *
	 * @return bool Returns true if the user does not exist, false otherwise.
	 */
	public function __construct() {
		add_action( 'wp_ajax_get_product_details', [ $this, 'my_ajax_product_details' ] );
		add_action( 'wp_ajax_nopriv_get_product_details', [ $this, 'my_ajax_product_details' ] );

		add_filter( 'posts_search', [ $this, 'product_search_by_sku' ], 110, 2 );
		add_filter( 'woocommerce_redirect_single_search_result', '__return_false' );

	}


	public function my_ajax_product_details() {
		check_ajax_referer( 'b2b_popup_nonce', 'nonce' );

		$id = $_POST['id'];

		$product_post = get_post( $id );

		ob_start();

		if ( $product_post && 'product' === $product_post->post_type ) {

			global $post, $product;
			$post    = $product_post;
			$product = wc_get_product( $id );

			setup_postdata( $post );


			wc_get_template_part( 'b2b', 'content-popup' );

			wp_reset_postdata();

		}

		$result = ob_get_clean();

		wp_send_json_success( $result );
		wp_die();
	}

	public function product_search_by_sku( $search, $wp_query ) {
		global $wpdb;

		if ( is_admin() || ! is_search() || ! isset( $wp_query->query_vars['s'] ) || ( ! is_array( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] !== "product" ) || ( is_array( $wp_query->query_vars['post_type'] ) && ! in_array( "product", $wp_query->query_vars['post_type'] ) ) ) {
			return $search;
		}

		$product_id = wc_get_product_id_by_sku( $wp_query->query_vars['s'] );
		if ( ! $product_id && $product_id != 0 ) {
			return $search;
		}

		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product->is_type( 'variation' ) ) {
				$product_id = $product->get_parent_id();
			}
		}

		$search = str_replace( 'AND (((', "AND (({$wpdb->posts}.ID IN (" . $product_id . ")) OR ((", $search );

		return $search;
	}
}