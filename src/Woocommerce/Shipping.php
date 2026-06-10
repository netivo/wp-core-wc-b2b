<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 08.06.2026
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Woocommerce;

use Netivo\Module\WooCommerce\B2B\Module;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Filters available shipping rates on the frontend based on the nt_b2b_visibility setting.
 *
 *  - `both` — always visible
 *  - `b2b`  — visible only to B2B users
 *  - `b2c`  — visible only to B2C (non-B2B) users
 */
class Shipping {

	public function __construct() {
		add_filter( 'woocommerce_package_rates', [ $this, 'filter_rates' ], 10, 2 );
	}

	/**
	 * Removes rates that do not match the current user's B2B/B2C status.
	 *
	 * @param \WC_Shipping_Rate[] $rates   Available rates for the package.
	 * @param array               $package Cart package data.
	 * @return \WC_Shipping_Rate[]
	 */
	public function filter_rates( array $rates, array $package ): array {
		$is_b2b = Module::is_b2b_context();

		foreach ( $rates as $rate_id => $rate ) {
			$visibility = self::get_rate_visibility( $rate );

			if ( $visibility === 'b2b' && ! $is_b2b ) {
				unset( $rates[ $rate_id ] );
			} elseif ( $visibility === 'b2c' && $is_b2b ) {
				unset( $rates[ $rate_id ] );
			}
		}

		return $rates;
	}

	/**
	 * Returns the nt_b2b_visibility setting for a given shipping rate.
	 * Falls back to `both` when the instance cannot be loaded.
	 *
	 * @param \WC_Shipping_Rate $rate Shipping rate from the cart package.
	 * @return string  `both` | `b2b` | `b2c`
	 */
	public static function get_rate_visibility( \WC_Shipping_Rate $rate ): string {
		$instance_id = $rate->get_instance_id();
		if ( empty( $instance_id ) ) {
			return 'both';
		}

		$method = \WC_Shipping_Zones::get_shipping_method( (int) $instance_id );
		if ( ! $method instanceof \WC_Shipping_Method ) {
			return 'both';
		}

		$visibility = $method->get_instance_option( 'nt_b2b_visibility' );

		return in_array( $visibility, [ 'b2b', 'b2c', 'both' ], true ) ? $visibility : 'both';
	}
}
