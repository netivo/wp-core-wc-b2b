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

		$id = intval( $_POST['id'] );

		add_filter( 'wcml_multi_currency_ajax_actions', function ( $actions ) {
			$actions[] = 'get_product_details';
			return $actions;
		} );

		if ( ! empty( $_POST['currency'] ) ) {
			$currency = sanitize_text_field( $_POST['currency'] );
			add_filter( 'wcml_client_currency', function () use ( $currency ) {
				return $currency;
			} );
		}

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

		$term = trim( wp_unslash( $wp_query->query_vars['s'] ) );

		if ( '' === $term ) {
			return $search;
		}

		// Matches by SKU (_sku) or EAN/GTIN (_global_unique_id), partial match on either.
		$like = '%' . $wpdb->esc_like( $term ) . '%';

		$product_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ( '_sku', '_global_unique_id' ) AND meta_value LIKE %s",
			$like
		) );

		if ( empty( $product_ids ) ) {
			return $search;
		}

		// Variations should surface their parent product in the results.
		$product_ids = array_unique( array_map( function ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product && $product->is_type( 'variation' ) ) {
				return $product->get_parent_id();
			}

			return (int) $product_id;
		}, $product_ids ) );

		$id_clause = "{$wpdb->posts}.ID IN (" . implode( ',', $product_ids ) . ")";

		return $this->or_into_search_clause( $search, $id_clause );
	}

	/**
	 * ORs an extra SQL condition into an existing "AND (...)" search clause produced
	 * by WP_Query::parse_search() (or any other posts_search filter, such as this
	 * theme's own exact-word search override in Theme\WooCommerce::search_exact_word()).
	 *
	 * Earlier versions of this method used str_replace( 'AND (((', ... ) which hard-coded
	 * WordPress core's exact default search SQL shape. That broke silently as soon as
	 * another posts_search filter running at an earlier priority produced a differently
	 * shaped clause (e.g. a single-paren wrap instead of the triple-paren default) -
	 * the string to replace simply no longer existed, so nothing was injected.
	 *
	 * This version finds the first top-level "AND ( ... )" group by counting
	 * parentheses to its real matching close, so it works regardless of what produced
	 * $search or how deeply its condition is nested.
	 */
	protected function or_into_search_clause( string $search, string $extra_condition ): string {
		$and_pos = stripos( $search, 'AND (' );

		if ( false === $and_pos ) {
			// No existing search clause (e.g. $search is empty) - stand alone.
			return " AND ({$extra_condition})";
		}

		$open_pos  = $and_pos + 4; // offset of the "(" right after "AND"
		$depth     = 0;
		$close_pos = null;

		for ( $i = $open_pos, $len = strlen( $search ); $i < $len; $i++ ) {
			if ( '(' === $search[ $i ] ) {
				$depth++;
			} elseif ( ')' === $search[ $i ] ) {
				$depth--;
				if ( 0 === $depth ) {
					$close_pos = $i;
					break;
				}
			}
		}

		if ( null === $close_pos ) {
			// Unbalanced/unexpected shape - fall back to appending our own clause.
			return $search . " OR ({$extra_condition})";
		}

		$inner = substr( $search, $open_pos + 1, $close_pos - $open_pos - 1 );

		return substr( $search, 0, $open_pos + 1 )
			. "({$inner}) OR ({$extra_condition})"
			. substr( $search, $close_pos );
	}
}