<?php

namespace Netivo\Module\WooCommerce\B2B\Admin\Model;

use WP_User_Query;

class Client {
	public static function get_users( $type = 'b2b_client', $search = '', $per_page = 10, $page = 1, $orderby = '', $order = '', $custom = array() ): array {
		$args = array(
			'role'   => 'b2b_client',
			'number' => $per_page,
			'offset' => ( $page - 1 ) * $per_page,
			'search' => $search,
			'fields' => 'all_with_meta',
		);

		if ( '' !== $args['search'] ) {
			$args['search'] = '*' . $args['search'] . '*';
		}

		if ( ! empty( $orderby ) ) { // @phpcs:ignore
			if ( in_array( $orderby, array( 'nip', 'company_name' ) ) ) {
				$args['orderby']  = 'meta_value';
				$args['meta_key'] = $orderby;
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

		$wp_user_search = new WP_User_Query( $args );

		return array(
			'items' => $wp_user_search->get_results(),
			'total' => $wp_user_search->get_total(),
		);
	}
}