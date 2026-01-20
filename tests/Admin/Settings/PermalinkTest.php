<?php

namespace Netivo\Module\WooCommerce\B2B\Tests\Admin\Settings;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Netivo\Module\WooCommerce\B2B\Admin\Settings\Permalink;
use PHPUnit\Framework\TestCase;

class PermalinkTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_constructor_registers_admin_init() {
		$permalink = new Permalink();
		$this->assertNotFalse( has_action( 'admin_init', [ $permalink, 'register_settings' ] ) );
	}

	public function test_register_settings() {
		Functions\expect( 'add_settings_field' )
			->atLeast()->once();

		Functions\expect( 'register_setting' )
			->atLeast()->once();

		Functions\expect( '__' )
			->andReturnFirstArg();

		$permalink = new Permalink();
		$permalink->register_settings();

		$this->assertTrue( true ); // Mark as not risky
	}

	public function test_render_slug_field() {
		Functions\expect( 'get_option' )
			->with( 'nt_b2b_base_url', 'b2b-panel' )
			->once()
			->andReturn( 'custom-b2b' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		$permalink = new Permalink();

		$this->expectOutputRegex( '/value="custom-b2b"/' );
		$permalink->render_slug_field();
	}
}
