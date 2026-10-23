<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 */

namespace Netivo\Module\WooCommerce\B2B\Woocommerce;

use Netivo\Module\WooCommerce\B2B\Module;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Currency {

	public function __construct() {
		add_filter( 'wcml_client_currency', [ $this, 'enforce_b2b_currency' ], 20 );
		add_filter( 'widget_display_callback', [ $this, 'hide_currency_switcher' ], 10, 3 );
	}

	/**
	 * Forces the WCML client currency to the value stored in user meta for B2B clients.
	 */
	public function enforce_b2b_currency( ?string $currency ): ?string {
		if ( ! Module::is_b2b_user() ) {
			return $currency;
		}

		$user         = wp_get_current_user();
		$b2b_currency = get_user_meta( $user->ID, 'b2b_currency', true );

		return ! empty( $b2b_currency ) ? $b2b_currency : $currency;
	}

	/**
	 * Hides the WCML currency switcher widget for logged-in B2B clients.
	 */
	public function hide_currency_switcher( $instance, $widget, $args ) {
		if ( Module::is_b2b_user() && $widget instanceof \WCML_Currency_Switcher_Widget ) {
			return false;
		}

		return $instance;
	}
}
