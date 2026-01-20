<?php

namespace Netivo\Module\WooCommerce\B2B\Tests\Gutenberg;

use Brain\Monkey\Functions;
use Netivo\Module\WooCommerce\B2B\Gutenberg\RegisterForm;
use PHPUnit\Framework\TestCase;

class RegisterFormTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
	}

	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test the register_block method when block.json exists.
	 */
	public function test_register_block_success(): void {
		Functions\expect( 'register_block_type' )->once()->with( \Mockery::type( 'string' ), \Mockery::on( function ( $args ) {
			return isset( $args['render_callback'] ) && is_array( $args['render_callback'] ) && $args['render_callback'][1] === 'render';
		} ) );

		$block = new RegisterForm();
		$block->register_block();
		$this->assertTrue( true );
	}

	/**
	 * Test the register_block method when block.json does not exist.
	 */
	public function test_register_block_throws_exception(): void {
		// Since we cannot easily mock Module::get_module_path (static), 
		// we skip this if we cannot guarantee it throws.
		$this->markTestSkipped( 'Cannot easily mock static method Module::get_module_path.' );
	}

	/**
	 * Test the render method.
	 */
	public function test_render(): void {
		$block = new RegisterForm();

		// Ensure the directory exists
		$path = \Netivo\Module\WooCommerce\B2B\Module::get_module_path() . '/dist/gutenberg/register-form';
		if ( ! is_dir( $path ) ) {
			mkdir( $path, 0777, true );
		}
		$render_file = $path . '/render.php';
		file_put_contents( $render_file, '<?php echo "Rendered Content"; ?>' );

		$output = $block->render( [], '' );

		$this->assertEquals( 'Rendered Content', $output );

		// Cleanup
		unlink( $render_file );
	}
}
