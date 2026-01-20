<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 20.01.2026
 * Time: 13:54
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Permalink {
	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_settings(): void {

		// Add the field
		add_settings_field(
			'nt_b2b_base_url',
			__( 'Baza panelu B2B', 'netivo' ),
			[ $this, 'render_slug_field' ],
			'permalink',
			'optional'
		);

		// Register the setting so WordPress saves it
		register_setting( 'permalink', 'nt_b2b_base_url', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_title',
			'default'           => 'panel-b2b',
		] );
	}

	public function render_slug_field(): void {
		$value = get_option( 'nt_b2b_base_url', 'b2b-panel' );
		echo '<input name="netivo_b2b_slug" type="text" class="regular-text code" value="' . esc_attr( $value ) . '" />';
	}
}