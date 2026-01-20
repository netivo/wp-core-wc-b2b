<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 20.01.2026
 * Time: 13:51
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Admin;

use Netivo\Module\WooCommerce\B2B\Admin\Settings\Permalink;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Panel {

	public function __construct() {
		new Permalink();
	}
}