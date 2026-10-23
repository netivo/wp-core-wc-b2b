<?php

namespace Netivo\Module\WooCommerce\B2B\Admin\Model;

use WP_User_Query;

class Client {

	/**
	 * Meta keys searched in addition to the default WP_User_Query search columns
	 * (user_login, user_nicename, user_email, user_url, display_name).
	 */
	protected static array $search_meta_keys = array( 'billing_company', 'billing_nip' );

	public static function get_users( $type = 'b2b_client', $search = '', $per_page = 10, $page = 1, $orderby = '', $order = '', $custom = array() ): array {
		$args = array(
			'role'   => $type,
			'number' => $per_page,
			'offset' => ( $page - 1 ) * $per_page,
			'fields' => 'all_with_meta',
		);

		if ( ! empty( $orderby ) ) { // @phpcs:ignore
			if ( 'nip' === $orderby || 'company_name' === $orderby ) {
				$args['orderby']  = 'meta_value';
				$args['meta_key'] = 'nip' === $orderby ? 'billing_nip' : 'billing_company';
			} else {
				$args['orderby'] = sanitize_text_field( $orderby );// @phpcs:ignore
			}
		}

		if ( ! empty( $order ) ) { // @phpcs:ignore
			$args['order'] = sanitize_text_field( $order );// @phpcs:ignore
		}

		if ( $type == 'customer' ) {
			if ( empty( $args['meta_query'] ) ) {
				$args['meta_query'][] = array();
			}
			$args['meta_query'][] = array(
				'key'     => 'subscribe_to_b2b',
				'value'   => 1,
				'compare' => '='
			);
		}

		$search_callback = null;

		if ( '' !== $search ) {
			$search_callback = function ( WP_User_Query $query ) use ( $search ) {
				self::add_search_clause( $query, $search );
			};
			add_action( 'pre_user_query', $search_callback );
		}

		$wp_user_search = new WP_User_Query( $args );

		if ( null !== $search_callback ) {
			remove_action( 'pre_user_query', $search_callback );
		}

		return array(
			'items' => $wp_user_search->get_results(),
			'total' => $wp_user_search->get_total(),
		);
	}

	/**
	 * Extends the user query so the search term matches the default core
	 * user fields OR any of self::$search_meta_keys (e.g. billing_company, billing_nip).
	 *
	 * Uses EXISTS subqueries rather than LEFT JOIN + GROUP BY so it can't
	 * duplicate rows or clash with the meta JOIN used when sorting by nip/company_name.
	 */
	protected static function add_search_clause( WP_User_Query $query, string $search ): void {
		global $wpdb;

		$like = '%' . $wpdb->esc_like( $search ) . '%';

		$conditions = array(
			$wpdb->prepare( "{$wpdb->users}.user_login LIKE %s", $like ),
			$wpdb->prepare( "{$wpdb->users}.user_nicename LIKE %s", $like ),
			$wpdb->prepare( "{$wpdb->users}.user_email LIKE %s", $like ),
			$wpdb->prepare( "{$wpdb->users}.display_name LIKE %s", $like ),
		);

		foreach ( self::$search_meta_keys as $meta_key ) {
			$conditions[] = $wpdb->prepare(
				"EXISTS ( SELECT 1 FROM {$wpdb->usermeta} WHERE {$wpdb->usermeta}.user_id = {$wpdb->users}.ID AND {$wpdb->usermeta}.meta_key = %s AND {$wpdb->usermeta}.meta_value LIKE %s )", // @phpcs:ignore
				$meta_key,
				$like
			);
		}

		$query->query_where .= ' AND (' . implode( ' OR ', $conditions ) . ')';
	}
}