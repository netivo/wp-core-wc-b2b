<?php
/**
 * Created by Netivo for Netivo Woocommerce
 * Creator: Netivo
 * Creation date: Thu, 17 Jul 2025 14:21:42 GMT
 */

namespace Netivo\Module\WooCommerce\B2B\Gutenberg;


use Netivo\Core\Gutenberg;
use Netivo\Module\WooCommerce\B2B\Module;


if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 404 Forbidden' );
	exit;
}

class RegisterForm extends Gutenberg {
	/**
	 * @var string|null
	 */
	protected ?string $callback = 'render';

	/**
	 * Render block contents
	 *
	 * @param array $attributes Block attributes
	 * @param string $content Block content
	 *
	 * @return string
	 */
	public function render( array $attributes, string $content ): string {
		ob_start();
		include Module::get_module_path() . '/dist/gutenberg/register-form/render.php';

		return ob_get_clean();
	}

	public function register_block(): void {

		$block_json = Module::get_module_path() . 'dist/gutenberg/register-form/block.json';
		if ( file_exists( $block_json ) ) {
			$args = [];
			if ( ! empty( $this->callback ) ) {
				$args['render_callback'] = array( $this, $this->callback );
			}

			register_block_type( $block_json, $args );
		} else {
			throw new \Exception( 'Block json not found.' );
		}

	}
}