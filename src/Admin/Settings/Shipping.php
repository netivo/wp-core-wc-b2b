<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 08.06.2026
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Adds a visibility select to every WooCommerce shipping method instance settings form.
 *
 * The stored option `nt_b2b_visibility` can be:
 *  - `both`  — visible to all users (default)
 *  - `b2b`   — visible only to B2B users
 *  - `b2c`   — visible only to B2C users
 */
class Shipping {

	public function __construct() {
		add_filter( 'woocommerce_shipping_methods', [ $this, 'register_instance_field_filters' ], PHP_INT_MAX );
	}

	/**
	 * Registers the visibility field filter for every shipping method ID.
	 *
	 * @param array $methods Map of method_id => class_name.
	 * @return array Unchanged methods array.
	 */
	public function register_instance_field_filters( array $methods ): array {
		foreach ( array_keys( $methods ) as $method_id ) {
			add_filter( "woocommerce_shipping_instance_form_fields_{$method_id}", [ $this, 'add_visibility_field' ] );
		}

		return $methods;
	}

	/**
	 * Appends the B2B visibility select to the shipping method instance settings form.
	 *
	 * @param array $fields Existing form fields for the shipping method instance.
	 * @return array
	 */
	public function add_visibility_field( array $fields ): array {
		$fields['nt_b2b_visibility'] = [
			'title'   => __( 'Widoczność B2B/B2C', 'netivo' ),
			'type'    => 'select',
			'options' => [
				'both' => __( 'Wszyscy (B2B i B2C)', 'netivo' ),
				'b2b'  => __( 'Tylko B2B', 'netivo' ),
				'b2c'  => __( 'Tylko B2C', 'netivo' ),
			],
			'default' => 'both',
		];

		return $fields;
	}
}
